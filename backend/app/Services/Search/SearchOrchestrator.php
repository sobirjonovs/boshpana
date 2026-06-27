<?php

namespace App\Services\Search;

use App\Enums\ConversationStatus;
use App\Enums\MatchStatus;
use App\Enums\SearchStatus;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\SearchMatch;
use App\Models\SearchRequest;
use App\Models\TelegramAccount;
use App\Services\Matching\ListingMatcher;
use Illuminate\Support\Facades\Log;

/**
 * The heart of a search run (CONTRACT §5).
 *
 * Two modes:
 *  - SIMULATION (is_simulation = true): the AI plays both sides synchronously
 *    and the search finishes immediately. Fully offline.
 *  - REAL (is_simulation = false): the listings are still seed data, but the
 *    owner conversations are REAL — the orchestrator only creates pending
 *    Conversations for the top candidates, and the Telethon userbot drives them
 *    over actual Telegram (talking ONLY to the configured test accounts, never
 *    real owners). The search is completed later, as outcomes arrive, by
 *    NegotiationController::outcome().
 */
class SearchOrchestrator
{
    /** Pause between candidates so the bot poller observes incremental movement. */
    private const STEP_USLEEP = 200_000;

    /** Safety cap on negotiation turns per candidate (simulation only). */
    private const MAX_TURNS = 6;

    public function __construct(
        private readonly ListingMatcher $matcher,
        private readonly OwnerNegotiator $negotiator,
    ) {
    }

    public function run(SearchRequest $r): void
    {
        try {
            $this->begin($r);

            $candidates = $this->matcher->match($r);
            $total = $candidates->count();

            if ($total === 0) {
                $this->finish($r);

                return;
            }

            // Pre-create candidate match rows so the CRM/bot can see the funnel.
            $matchIds = [];
            foreach ($candidates as $candidate) {
                $match = SearchMatch::create([
                    'search_request_id' => $r->id,
                    'listing_id' => $candidate['listing_id'],
                    'score' => $candidate['score'],
                    'score_breakdown' => $candidate['breakdown'],
                    'status' => MatchStatus::Candidate->value,
                    'notified' => false,
                ]);
                $matchIds[$candidate['listing_id']] = $match->id;
            }

            $r->scanned_count = $total;
            $this->save($r);

            if ($r->is_simulation) {
                $this->runSimulation($r, $candidates, $matchIds, $total);
            } else {
                $this->runReal($r, $candidates, $matchIds);
            }
        } catch (\Throwable $e) {
            Log::error('SearchOrchestrator failed', [
                'search_request_id' => $r->id,
                'error' => $e->getMessage(),
            ]);
            $r->status = SearchStatus::Failed;
            $this->save($r);
        }
    }

    // -------------------------------------------------------- simulation mode

    private function runSimulation(SearchRequest $r, $candidates, array $matchIds, int $total): void
    {
        $index = 0;
        foreach ($candidates as $candidate) {
            $index++;
            $listing = Listing::find($candidate['listing_id']);
            $match = SearchMatch::find($matchIds[$candidate['listing_id']] ?? null);
            if (! $listing || ! $match) {
                continue;
            }

            $this->contactSimulated($r, $match, $listing);

            $r->progress = (int) min(99, round(($index / $total) * 100));
            $this->save($r);
            usleep(self::STEP_USLEEP);
        }

        $this->finish($r);
    }

    private function contactSimulated(SearchRequest $r, SearchMatch $match, Listing $listing): void
    {
        $conversation = Conversation::create([
            'search_request_id' => $r->id,
            'listing_id' => $listing->id,
            'listing_owner_id' => $listing->listing_owner_id,
            'channel' => 'simulation',
            'status' => ConversationStatus::Pending->value,
            'is_simulation' => true,
        ]);

        $match->forceFill([
            'conversation_id' => $conversation->id,
            'status' => MatchStatus::Contacting->value,
        ])->save();

        $r->contacted_count = (int) $r->contacted_count + 1;
        $this->save($r);

        $outcome = $this->negotiate($conversation);

        if ($outcome === 'agreed') {
            $match->forceFill([
                'status' => MatchStatus::Agreed->value,
                'reason' => $conversation->summary ?? 'Uy egasi rozi bo‘ldi.',
                'notified' => false,
            ])->save();
            $r->agreed_count = (int) $r->agreed_count + 1;
            $r->matched_count = (int) $r->matched_count + 1;
        } else {
            $match->forceFill([
                'status' => MatchStatus::Rejected->value,
                'reason' => $conversation->summary ?? 'Mos kelmadi.',
            ])->save();
        }
    }

    private function negotiate(Conversation $conversation): ?string
    {
        $outcome = null;
        for ($turn = 0; $turn < self::MAX_TURNS; $turn++) {
            $result = $this->negotiator->nextMessage($conversation, null);
            if (! empty($result['outcome'])) {
                $outcome = $result['outcome'];
            }
            if (! empty($result['done'])) {
                break;
            }
        }

        return $outcome ?? $conversation->fresh()?->outcome;
    }

    // --------------------------------------------------------------- real mode

    /**
     * Create pending Conversations for the top candidates and hand off to the
     * Telethon userbot. We deliberately DO NOT negotiate here and DO NOT finish
     * the search — that happens asynchronously as outcomes arrive.
     */
    private function runReal(SearchRequest $r, $candidates, array $matchIds): void
    {
        $cap = (int) config('boshpana.search.real_max_contacts', 3);
        $account = TelegramAccount::where('is_active', true)->first();

        $contacted = 0;
        foreach ($candidates as $candidate) {
            if ($contacted >= $cap) {
                break;
            }

            $listing = Listing::find($candidate['listing_id']);
            $match = SearchMatch::find($matchIds[$candidate['listing_id']] ?? null);
            if (! $listing || ! $match) {
                continue;
            }

            Conversation::create([
                'search_request_id' => $r->id,
                'listing_id' => $listing->id,
                'listing_owner_id' => $listing->listing_owner_id,
                'telegram_account_id' => $account?->id,
                'channel' => 'telegram',
                'status' => ConversationStatus::Pending->value, // userbot picks these up
                'is_simulation' => false,
            ]);

            $match->forceFill([
                'conversation_id' => Conversation::where('search_request_id', $r->id)
                    ->where('listing_id', $listing->id)->latest('id')->value('id'),
                'status' => MatchStatus::Contacting->value,
            ])->save();

            $contacted++;
        }

        // Leave the request "searching" with partial progress; the userbot drives
        // the conversations and NegotiationController::outcome() completes it.
        $r->forceFill([
            'contacted_count' => $contacted,
            'progress' => $contacted > 0 ? 20 : 100,
            'last_progress_at' => now(),
        ])->save();

        if ($contacted === 0) {
            $this->finish($r);
        }
    }

    // ----------------------------------------------------------------- helpers

    private function begin(SearchRequest $r): void
    {
        $r->forceFill([
            'status' => SearchStatus::Searching->value,
            'started_at' => now(),
            'progress' => 0,
            'scanned_count' => 0,
            'matched_count' => 0,
            'contacted_count' => 0,
            'agreed_count' => 0,
            'last_progress_at' => now(),
        ])->save();
    }

    private function finish(SearchRequest $r): void
    {
        $r->forceFill([
            'status' => SearchStatus::Completed->value,
            'completed_at' => now(),
            'progress' => 100,
            'last_progress_at' => now(),
        ])->save();
    }

    private function save(SearchRequest $r): void
    {
        $r->last_progress_at = now();
        $r->save();
    }
}
