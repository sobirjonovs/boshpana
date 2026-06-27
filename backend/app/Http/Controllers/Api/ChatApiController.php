<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RunsChatSearch;
use App\Http\Controllers\Controller;
use App\Models\SearchRequest;
use App\Services\Chat\CardFlow;
use App\Services\Chat\ChatAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stateless chat API for the Flutter mobile app — the same guided-card +
 * free-text flow as the website, but the CLIENT holds the criteria and passes
 * it on every call (no cookies/CSRF). Mirrors ChatController, minus the session.
 *
 *   POST /api/v1/chat/card    {criteria?, lang?}                  -> {reply, card, ready, criteria}
 *   POST /api/v1/chat/send    {message, lang?, field?, value?, criteria?}
 *                                                                 -> {reply, card, criteria, ready, status, search_id?}
 *   GET  /api/v1/chat/status/{search}?lang=                       -> {status, progress, done, results, summary, ...}
 */
class ChatApiController extends Controller
{
    use RunsChatSearch;

    /** The next guided card for the given criteria. */
    public function card(Request $request): JsonResponse
    {
        $lang = $this->lang($request);
        $criteria = $this->criteria($request);

        $flow = app(CardFlow::class)->next($criteria, $lang);

        return response()->json([
            'reply' => $flow['reply'],
            'card' => $flow['card'],
            'ready' => $flow['ready'],
            'criteria' => $criteria,
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'lang' => ['nullable', 'string', 'in:uz,ru,en'],
            'field' => ['nullable', 'string', 'max:40'],
            'value' => ['nullable'],
            'criteria' => ['nullable', 'array'],
        ]);

        $lang = $data['lang'] ?? 'uz';
        $criteria = (array) ($data['criteria'] ?? []);

        $aiReply = null;
        if (! empty($data['field'])) {
            $criteria = app(CardFlow::class)->apply($criteria, $data['field'], $data['value'] ?? null);
        } else {
            // Free text: let DeepSeek read it, extract criteria AND acknowledge it.
            $res = app(ChatAssistant::class)->respond(
                [['role' => 'user', 'content' => $data['message']]], $criteria, $lang);
            $criteria = $res['criteria'];
            $aiReply = trim((string) ($res['reply'] ?? '')) ?: null;
        }

        $flow = app(CardFlow::class)->next($criteria, $lang);

        $payload = [
            // For free text, show DeepSeek's conversational reply so the user sees
            // they were understood; for cards / the search step use the flow reply.
            'reply' => (! $flow['ready'] && $aiReply !== null) ? $aiReply : $flow['reply'],
            'card' => $flow['card'],
            'criteria' => $criteria,
            'ready' => $flow['ready'],
            'status' => $flow['ready'] ? 'searching' : 'asking',
            'search_id' => null,
        ];

        if ($flow['ready']) {
            $search = $this->runSearch($criteria, $lang);
            $payload['search_id'] = $search->id;
            $payload['status'] = 'searching';
            $payload['criteria'] = []; // reset; the app starts a fresh flow next time
        }

        return response()->json($payload);
    }

    public function status(Request $request, SearchRequest $search): JsonResponse
    {
        $lang = $this->lang($request);

        $done = in_array($search->status->value, ['completed', 'failed', 'cancelled'], true);
        $results = $done ? $this->formatResults($search) : [];

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

    private function lang(Request $request): string
    {
        $lang = $request->input('lang', $request->query('lang', 'uz'));

        return in_array($lang, ['uz', 'ru', 'en'], true) ? $lang : 'uz';
    }

    private function criteria(Request $request): array
    {
        $c = $request->input('criteria', []);

        return is_array($c) ? $c : [];
    }
}
