<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Condition: string implements HasLabel, HasColor
{
    case Average = 'average';
    case Excellent = 'excellent';
    case Any = 'any';

    public function getLabel(): string
    {
        return __('crm.enums.condition.'.$this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Average => 'warning',
            self::Excellent => 'success',
            self::Any => 'gray',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Average => '👍',
            self::Excellent => '✨',
            self::Any => '🤷',
        };
    }
}
