<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RunsChatSearch;
use App\Models\SearchRequest;
use App\Services\Chat\CardFlow;
use App\Services\Chat\ChatAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Website chat (Gemini-style). The user describes (typed or via voice→text) what
 * apartment they want; the AI extracts criteria, asks for missing details with
 * inline quick-reply chips, then runs the (simulated, instant) search and returns
 * result cards — all inside the chat. Conversation state lives in the session.
 */
class ChatController extends Controller
{
    use RunsChatSearch;

    public function index(Request $request)
    {
        return view('chat');
    }

    public function reset(Request $request): JsonResponse
    {
        $request->session()->forget(['chat_history', 'chat_criteria']);

        return response()->json(['ok' => true]);
    }

    /** The first/next guided card for the current session (called on load & reset). */
    public function card(Request $request): JsonResponse
    {
        $lang = $request->query('lang', $request->session()->get('chat_lang', 'uz'));
        $lang = in_array($lang, ['uz', 'ru', 'en'], true) ? $lang : 'uz';
        $request->session()->put('chat_lang', $lang);

        $criteria = $request->session()->get('chat_criteria', []);
        $flow = app(CardFlow::class)->next($criteria, $lang);

        return response()->json([
            'reply' => $flow['reply'],
            'card' => $flow['card'],
            'ready' => $flow['ready'],
            'status' => $flow['ready'] ? 'searching' : 'asking',
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'lang' => ['nullable', 'string', 'in:uz,ru,en'],
            'field' => ['nullable', 'string', 'max:40'],
            'value' => ['nullable'], // string | array | bool | int — depends on the card
        ]);

        $lang = $data['lang'] ?? $request->session()->get('chat_lang', 'uz');
        $request->session()->put('chat_lang', $lang);

        $history = $request->session()->get('chat_history', []);
        $criteria = $request->session()->get('chat_criteria', []);

        $history[] = ['role' => 'user', 'content' => $data['message']];

        $aiReply = null;
        if (! empty($data['field'])) {
            // Structured card submission — apply directly (reliable, already localized).
            $criteria = app(CardFlow::class)->apply($criteria, $data['field'], $data['value'] ?? null);
        } else {
            // Free text — DeepSeek reads it, extracts criteria AND replies in context.
            $res = app(ChatAssistant::class)->respond($history, $criteria, $lang);
            $criteria = $res['criteria'];
            $aiReply = trim((string) ($res['reply'] ?? '')) ?: null;
        }

        // The card flow decides the next localized question + input widget.
        $flow = app(CardFlow::class)->next($criteria, $lang);
        $reply = (! $flow['ready'] && $aiReply !== null) ? $aiReply : $flow['reply'];
        $history[] = ['role' => 'ai', 'content' => $reply];

        $payload = [
            'reply' => $reply,
            'card' => $flow['card'],
            'options' => [],
            'criteria' => $criteria,
            'ready' => $flow['ready'],
            'status' => $flow['ready'] ? 'searching' : 'asking',
            'results' => [],
            'summary' => null,
        ];

        if ($flow['ready']) {
            // REAL search: the userbot contacts the owner(s) on Telegram. Return a
            // search id immediately; the frontend polls /chat/status for results.
            $search = $this->runSearch($criteria, $lang);
            $request->session()->put('chat_active_search', $search->id);
            $payload['status'] = 'searching';
            $payload['search_id'] = $search->id;
            $history = [];
            $criteria = [];
        }

        $request->session()->put('chat_history', $history);
        $request->session()->put('chat_criteria', $criteria);

        return response()->json($payload);
    }

    /** Polled by the web chat while a real search runs (mirrors the bot's progress). */
    public function status(Request $request): JsonResponse
    {
        $id = $request->session()->get('chat_active_search');
        $lang = $request->session()->get('chat_lang', 'uz');

        if (! $id) {
            return response()->json(['status' => 'idle', 'done' => true, 'results' => []]);
        }

        $search = SearchRequest::find($id);
        if (! $search) {
            $request->session()->forget('chat_active_search');

            return response()->json(['status' => 'idle', 'done' => true, 'results' => []]);
        }

        $done = in_array($search->status->value, ['completed', 'failed', 'cancelled'], true);
        $results = $done ? $this->formatResults($search) : [];

        if ($done) {
            $request->session()->forget('chat_active_search');
        }

        return response()->json([
            'status' => $search->status->value,
            'stage' => $this->searchStage($search, $done),
            'progress' => (int) $search->progress,
            'contacted' => (int) $search->contacted_count,
            'agreed' => (int) $search->agreed_count,
            'done' => $done,
            'results' => $results,
            'summary' => $done ? $this->summaryLine($search, count($results), $lang) : null,
        ]);
    }

}
