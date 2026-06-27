<?php

namespace App\Services\Ai;

use App\Models\District;
use App\Models\Listing;
use App\Models\Region;
use Illuminate\Support\Facades\Log;

/**
 * Turns a raw scraped Listing into structured rental criteria.
 *
 * When the Anthropic API is configured it sends the title/description plus up
 * to a few photos to Claude and asks for a JSON object (region/district,
 * price, rooms, condition, preferences, amenities, summary). When the API is
 * disabled it falls back to a deterministic keyword/regex analyzer so the whole
 * product keeps working fully offline (simulation mode).
 */
class ListingAnalyzer
{
    private const MAX_IMAGES = 4;

    public function __construct(private readonly AiClient $ai)
    {
    }

    public function analyze(Listing $listing): void
    {
        if ($this->ai->enabled()) {
            try {
                $this->aiAnalyze($listing);

                return;
            } catch (\Throwable $e) {
                Log::warning('ListingAnalyzer AI pass failed, falling back to heuristics', [
                    'listing_id' => $listing->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->heuristicAnalyze($listing);
    }

    // ---------------------------------------------------------------- AI pass

    private function aiAnalyze(Listing $listing): void
    {
        $content = [AnthropicClient::textBlock($this->prompt($listing))];

        foreach (array_slice((array) $listing->images, 0, self::MAX_IMAGES) as $url) {
            if (is_string($url) && $url !== '') {
                $content[] = AnthropicClient::imageBlock($url);
            }
        }

        $messages = [['role' => 'user', 'content' => $content]];

        $data = $this->ai->structured($messages, $this->schema(), $this->system());

        $this->persist($listing, $data, (float) ($data['confidence'] ?? 0.6));
    }

    private function system(): string
    {
        return <<<'TXT'
You are an extraction engine for a Tashkent (Uzbekistan) apartment-rental search.
Given a listing's title, description and photos, extract clean structured rental
criteria as JSON that conforms exactly to the provided schema. Money is in US
dollars (integer). If a fact is not stated, return null (do not guess wildly).
IMPORTANT: when the renovation level / condition is NOT described in the text,
infer it from the photos — modern/renovated interiors => "excellent", plain or
older interiors => "average". Map the neighbourhood to one of the region/district
slugs listed in the user message. Write the "summary" in short, friendly Uzbek.
TXT;
    }

    private function prompt(Listing $listing): string
    {
        $regions = Region::query()->orderBy('sort')->get()
            ->map(fn (Region $r) => "{$r->slug} ({$r->name_uz})")->implode(', ');

        $districts = District::query()->orderBy('sort')->get()
            ->map(fn (District $d) => "{$d->slug} ({$d->name_uz})")->implode(', ');

        $title = $listing->title ?? '';
        $description = $listing->description ?? '';

        return <<<TXT
Listing title: {$title}

Listing description:
{$description}

Valid region slugs: {$regions}
Valid district slugs: {$districts}

Return the structured rental criteria for this listing.
TXT;
    }

    private function schema(): array
    {
        $nullableInt = ['type' => ['integer', 'null']];
        $nullableBool = ['type' => ['boolean', 'null']];
        $nullableString = ['type' => ['string', 'null']];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'region_slug' => $nullableString,
                'district_slug' => $nullableString,
                'price_usd' => $nullableInt,
                'rooms' => $nullableInt,
                'area_m2' => $nullableInt,
                'condition' => ['type' => 'string', 'enum' => ['average', 'excellent', 'any']],
                'has_furniture' => $nullableBool,
                'has_commission' => $nullableBool,
                'near_metro' => $nullableBool,
                'metro_station' => $nullableString,
                'gender_pref' => ['type' => 'string', 'enum' => ['male', 'female', 'any']],
                'marital_pref' => ['type' => 'string', 'enum' => ['single', 'married', 'any']],
                'mode' => ['type' => 'string', 'enum' => ['solo', 'partnership']],
                'partners_needed' => $nullableInt,
                'amenities' => ['type' => 'array', 'items' => ['type' => 'string']],
                'summary' => ['type' => 'string'],
                'confidence' => ['type' => 'number'],
            ],
            'required' => [
                'region_slug', 'district_slug', 'price_usd', 'rooms', 'area_m2', 'condition',
                'has_furniture', 'has_commission', 'near_metro', 'metro_station',
                'gender_pref', 'marital_pref', 'mode', 'partners_needed',
                'amenities', 'summary', 'confidence',
            ],
        ];
    }

    // ------------------------------------------------------------- Heuristics

    private function heuristicAnalyze(Listing $listing): void
    {
        $text = trim(($listing->title ?? '').' '.($listing->description ?? ''));
        $lower = mb_strtolower($text);

        $price = $listing->price ?: $this->extractPrice($text);
        $rooms = $listing->rooms ?: $this->extractRooms($lower);
        $area = $listing->area ?: $this->extractArea($lower);

        $nearMetro = $this->hasAny($lower, ['metro', 'метро']);
        $furniture = $this->hasAny($lower, ['mebel', 'jihoz', 'konditsioner', 'kir mashina', 'мебел', 'кондиционер']);
        $commission = $this->hasAny($lower, ['vositachi', 'makler', 'komissiya', 'broker', 'маклер', 'комисси']);

        $gender = match (true) {
            $this->hasAny($lower, ['qizlar', 'ayollar', 'qiz bola', 'девушк', 'женщин']) => 'female',
            $this->hasAny($lower, ['yigit', 'erkak', "o'g'il", 'парн', 'мужчин']) => 'male',
            default => 'any',
        };

        $marital = $this->hasAny($lower, ['oila', 'oilaga', 'oilali', 'семей', 'семьи']) ? 'married' : 'any';
        $mode = $this->hasAny($lower, ['sherik', 'sherikchilik', 'напарник']) ? 'partnership' : 'solo';

        $condition = $this->hasAny($lower, ['zo‘r', "zo'r", 'yevro', 'euro', 'remont', "ta'mir", 'евро', 'ремонт'])
            ? 'excellent'
            : 'average';

        $region = $this->matchRegionByName($text);
        $district = $this->matchDistrictByName($text, $region?->id);

        $amenities = [];
        foreach ([
            'wifi' => ['wifi', 'wi-fi', 'internet'],
            'konditsioner' => ['konditsioner', 'кондиционер'],
            'kir mashina' => ['kir mashina', 'стиральн'],
            'muzlatgich' => ['muzlatgich', 'холодильник'],
        ] as $label => $needles) {
            if ($this->hasAny($lower, $needles)) {
                $amenities[] = $label;
            }
        }

        $data = [
            'region_slug' => $region?->slug,
            'district_slug' => $district?->slug,
            'price_usd' => $price,
            'rooms' => $rooms,
            'area_m2' => $area,
            'condition' => $condition,
            'has_furniture' => $furniture ?: null,
            'has_commission' => $commission ?: null,
            'near_metro' => $nearMetro ?: null,
            'metro_station' => $listing->metro_station,
            'gender_pref' => $gender,
            'marital_pref' => $marital,
            'mode' => $mode,
            'partners_needed' => $mode === 'partnership' ? ($listing->partners_needed ?: 1) : null,
            'amenities' => $amenities,
            'summary' => $this->heuristicSummary($rooms, $price, $region, $district, $condition),
            'confidence' => 0.4,
            'engine' => 'heuristic',
        ];

        $this->persist($listing, $data, 0.4);
    }

    private function heuristicSummary(?int $rooms, ?int $price, ?Region $region, ?District $district, string $condition): string
    {
        $parts = [];
        if ($rooms) {
            $parts[] = "{$rooms} xonali";
        }
        $place = $district?->name_uz ?? $region?->name_uz;
        if ($place) {
            $parts[] = $place;
        }
        if ($price) {
            $parts[] = "{$price}\$";
        }
        $parts[] = $condition === 'excellent' ? 'zo‘r holatda' : 'o‘rtacha holatda';

        return 'Ijaraga kvartira: '.implode(', ', $parts).'.';
    }

    // ---------------------------------------------------------------- Persist

    private function persist(Listing $listing, array $data, float $confidence): void
    {
        $region = $this->resolveRegion($data['region_slug'] ?? null) ?? $listing->region;
        $district = $this->resolveDistrict($data['district_slug'] ?? null, $region?->id) ?? $listing->district;

        $listing->fill([
            'region_id' => $region?->id ?? $listing->region_id,
            'district_id' => $district?->id ?? $listing->district_id,
            'price' => $this->intOrNull($data['price_usd'] ?? null) ?? $listing->price,
            'rooms' => $this->intOrNull($data['rooms'] ?? null) ?? $listing->rooms,
            'area' => $this->intOrNull($data['area_m2'] ?? null) ?? $listing->area,
            'condition' => $this->enumValue($data['condition'] ?? null, ['average', 'excellent', 'any'], 'average'),
            'has_furniture' => $this->boolOrNull($data['has_furniture'] ?? null) ?? $listing->has_furniture,
            'has_commission' => $this->boolOrNull($data['has_commission'] ?? null) ?? $listing->has_commission,
            'near_metro' => $this->boolOrNull($data['near_metro'] ?? null) ?? $listing->near_metro,
            'metro_station' => $data['metro_station'] ?? $listing->metro_station,
            'gender_pref' => $this->enumValue($data['gender_pref'] ?? null, ['male', 'female', 'any'], 'any'),
            'marital_pref' => $this->enumValue($data['marital_pref'] ?? null, ['single', 'married', 'any'], 'any'),
            'mode' => $this->enumValue($data['mode'] ?? null, ['solo', 'partnership'], 'solo'),
            'partners_needed' => $this->intOrNull($data['partners_needed'] ?? null) ?? $listing->partners_needed,
            'amenities' => array_values(array_filter((array) ($data['amenities'] ?? []), 'is_string')),
            'ai_analyzed' => true,
            'ai_summary' => $data['summary'] ?? $listing->ai_summary,
            'ai_attributes' => $data,
            'ai_confidence' => max(0.0, min(1.0, $confidence)),
            'analyzed_at' => now(),
        ]);

        $listing->save();
    }

    // ---------------------------------------------------------------- Helpers

    private function resolveRegion(?string $slug): ?Region
    {
        if (! $slug) {
            return null;
        }

        return Region::query()->where('slug', $slug)->first()
            ?? Region::query()
                ->where('name_uz', 'like', "%{$slug}%")
                ->orWhere('name_ru', 'like', "%{$slug}%")
                ->orWhere('name_en', 'like', "%{$slug}%")
                ->first();
    }

    private function resolveDistrict(?string $slug, ?int $regionId): ?District
    {
        if (! $slug) {
            return null;
        }

        $query = District::query()->where('slug', $slug);
        if ($regionId) {
            $query->orWhere(fn ($q) => $q->where('region_id', $regionId)
                ->where(fn ($qq) => $qq->where('name_uz', 'like', "%{$slug}%")
                    ->orWhere('name_ru', 'like', "%{$slug}%")
                    ->orWhere('name_en', 'like', "%{$slug}%")));
        }

        return $query->first();
    }

    private function matchRegionByName(string $text): ?Region
    {
        foreach (Region::query()->orderBy('sort')->get() as $region) {
            foreach ([$region->name_uz, $region->name_ru, $region->name_en] as $name) {
                if ($name && mb_stripos($text, $name) !== false) {
                    return $region;
                }
            }
        }

        return null;
    }

    private function matchDistrictByName(string $text, ?int $regionId): ?District
    {
        $query = District::query()->orderBy('sort');
        if ($regionId) {
            $query->where('region_id', $regionId);
        }

        foreach ($query->get() as $district) {
            foreach ([$district->name_uz, $district->name_ru, $district->name_en] as $name) {
                if ($name && mb_stripos($text, $name) !== false) {
                    return $district;
                }
            }
        }

        return null;
    }

    private function extractPrice(string $text): ?int
    {
        if (preg_match('/(\d[\d\s]{1,5})\s*(?:\$|y\.?e|у\.?е|usd|dollar|долл)/iu', $text, $m)) {
            return (int) preg_replace('/\s+/', '', $m[1]);
        }
        if (preg_match('/\$\s*(\d[\d\s]{1,5})/u', $text, $m)) {
            return (int) preg_replace('/\s+/', '', $m[1]);
        }

        return null;
    }

    private function extractRooms(string $lower): ?int
    {
        if (preg_match('/(\d+)\s*[- ]?\s*xona/u', $lower, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/(\d+)\s*комнат/u', $lower, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    private function extractArea(string $lower): ?int
    {
        if (preg_match('/(\d{2,4})\s*(?:m2|m²|кв|sotix|кв\.?м)/u', $lower, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    private function hasAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (mb_stripos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    private function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function boolOrNull(mixed $value): ?bool
    {
        return is_bool($value) ? $value : null;
    }

    private function enumValue(mixed $value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }
}
