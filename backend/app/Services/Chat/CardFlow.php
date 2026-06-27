<?php

namespace App\Services\Chat;

use App\Models\Region;
use Illuminate\Support\Facades\Cache;

/**
 * Deterministic, fully-localized guided card flow for the website chat.
 *
 * Instead of relying on the LLM to design widgets, the backend decides the NEXT
 * card (region → budget → household → rooms → must-haves → search) from the
 * criteria gathered so far, with all text pre-translated (uz/ru/en) so the chat
 * UI always matches the selected language. Free-text input still goes through
 * DeepSeek (ChatAssistant); both update the same criteria and the flow simply
 * skips any field already known.
 */
class CardFlow
{
    /** Ordered soft steps that don't map 1:1 to a required criterion. */
    private const SOFT_STEPS = ['household', 'musthaves'];

    /**
     * The next guided step.
     *
     * @return array{reply:string, card:?array, ready:bool}
     */
    public function next(array $c, string $lang): array
    {
        $steps = (array) ($c['_steps'] ?? []);

        if (empty($c['region_id'])) {
            return $this->step($this->t('region_q', $lang), $this->regionCard($lang));
        }
        if (empty($c['price_min']) && empty($c['price_max'])) {
            return $this->step($this->t('budget_q', $lang), $this->budgetCard($lang));
        }
        if (! in_array('household', $steps, true)) {
            return $this->step($this->t('household_q', $lang), $this->householdCard($lang));
        }
        if (empty($c['rooms'])) {
            return $this->step($this->t('rooms_q', $lang), $this->roomsCard($lang));
        }
        if (! in_array('musthaves', $steps, true)) {
            return $this->step($this->t('musthaves_q', $lang), $this->mustHavesCard($lang));
        }

        return ['reply' => $this->t('ready', $lang), 'card' => null, 'ready' => true];
    }

    /**
     * Apply a structured card submission directly to the criteria (no LLM).
     */
    public function apply(array $c, string $field, mixed $value): array
    {
        $steps = (array) ($c['_steps'] ?? []);

        switch ($field) {
            case 'region':
                $c['region_id'] = (int) $value ?: null;
                break;

            case 'budget':
                [$min, $max] = $this->parseBudget($value);
                $c['price_min'] = $min;
                $c['price_max'] = $max;
                break;

            case 'household':
                $occ = (int) ($value['occupants'] ?? 1);
                $c['mode'] = $occ > 1 ? 'partnership' : 'solo';
                $c['partners_count'] = $occ > 1 ? $occ : null;
                if (! empty($value['furnished'])) {
                    $c['has_furniture'] = 'yes';
                }
                $steps[] = 'household';
                break;

            case 'rooms':
                $c['rooms'] = $value === 'any' || $value === null
                    ? [1, 2, 3, 4]
                    : [(int) $value];
                break;

            case 'musthaves':
                foreach ((array) $value as $tag) {
                    match ($tag) {
                        'furnished' => $c['has_furniture'] = 'yes',
                        'near_metro' => $c['near_metro'] = 'yes',
                        'no_commission' => $c['has_commission'] = 'no',
                        'excellent' => $c['condition'] = 'excellent',
                        default => null,
                    };
                }
                $steps[] = 'musthaves';
                break;
        }

        $c['_steps'] = array_values(array_unique($steps));

        return $c;
    }

    // -------------------------------------------------------------- the cards

    private function regionCard(string $lang): array
    {
        $regions = Cache::remember('cardflow.regions', 600, fn () => Region::orderBy('sort')->get()
            ->map(fn ($r) => ['value' => (string) $r->id, 'label' => $r->name_uz])->all());

        return [
            'key' => 'region',
            'icon' => 'map-pin',
            'title' => $this->t('region_t', $lang),
            'select' => 'single',
            'choices' => $regions,
            'continueLabel' => null, // single-select chips submit on tap
            'allowSkip' => false,
        ];
    }

    private function budgetCard(string $lang): array
    {
        return [
            'key' => 'budget',
            'icon' => 'wallet',
            'title' => $this->t('budget_t', $lang),
            'select' => 'single',
            'choices' => [
                ['value' => '300-500', 'label' => '$300–500'],
                ['value' => '500-800', 'label' => '$500–800'],
                ['value' => '800-1200', 'label' => '$800–1200'],
                ['value' => '1200-2000', 'label' => '$1200+'],
                ['value' => 'any', 'label' => $this->t('any', $lang)],
            ],
            'continueLabel' => null,
            'allowSkip' => false,
        ];
    }

    private function householdCard(string $lang): array
    {
        return [
            'key' => 'household',
            'icon' => 'user',
            'title' => $this->t('household_t', $lang),
            'fields' => [
                [
                    'type' => 'counter',
                    'key' => 'occupants',
                    'label' => $this->t('occupants', $lang),
                    'sublabel' => $this->t('occupants_sub', $lang),
                    'min' => 1,
                    'max' => 8,
                    'value' => 1,
                ],
                [
                    'type' => 'toggle',
                    'key' => 'furnished',
                    'label' => $this->t('furnished', $lang),
                    'sublabel' => $this->t('furnished_sub', $lang),
                    'value' => false,
                ],
            ],
            'continueLabel' => $this->t('continue', $lang),
            'allowSkip' => false,
        ];
    }

    private function roomsCard(string $lang): array
    {
        return [
            'key' => 'rooms',
            'icon' => 'door',
            'title' => $this->t('rooms_t', $lang),
            'select' => 'single',
            'choices' => [
                ['value' => '1', 'label' => $this->t('room1', $lang)],
                ['value' => '2', 'label' => $this->t('room2', $lang)],
                ['value' => '3', 'label' => $this->t('room3', $lang)],
                ['value' => '4', 'label' => $this->t('room4', $lang)],
                ['value' => 'any', 'label' => $this->t('any', $lang)],
            ],
            'continueLabel' => null,
            'allowSkip' => false,
        ];
    }

    private function mustHavesCard(string $lang): array
    {
        return [
            'key' => 'musthaves',
            'icon' => 'sparkles',
            'title' => $this->t('musthaves_t', $lang),
            'subtitle' => $this->t('musthaves_sub', $lang),
            'select' => 'multi',
            'choices' => [
                ['value' => 'furnished', 'label' => $this->t('mh_furnished', $lang)],
                ['value' => 'near_metro', 'label' => $this->t('mh_metro', $lang)],
                ['value' => 'no_commission', 'label' => $this->t('mh_no_commission', $lang)],
                ['value' => 'excellent', 'label' => $this->t('mh_excellent', $lang)],
            ],
            'continueLabel' => $this->t('continue', $lang),
            'allowSkip' => true,
            'skipLabel' => $this->t('skip', $lang),
        ];
    }

    // ----------------------------------------------------------------- helpers

    private function step(string $reply, array $card): array
    {
        return ['reply' => $reply, 'card' => $card, 'ready' => false];
    }

    /** @return array{0:?int,1:?int} [min, max] */
    private function parseBudget(mixed $value): array
    {
        if ($value === 'any' || $value === null) {
            return [0, 100000];
        }
        if (is_string($value) && preg_match('/(\d+)\s*-\s*(\d+)/', $value, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }
        if (is_numeric($value)) {
            return [0, (int) $value];
        }

        return [null, null];
    }

    private function t(string $key, string $lang): string
    {
        $tr = self::TRANSLATIONS[$key] ?? [];

        return $tr[$lang] ?? $tr['uz'] ?? $key;
    }

    private const TRANSLATIONS = [
        'region_q' => [
            'uz' => 'Avvalo, qaysi shahar yoki viloyatdan qidiramiz?',
            'ru' => 'Для начала — в каком городе или регионе ищем?',
            'en' => 'First, which city or region should we search in?',
        ],
        'region_t' => ['uz' => 'Qaysi shahar/viloyat?', 'ru' => 'Какой город/регион?', 'en' => 'Which city / region?'],
        'budget_q' => [
            'uz' => 'Oylik byudjetingiz qancha?',
            'ru' => 'Какой у вас месячный бюджет?',
            'en' => 'What is your monthly budget?',
        ],
        'budget_t' => ['uz' => 'Oylik byudjet', 'ru' => 'Месячный бюджет', 'en' => 'Monthly budget'],
        'household_q' => [
            'uz' => 'Uyga kim ko‘chib o‘tadi?',
            'ru' => 'Кто будет проживать?',
            'en' => 'Who’s moving in?',
        ],
        'household_t' => ['uz' => 'Kim ko‘chib o‘tadi?', 'ru' => 'Кто заселяется?', 'en' => 'Who’s moving in?'],
        'occupants' => ['uz' => 'Yashovchilar', 'ru' => 'Жильцы', 'en' => 'Occupants'],
        'occupants_sub' => [
            'uz' => 'Nechta kishi yashaydi',
            'ru' => 'Сколько человек будет жить',
            'en' => 'People who will live there',
        ],
        'furnished' => ['uz' => 'Mebel', 'ru' => 'Мебель', 'en' => 'Furniture'],
        'furnished_sub' => [
            'uz' => 'Mebelli bo‘lsinmi?',
            'ru' => 'Нужна меблировка?',
            'en' => 'Furnished apartment?',
        ],
        'rooms_q' => ['uz' => 'Necha xonali bo‘lsin?', 'ru' => 'Сколько комнат?', 'en' => 'How many rooms?'],
        'rooms_t' => ['uz' => 'Xonalar soni', 'ru' => 'Количество комнат', 'en' => 'Number of rooms'],
        'room1' => ['uz' => '1 xona', 'ru' => '1 комната', 'en' => '1 room'],
        'room2' => ['uz' => '2 xona', 'ru' => '2 комнаты', 'en' => '2 rooms'],
        'room3' => ['uz' => '3 xona', 'ru' => '3 комнаты', 'en' => '3 rooms'],
        'room4' => ['uz' => '4+ xona', 'ru' => '4+ комнаты', 'en' => '4+ rooms'],
        'musthaves_q' => [
            'uz' => 'Qo‘shimcha talablar bormi?',
            'ru' => 'Есть обязательные условия?',
            'en' => 'Any must-haves?',
        ],
        'musthaves_t' => ['uz' => 'Qo‘shimcha talablar', 'ru' => 'Обязательные условия', 'en' => 'Any must-haves?'],
        'musthaves_sub' => [
            'uz' => 'Eng mos variantlarni tanlash uchun belgilang. Chatda ham yozishingiz mumkin.',
            'ru' => 'Отметьте, чтобы подобрать лучшее. Можно также написать в чате.',
            'en' => 'Select any to help me rank your best matches. You can also type in chat.',
        ],
        'mh_furnished' => ['uz' => 'Mebelli', 'ru' => 'С мебелью', 'en' => 'furnished'],
        'mh_metro' => ['uz' => 'Metroga yaqin', 'ru' => 'Рядом метро', 'en' => 'near metro'],
        'mh_no_commission' => ['uz' => 'Komissiyasiz', 'ru' => 'Без комиссии', 'en' => 'no commission'],
        'mh_excellent' => ['uz' => 'A’lo holatda', 'ru' => 'Отличное состояние', 'en' => 'excellent condition'],
        'continue' => ['uz' => 'Davom etish', 'ru' => 'Продолжить', 'en' => 'Continue'],
        'skip' => ['uz' => 'O‘tkazib yuborish', 'ru' => 'Пропустить', 'en' => 'Skip'],
        'any' => ['uz' => 'Farqi yo‘q', 'ru' => 'Не важно', 'en' => 'Any'],
        'ready' => [
            'uz' => 'Ajoyib! Endi uy egalari bilan bog‘lanib, sizga mos variantlarni qidiraman.',
            'ru' => 'Отлично! Сейчас свяжусь с владельцами и подберу варианты.',
            'en' => 'Great! I’ll now contact owners and find your best matches.',
        ],
    ];
}
