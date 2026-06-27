<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SearchMode: string implements HasLabel, HasColor
{
    case Solo = 'solo';
    case Partnership = 'partnership';

    public function getLabel(): string
    {
        return __('crm.enums.search_mode.'.$this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Solo => 'info',
            self::Partnership => 'success',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Solo => '🙋',
            self::Partnership => '🤝',
        };
    }
}
