<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeListingJob;
use App\Models\Listing;
use App\Models\ListingOwner;
use App\Models\Region;
use App\Models\Source;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IngestController extends Controller
{
    /** Active sources + their parser config. */
    public function sources(): JsonResponse
    {
        $sources = Source::where('is_active', true)
            ->get()
            ->map(fn (Source $source) => [
                'id' => $source->id,
                'slug' => $source->slug,
                'name' => $source->name,
                'type' => $source->type?->value,
                'base_url' => $source->base_url,
                'config' => $source->config ?? [],
            ]);

        return response()->json(['data' => $sources]);
    }

    /** Upsert raw listings coming from a parser. */
    public function listings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'source' => ['required', 'string'],
            'listings' => ['required', 'array'],
            'listings.*.external_id' => ['required'],
            'listings.*.title' => ['nullable', 'string'],
            'listings.*.description' => ['nullable', 'string'],
            'listings.*.url' => ['nullable', 'string'],
            'listings.*.price' => ['nullable', 'integer'],
            'listings.*.currency' => ['nullable', 'string'],
            'listings.*.images' => ['nullable', 'array'],
            'listings.*.contact' => ['nullable', 'array'],
            'listings.*.rooms' => ['nullable', 'integer'],
            'listings.*.area' => ['nullable', 'integer'],
            'listings.*.address' => ['nullable', 'string'],
            'listings.*.region_hint' => ['nullable', 'string'],
            'listings.*.posted_at' => ['nullable', 'string'],
        ]);

        $source = Source::where('slug', $data['source'])->firstOrFail();

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($data['listings'] as $raw) {
            $externalId = $raw['external_id'] ?? null;
            if ($externalId === null || $externalId === '') {
                $skipped++;

                continue;
            }

            $contact = $raw['contact'] ?? null;
            $owner = $this->resolveOwner($contact);

            // The parser knows the region authoritatively (e.g. the OLX list URL
            // or the birbir region filter), so resolve it here. The analyzer
            // preserves a pre-set region_id when it can't infer a better one.
            $region = $this->resolveRegion($raw['region_hint'] ?? null);

            $attributes = array_filter([
                'title' => $raw['title'] ?? null,
                'description' => $raw['description'] ?? null,
                'url' => $raw['url'] ?? null,
                'price' => $raw['price'] ?? null,
                'currency' => $raw['currency'] ?? 'USD',
                'images' => $raw['images'] ?? null,
                'contact' => $contact,
                'rooms' => $raw['rooms'] ?? null,
                'area' => $raw['area'] ?? null,
                'address' => $raw['address'] ?? null,
                'region_id' => $region?->id,
                'posted_at' => isset($raw['posted_at']) ? $this->parseDate($raw['posted_at']) : null,
                'listing_owner_id' => $owner?->id,
            ], fn ($v) => $v !== null);

            $listing = Listing::firstOrNew([
                'source_id' => $source->id,
                'external_id' => (string) $externalId,
            ]);

            $existed = $listing->exists;
            $listing->fill($attributes);
            $listing->save();

            $existed ? $updated++ : $created++;

            AnalyzeListingJob::dispatch($listing);
        }

        return response()->json([
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ]);
    }

    /** Resolve a free-text region hint ("Toshkent", "Farg'ona", ...) to a Region. */
    private function resolveRegion(?string $hint): ?Region
    {
        $hint = trim((string) $hint);
        if ($hint === '') {
            return null;
        }

        $lower = mb_strtolower($hint);
        $regions = Region::all();

        // Exact match on slug or any localized name.
        foreach ($regions as $region) {
            foreach ([$region->slug, $region->name_uz, $region->name_ru, $region->name_en] as $name) {
                if ($name && mb_strtolower((string) $name) === $lower) {
                    return $region;
                }
            }
        }

        // Bare "Toshkent" / "Ташкент" / "Tashkent" => the city.
        if (in_array($lower, ['toshkent', 'ташкент', 'tashkent'], true)) {
            return $regions->firstWhere('slug', 'toshkent-shahri');
        }

        // Substring match either direction.
        foreach ($regions as $region) {
            foreach ([$region->name_uz, $region->name_ru, $region->name_en] as $name) {
                $n = mb_strtolower((string) $name);
                if ($n && (str_contains($lower, $n) || str_contains($n, $lower))) {
                    return $region;
                }
            }
        }

        return null;
    }

    private function resolveOwner(?array $contact): ?ListingOwner
    {
        if (! $contact) {
            return null;
        }

        $telegram = $contact['telegram'] ?? null;
        $phone = $contact['phone'] ?? null;

        if (! $telegram && ! $phone) {
            return null;
        }

        $key = [];
        if ($telegram) {
            $key['telegram_username'] = ltrim((string) $telegram, '@');
        } elseif ($phone) {
            $key['phone'] = (string) $phone;
        }

        return ListingOwner::firstOrCreate($key, array_filter([
            'phone' => $phone ? (string) $phone : null,
            'telegram_username' => $telegram ? ltrim((string) $telegram, '@') : null,
        ], fn ($v) => $v !== null));
    }

    private function parseDate(string $value): ?string
    {
        try {
            return \Illuminate\Support\Carbon::parse($value)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }
}
