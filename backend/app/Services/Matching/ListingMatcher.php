<?php

namespace App\Services\Matching;

use App\Enums\Condition;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\SearchMode;
use App\Models\Listing;
use App\Models\SearchRequest;
use Illuminate\Support\Collection;

/**
 * Scores analyzed, active listings against a SearchRequest.
 *
 * A handful of loose HARD filters keep the candidate set sane (region, a
 * generous price band); everything else is weighted SOFT scoring. Any criterion
 * the user left as "doesn't matter" (TriState::Any / null / Condition::Any /
 * Gender::Any / ...) is simply skipped and does not dilute the score.
 */
class ListingMatcher
{
    /** Relative weights per criterion (only active criteria are counted). */
    private const WEIGHTS = [
        'location' => 20,
        'price' => 20,
        'rooms' => 12,
        'condition' => 8,
        'area' => 8,
        'furniture' => 6,
        'commission' => 6,
        'metro' => 5,
        'gender' => 5,
        'marital' => 5,
        'mode' => 5,
    ];

    /**
     * @return Collection<int, array{listing_id:int, score:float, breakdown:array}>
     */
    public function match(SearchRequest $r): Collection
    {
        $minScore = (int) config('boshpana.search.min_score', 55);
        $maxCandidates = (int) config('boshpana.search.max_candidates', 60);

        $listings = $this->baseQuery($r)->get();

        return $listings
            ->map(function (Listing $listing) use ($r) {
                [$score, $breakdown] = $this->score($r, $listing);

                return [
                    'listing_id' => $listing->id,
                    'score' => $score,
                    'breakdown' => $breakdown,
                ];
            })
            ->filter(fn (array $row) => $row['score'] >= $minScore)
            ->sortByDesc('score')
            ->take($maxCandidates)
            ->values();
    }

    private function baseQuery(SearchRequest $r)
    {
        $query = Listing::query()->active()->analyzed();

        // Loose HARD filter: region (allow listings with no region set through).
        if ($r->region_id) {
            $query->where(fn ($q) => $q->where('region_id', $r->region_id)->orWhereNull('region_id'));
        }

        // Loose HARD filter: price band with generous tolerance.
        if ($r->price_max) {
            $query->where(fn ($q) => $q->where('price', '<=', (int) round($r->price_max * 1.25))->orWhereNull('price'));
        }
        if ($r->price_min) {
            $query->where(fn ($q) => $q->where('price', '>=', (int) round($r->price_min * 0.75))->orWhereNull('price'));
        }

        return $query;
    }

    /**
     * @return array{0: float, 1: array}
     */
    private function score(SearchRequest $r, Listing $l): array
    {
        $parts = [];

        $parts['location'] = $this->scoreLocation($r, $l);
        $parts['price'] = $this->scorePrice($r, $l);
        $parts['rooms'] = $this->scoreRooms($r, $l);
        $parts['condition'] = $this->scoreCondition($r, $l);
        $parts['area'] = $this->scoreArea($r, $l);
        $parts['furniture'] = $this->scoreTriState($r->has_furniture?->toBool(), $l->has_furniture);
        $parts['commission'] = $this->scoreTriState($r->has_commission?->toBool(), $l->has_commission);
        $parts['metro'] = $this->scoreTriState($r->near_metro?->toBool(), $l->near_metro);
        $parts['gender'] = $this->scorePreference($this->activeGender($r->gender), $l->gender_pref?->value);
        $parts['marital'] = $this->scorePreference($this->activeMarital($r->marital_status), $l->marital_pref?->value);
        $parts['mode'] = $this->scoreMode($r, $l);

        $earned = 0.0;
        $total = 0.0;
        $breakdown = [];

        foreach (self::WEIGHTS as $key => $weight) {
            $fraction = $parts[$key];
            if ($fraction === null) {
                continue; // criterion not active -> skip entirely
            }
            $earned += $weight * $fraction;
            $total += $weight;
            $breakdown[$key] = round($fraction * 100, 1);
        }

        $score = $total > 0 ? round(($earned / $total) * 100, 2) : 0.0;

        return [$score, $breakdown];
    }

    // ------------------------------------------------------------- Criteria

    private function scoreLocation(SearchRequest $r, Listing $l): ?float
    {
        $hasRegion = (bool) $r->region_id;
        $hasDistrict = (bool) $r->district_id;
        if (! $hasRegion && ! $hasDistrict) {
            return null;
        }

        $regionMatch = $l->region_id && $l->region_id === $r->region_id ? 1.0 : 0.0;
        $districtMatch = $l->district_id && $l->district_id === $r->district_id ? 1.0 : 0.0;

        return match (true) {
            $hasRegion && $hasDistrict => 0.6 * $regionMatch + 0.4 * $districtMatch,
            $hasRegion => $regionMatch,
            default => $districtMatch,
        };
    }

    private function scorePrice(SearchRequest $r, Listing $l): ?float
    {
        if (! $r->price_min && ! $r->price_max) {
            return null;
        }
        if (! $l->price) {
            return 0.5; // unknown price -> neutral
        }

        return $this->rangeFraction($l->price, $r->price_min, $r->price_max);
    }

    private function scoreArea(SearchRequest $r, Listing $l): ?float
    {
        if (! $r->area_min && ! $r->area_max) {
            return null;
        }
        if (! $l->area) {
            return 0.5;
        }

        return $this->rangeFraction($l->area, $r->area_min, $r->area_max);
    }

    private function scoreRooms(SearchRequest $r, Listing $l): ?float
    {
        $wanted = array_values(array_filter(array_map('intval', (array) $r->rooms)));
        if (empty($wanted)) {
            return null;
        }
        if (! $l->rooms) {
            return 0.4;
        }
        if (in_array($l->rooms, $wanted, true)) {
            return 1.0;
        }

        // Off-by-one is a partial match.
        foreach ($wanted as $w) {
            if (abs($w - $l->rooms) === 1) {
                return 0.5;
            }
        }

        return 0.0;
    }

    private function scoreCondition(SearchRequest $r, Listing $l): ?float
    {
        if (! $r->condition || $r->condition === Condition::Any) {
            return null;
        }
        $listing = $l->condition;
        if (! $listing || $listing === Condition::Any) {
            return 0.5;
        }
        if ($listing === $r->condition) {
            return 1.0;
        }

        // Wanting "average" but getting "excellent" is still good; the reverse is weak.
        return $r->condition === Condition::Average && $listing === Condition::Excellent ? 0.75 : 0.3;
    }

    /** Generic yes/no preference (furniture, commission, metro). */
    private function scoreTriState(?bool $wanted, ?bool $actual): ?float
    {
        if ($wanted === null) {
            return null;
        }
        if ($actual === null) {
            return 0.5;
        }

        return $wanted === $actual ? 1.0 : 0.0;
    }

    /**
     * Owner preference: a listing whose preference is "any" accepts everyone.
     */
    private function scorePreference(?string $wanted, ?string $listingPref): ?float
    {
        if ($wanted === null) {
            return null;
        }
        if ($listingPref === null || $listingPref === 'any') {
            return 1.0;
        }

        return $listingPref === $wanted ? 1.0 : 0.0;
    }

    private function scoreMode(SearchRequest $r, Listing $l): ?float
    {
        if (! $r->mode) {
            return null;
        }
        if (! $l->mode) {
            return 0.5;
        }
        if ($l->mode === $r->mode) {
            // For partnership, reward when the listing can host the partner count.
            if ($r->mode === SearchMode::Partnership && $r->partners_count && $l->partners_needed) {
                return $l->partners_needed >= $r->partners_count ? 1.0 : 0.7;
            }

            return 1.0;
        }

        return 0.4;
    }

    // ------------------------------------------------------------- Utilities

    private function activeGender(?Gender $g): ?string
    {
        return $g && $g !== Gender::Any ? $g->value : null;
    }

    private function activeMarital(?MaritalStatus $m): ?string
    {
        return $m && $m !== MaritalStatus::Any ? $m->value : null;
    }

    /**
     * 1.0 inside [min,max]; decays smoothly outside by relative distance.
     */
    private function rangeFraction(int $value, ?int $min, ?int $max): float
    {
        if ($min && $value < $min) {
            $deficit = ($min - $value) / max(1, $min);

            return max(0.0, 1.0 - $deficit * 2);
        }
        if ($max && $value > $max) {
            $excess = ($value - $max) / max(1, $max);

            return max(0.0, 1.0 - $excess * 2);
        }

        return 1.0;
    }
}
