<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Conversation;
use App\Models\SearchMatch;
use App\Models\SearchRequest;
use App\Models\TelegramUser;
use App\Services\Search\SearchOrchestrator;

/**
 * Shared search plumbing for the website chat and the mobile API: build a real
 * SearchRequest from gathered criteria, format agreed matches as result cards,
 * and a localized summary line. Used by ChatController (session) and
 * ChatApiController (stateless / Flutter app).
 */
trait RunsChatSearch
{
    /** Synthetic user that owns all web/app guest searches. */
    private int $webUserTelegramId = 900000001;

    protected function runSearch(array $c, string $lang): SearchRequest
    {
        $user = TelegramUser::firstOrCreate(
            ['telegram_id' => $this->webUserTelegramId],
            ['first_name' => 'Web', 'language' => $lang],
        );

        $search = SearchRequest::create([
            'telegram_user_id' => $user->id,
            'region_id' => $c['region_id'] ?? null,
            'district_id' => $c['district_id'] ?? null,
            'price_min' => $c['price_min'] ?? null,
            'price_max' => $c['price_max'] ?? null,
            'rooms' => $c['rooms'] ?? null,
            'condition' => $c['condition'] ?? 'any',
            'has_furniture' => $c['has_furniture'] ?? 'any',
            'has_commission' => $c['has_commission'] ?? 'any',
            'area_min' => $c['area_min'] ?? null,
            'area_max' => $c['area_max'] ?? null,
            'mode' => $c['mode'] ?? 'solo',
            'partners_count' => $c['partners_count'] ?? null,
            'near_metro' => $c['near_metro'] ?? 'any',
            'gender' => $c['gender'] ?? 'any',
            'marital_status' => $c['marital_status'] ?? 'any',
            'free_text' => null,
            'is_simulation' => false, // REAL: userbot contacts the owner on Telegram
            'status' => 'queued',
        ]);

        app(SearchOrchestrator::class)->run($search->fresh());

        return $search->fresh();
    }

    protected function formatResults(SearchRequest $search): array
    {
        return SearchMatch::where('search_request_id', $search->id)
            ->where('status', 'agreed')
            ->with(['listing.source', 'listing.region', 'listing.district'])
            ->orderByDesc('score')
            ->take(6)
            ->get()
            ->map(function (SearchMatch $m) {
                $l = $m->listing;

                return [
                    'title' => $l?->title,
                    'price' => $l?->price,
                    'currency' => $l?->currency ?? 'USD',
                    'rooms' => $l?->rooms,
                    'area' => $l?->area,
                    'district' => $l?->district?->name_uz,
                    'region' => $l?->region?->name_uz,
                    'condition' => $l?->condition?->getLabel(),
                    'near_metro' => $l?->near_metro,
                    'source' => $l?->source?->name,
                    'url' => $l?->url,
                    'images' => array_values(array_slice((array) ($l?->images ?? []), 0, 1)),
                    'score' => (int) round($m->score),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Current high-level phase of a search, for the live progress UI:
     * searching → checking → contacting → waiting → done.
     */
    protected function searchStage(SearchRequest $search, bool $done): string
    {
        if ($done) {
            return 'done';
        }

        $base = Conversation::where('search_request_id', $search->id);
        $total = (clone $base)->count();

        if ($total === 0) {
            return (int) $search->scanned_count > 0 ? 'checking' : 'searching';
        }

        // Any owner already messaged (opening sent) → we're waiting on replies.
        $opened = (clone $base)
            ->whereIn('status', ['contacted', 'replied', 'agreed', 'declined', 'no_response'])
            ->count();

        return $opened > 0 ? 'waiting' : 'contacting';
    }

    protected function summaryLine(SearchRequest $search, int $count, string $lang): string
    {
        return match ($lang) {
            'ru' => "Готово! Связались с {$search->contacted_count} владельцами, согласились: {$count}.",
            'en' => "Done! Contacted {$search->contacted_count} owners, {$count} agreed.",
            default => "Tayyor! {$search->contacted_count} ta uy egasi bilan gaplashdim, {$count} tasi rozi bo‘ldi.",
        };
    }
}
