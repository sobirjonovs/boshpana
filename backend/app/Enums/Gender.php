<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Gender: string implements HasLabel, HasColor
{
    case Male = 'male';
    case Female = 'female';
    case Any = 'any';

    public function getLabel(): string
    {
        return __('crm.enums.gender.'.$this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Male => 'info',
            self::Female => 'pink',
            self::Any => 'gray',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Male => '👨',
            self::Female => '👩',
            self::Any => '🚻',
        };
    }
}
