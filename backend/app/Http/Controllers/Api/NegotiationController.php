<?php

namespace App\Http\Controllers\Api;

use App\Enums\ConversationStatus;
use App\Enums\MatchStatus;
use App\Enums\MessageRole;
use App\Enums\SearchStatus;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\SearchMatch;
use App\Models\SearchRequest;
use App\Services\Search\OwnerNegotiator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NegotiationController extends Controller
{
    private const DEFAULT_OPENING = 'Assalomu alaykum! E’loningiz bo‘yicha bir necha savol bor edi, mumkinmi?';

    /** Real-mode conversations awaiting first outreach. */
    public function tasks(): JsonResponse
    {
        // Pending AND not yet opened. The "no AI message" guard is bulletproof:
        // once the userbot has sent the opening, this conversation never appears
        // again, even across userbot restarts or status races — so the owner is
        // contacted exactly once.
        $conversations = Conversation::query()
            ->where('is_simulation', false)
            ->where('status', ConversationStatus::Pending)
            ->whereDoesntHave('messages', fn ($q) => $q->where('role', MessageRole::Ai))
            ->with(['listing', 'messages'])
            ->get();

        $tasks = $conversations->map(function (Conversation $conversation) {
            $opening = $conversation->messages
                ->firstWhere('role', MessageRole::Ai)?->content
                ?? self::DEFAULT_OPENING;

            return [
                'conversation_id' => $conversation->id,
                'listing' => $conversation->listing ? [
                    'id' => $conversation->listing->id,
                    'title' => $conversation->listing->title,
                    'contact' => $conversation->listing->contact ?? [],
                ] : null,
                'opening_message' => $opening,
                'account_id' => $conversation->telegram_account_id,
            ];
        })->values();

        return response()->json(['data' => $tasks]);
    }

    public function reply(Request $request, Conversation $conversation): JsonResponse
    {
        $data = $request->validate([
            'owner_message' => ['nullable', 'string'],
        ]);

        $result = app(OwnerNegotiator::class)
            ->nextMessage($conversation, $data['owner_message'] ?? null);

        return response()->json([
            'reply' => $result['reply'] ?? null,
            'done' => (bool) ($result['done'] ?? false),
            'outcome' => $result['outcome'] ?? null,
        ]);
    }

    public function outcome(Request $request, Conversation $conversation): JsonResponse
    {
        $data = $request->validate([
            'outcome' => ['required', 'string', 'in:agreed,declined,no_response'],
            'summary' => ['sometimes', 'nullable', 'string'],
        ]);

        $statusMap = [
            'agreed' => ConversationStatus::Agreed,
            'declined' => ConversationStatus::Declined,
            'no_response' => ConversationStatus::NoResponse,
        ];

        $conversation->update(array_filter([
            'outcome' => $data['outcome'],
            'summary' => $data['summary'] ?? null,
            'status' => $statusMap[$data['outcome']],
            'closed_at' => now(),
        ], fn ($v) => $v !== null));

        // Flow the outcome into the search funnel: update the linked match and,
        // once every contacted conversation has concluded, complete the search.
        $match = SearchMatch::where('conversation_id', $conversation->id)->first();
        if ($match) {
            $agreed = $data['outcome'] === 'agreed';
            $match->forceFill([
                'status' => ($agreed ? MatchStatus::Agreed : MatchStatus::Rejected)->value,
                'reason' => $data['summary'] ?? ($agreed ? 'Uy egasi rozi bo‘ldi.' : 'Mos kelmadi.'),
                'notified' => false,
            ])->save();
        }

        if ($conversation->searchRequest) {
            $this->refreshSearch($conversation->searchRequest);
        }

        return response()->json(['ok' => true]);
    }

    /** Recompute a real-mode search's counters and complete it when all conversations are done. */
    private function refreshSearch(SearchRequest $search): void
    {
        $base = Conversation::where('search_request_id', $search->id);
        $total = (clone $base)->count();
        $open = (clone $base)->whereNull('closed_at')->count();
        $agreed = (clone $base)->where('outcome', 'agreed')->count();

        $search->forceFill([
            'agreed_count' => $agreed,
            'matched_count' => $agreed,
            'last_progress_at' => now(),
        ])->save();

        if ($total > 0 && $open === 0) {
            $search->forceFill([
                'status' => SearchStatus::Completed->value,
                'completed_at' => now(),
                'progress' => 100,
            ])->save();
        } else {
            $done = max(0, $total - $open);
            $search->forceFill([
                'progress' => $total > 0 ? (int) min(95, 20 + round(($done / $total) * 75)) : 20,
            ])->save();
        }
    }
}
