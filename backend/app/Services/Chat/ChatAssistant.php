<?php

namespace App\Services\Chat;

use App\Models\District;
use App\Models\Region;
use App\Services\Ai\AiClient;
use Illuminate\Support\Facades\Cache;

/**
 * Conversational front-end brain for the website chat (Gemini-style).
 *
 * Turns a free-text (or transcribed voice) apartment request into structured
 * search criteria via DeepSeek. When important details are missing it replies
 * with a short question plus quick-reply OPTIONS (rendered as inline chips in
 * the web chat). Once it has enough (at least a region + price/rooms, or the
 * user says "search now") it sets ready=true and the controller runs the search.
 */
class ChatAssistant
{
    public function __construct(private readonly AiClient $ai)
    {
    }

    /**
     * @param  array  $history   [['role'=>'user'|'ai','content'=>string], ...]
     * @param  array  $criteria  accumulated criteria so far
     * @return array{reply:string, options:array<int,array{label:string,value:string}>, ready:bool, criteria:array}
     */
    public function respond(array $history, array $criteria, string $lang = 'uz'): array
    {
        if (! $this->ai->enabled()) {
            return $this->fallback($history, $criteria, $lang);
        }

        $messages = [];
        foreach ($history as $m) {
            $messages[] = [
                'role' => ($m['role'] ?? 'user') === 'ai' ? 'assistant' : 'user',
                'content' => (string) ($m['content'] ?? ''),
            ];
        }
        if (empty($messages)) {
            $messages[] = ['role' => 'user', 'content' => '[boshlanish]'];
        }

        $data = null;
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                $data = $this->ai->structured($messages, $this->schema(), $this->system($criteria, $lang));
                break;
            } catch (\Throwable $e) {
                // transient DeepSeek error (e.g. empty/non-JSON) — retry, then fall back
            }
        }
        if ($data === null) {
            return $this->fallback($history, $criteria, $lang);
        }

        $merged = $this->merge($criteria, $data['criteria'] ?? []);

        return [
            'reply' => trim((string) ($data['reply'] ?? '')) ?: $this->defaultReply($lang),
            'options' => $this->normalizeOptions($data['options'] ?? []),
            'ready' => (bool) ($data['ready'] ?? false),
            'criteria' => $merged,
        ];
    }

    // ------------------------------------------------------------------ prompt

    private function system(array $criteria, string $lang): string
    {
        $regions = Cache::remember('chat.regions', 600, fn () => Region::orderBy('sort')->get()
            ->map(fn ($r) => $r->id.'='.$r->name_uz)->implode(', '));
        $districts = Cache::remember('chat.tashkent_districts', 600, function () {
            $tashkent = Region::where('slug', 'toshkent-shahri')->first();

            return $tashkent
                ? District::where('region_id', $tashkent->id)->orderBy('sort')->get()
                    ->map(fn ($d) => $d->id.'='.$d->name_uz)->implode(', ')
                : '';
        });

        $known = json_encode($criteria, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';

        return <<<TXT
You are the Boshpana.ai assistant — a friendly AI that helps young people rent an
apartment in Tashkent, Uzbekistan. Reply in the user's language (detected from
their messages; default "{$lang}"). Keep replies short, warm and natural — like a
helpful human, NOT a form.

Your job: from the conversation, fill these search criteria, then run the search.
Currency is US dollars (integer). Regions (id=name): {$regions}.
Tashkent districts (id=name): {$districts}.

Criteria already known (merge, don't lose them): {$known}

RULES
- Each turn, return JSON per the schema: a short "reply", a "criteria" object with
  everything you now know (keep prior values unless the user changes them), a list
  of 0–6 "options" (quick-reply chips: each {label, value} where value is the text
  to send if tapped), and "ready".
- Ask for the MOST important missing detail first: region, then budget (price in $),
  then number of rooms. Offer options to make it one-tap (e.g. district chips, price
  ranges like "300-500$", room counts "1","2","3", "Farqi yo'q").
- Only ONE question per turn. Don't re-ask things you already know.
- Set "ready": true ONLY when you have at least a region AND (a price OR rooms), OR
  the user explicitly asks to search now. When ready, "reply" should say you're
  starting the search, and "options" should be empty.
- condition: average|excellent|any. has_furniture/has_commission/near_metro:
  yes|no|any. mode: solo|partnership. gender: male|female|any.
  marital_status: single|married|any. rooms is an array of ints (1..5).
- Map neighbourhoods to the district ids above; use region id for the city.
TXT;
    }

    private function schema(): array
    {
        $triState = ['type' => ['string', 'null'], 'enum' => ['yes', 'no', 'any', null]];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'reply' => ['type' => 'string'],
                'ready' => ['type' => 'boolean'],
                'options' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'label' => ['type' => 'string'],
                            'value' => ['type' => 'string'],
                        ],
                        'required' => ['label', 'value'],
                    ],
                ],
                'criteria' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'region_id' => ['type' => ['integer', 'null']],
                        'district_id' => ['type' => ['integer', 'null']],
                        'price_min' => ['type' => ['integer', 'null']],
                        'price_max' => ['type' => ['integer', 'null']],
                        'rooms' => ['type' => ['array', 'null'], 'items' => ['type' => 'integer']],
                        'condition' => ['type' => ['string', 'null'], 'enum' => ['average', 'excellent', 'any', null]],
                        'has_furniture' => $triState,
                        'has_commission' => $triState,
                        'area_min' => ['type' => ['integer', 'null']],
                        'area_max' => ['type' => ['integer', 'null']],
                        'mode' => ['type' => ['string', 'null'], 'enum' => ['solo', 'partnership', null]],
                        'partners_count' => ['type' => ['integer', 'null']],
                        'near_metro' => $triState,
                        'gender' => ['type' => ['string', 'null'], 'enum' => ['male', 'female', 'any', null]],
                        'marital_status' => ['type' => ['string', 'null'], 'enum' => ['single', 'married', 'any', null]],
                    ],
                    'required' => ['region_id', 'price_min', 'price_max', 'rooms'],
                ],
            ],
            'required' => ['reply', 'ready', 'options', 'criteria'],
        ];
    }

    // ----------------------------------------------------------------- helpers

    private function merge(array $old, array $new): array
    {
        $keys = [
            'region_id', 'district_id', 'price_min', 'price_max', 'rooms', 'condition',
            'has_furniture', 'has_commission', 'area_min', 'area_max', 'mode',
            'partners_count', 'near_metro', 'gender', 'marital_status',
        ];
        $out = $old;
        foreach ($keys as $k) {
            $v = $new[$k] ?? null;
            if ($v !== null && $v !== [] && $v !== '') {
                $out[$k] = $v;
            }
        }

        return $out;
    }

    private function normalizeOptions(array $options): array
    {
        $out = [];
        foreach (array_slice($options, 0, 6) as $o) {
            $label = trim((string) ($o['label'] ?? ''));
            $value = trim((string) ($o['value'] ?? $label));
            if ($label !== '') {
                $out[] = ['label' => $label, 'value' => $value ?: $label];
            }
        }

        return $out;
    }

    private function defaultReply(string $lang): string
    {
        return match ($lang) {
            'ru' => 'Расскажите, какую квартиру вы ищете?',
            'en' => 'Tell me what kind of apartment you are looking for.',
            default => 'Qanday kvartira izlayapsiz? Bemalol yozing yoki ovozli ayting.',
        };
    }

    /**
     * Resilient offline fallback (used when DeepSeek is down/flaky). It heuristically
     * reads region/price/rooms from the conversation, then asks for the NEXT missing
     * field with matching options — so it never loops the same question, and it can
     * still trigger the search once it knows enough.
     */
    private function fallback(array $history, array $criteria, string $lang): array
    {
        $criteria = $this->heuristicExtract($history, $criteria);
        $anyLabel = match ($lang) { 'ru' => 'Не важно', 'en' => 'Any', default => "Farqi yo'q" };

        if (empty($criteria['region_id'])) {
            $regions = Region::orderBy('sort')->take(6)->get()
                ->map(fn ($r) => ['label' => $r->name_uz, 'value' => $r->name_uz])->all();

            return $this->turn(match ($lang) {
                'ru' => 'В каком городе или регионе ищете квартиру?',
                'en' => 'Which city or region are you looking in?',
                default => 'Qaysi shahar yoki viloyatdan kvartira qidiryapsiz?',
            }, $regions, false, $criteria);
        }

        if (empty($criteria['price_min']) && empty($criteria['price_max'])) {
            return $this->turn(match ($lang) {
                'ru' => 'Какой бюджет в долларах?',
                'en' => 'What is your budget in dollars?',
                default => 'Byudjetingiz qancha (dollarda)?',
            }, [
                ['label' => '300-500$', 'value' => '300-500$'],
                ['label' => '500-800$', 'value' => '500-800$'],
                ['label' => '800-1200$', 'value' => '800-1200$'],
                ['label' => $anyLabel, 'value' => $anyLabel],
            ], false, $criteria);
        }

        if (empty($criteria['rooms'])) {
            return $this->turn(match ($lang) {
                'ru' => 'Сколько комнат?',
                'en' => 'How many rooms?',
                default => 'Necha xonali bo‘lsin?',
            }, [
                ['label' => match ($lang) { 'ru' => '1 комн.', 'en' => '1 room', default => '1 xona' }, 'value' => '1 xona'],
                ['label' => match ($lang) { 'ru' => '2 комн.', 'en' => '2 rooms', default => '2 xona' }, 'value' => '2 xona'],
                ['label' => match ($lang) { 'ru' => '3 комн.', 'en' => '3 rooms', default => '3 xona' }, 'value' => '3 xona'],
                ['label' => $anyLabel, 'value' => $anyLabel],
            ], false, $criteria);
        }

        // Enough to search.
        return $this->turn(match ($lang) {
            'ru' => 'Отлично, начинаю поиск!',
            'en' => 'Great, starting the search!',
            default => 'Yaxshi, qidiruvni boshladim!',
        }, [], true, $criteria);
    }

    /** @return array{reply:string,options:array,ready:bool,criteria:array} */
    private function turn(string $reply, array $options, bool $ready, array $criteria): array
    {
        return ['reply' => $reply, 'options' => $options, 'ready' => $ready, 'criteria' => $criteria];
    }

    /** Best-effort extraction of region/district/price/rooms from the chat text. */
    private function heuristicExtract(array $history, array $criteria): array
    {
        $allText = '';
        $lastUser = '';
        $lastAi = '';
        foreach ($history as $m) {
            $content = mb_strtolower((string) ($m['content'] ?? ''));
            if (($m['role'] ?? 'user') === 'ai') {
                $lastAi = $content;
            } else {
                $allText .= ' '.$content;
                $lastUser = $content;
            }
        }

        // Region / district (district names are most specific).
        if (empty($criteria['region_id'])) {
            $tashkent = Region::where('slug', 'toshkent-shahri')->first();
            if ($tashkent) {
                foreach (District::where('region_id', $tashkent->id)->get() as $d) {
                    $name = mb_strtolower($d->name_uz);
                    if ($name !== '' && str_contains($allText, $name)) {
                        $criteria['region_id'] = $tashkent->id;
                        $criteria['district_id'] = $d->id;
                        break;
                    }
                }
            }
            if (empty($criteria['region_id'])) {
                foreach (Region::all() as $r) {
                    $name = mb_strtolower($r->name_uz);
                    if ($name !== '' && str_contains($allText, $name)) {
                        $criteria['region_id'] = $r->id;
                        break;
                    }
                }
            }
            if (empty($criteria['region_id']) && $tashkent && str_contains($allText, 'toshkent')) {
                $criteria['region_id'] = $tashkent->id;
            }
        }

        $saysAny = (bool) preg_match('/farqi|любой|не важно|\bany\b/u', $lastUser);

        // Price.
        if (empty($criteria['price_min']) && empty($criteria['price_max'])) {
            if (preg_match('/(\d{2,5})\s*[-–—]\s*(\d{2,5})/u', $allText, $mm)) {
                $criteria['price_min'] = (int) $mm[1];
                $criteria['price_max'] = (int) $mm[2];
            } elseif (preg_match('/(\d{2,5})\s*(\$|dollar|доллар|usd)/u', $allText, $mm)) {
                $criteria['price_max'] = (int) $mm[1];
            } elseif ($saysAny && preg_match('/byudjet|budget|бюджет|dollar|доллар/u', $lastAi)) {
                $criteria['price_min'] = 0;
                $criteria['price_max'] = 100000;
            }
        }

        // Rooms.
        if (empty($criteria['rooms'])) {
            if (preg_match('/(\d)\s*[- ]?\s*(xona|honali|xonali|комнат|room)/u', $allText, $mm)) {
                $criteria['rooms'] = [(int) $mm[1]];
            } elseif ($saysAny && preg_match('/xona|комнат|room/u', $lastAi)) {
                $criteria['rooms'] = [1, 2, 3, 4];
            }
        }

        return $criteria;
    }
}
