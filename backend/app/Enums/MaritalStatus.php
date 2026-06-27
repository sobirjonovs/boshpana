<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MaritalStatus: string implements HasLabel, HasColor
{
    case Single = 'single';
    case Married = 'married';
    case Any = 'any';

    public function getLabel(): string
    {
        return __('crm.enums.marital_status.'.$this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Single => 'info',
            self::Married => 'success',
            self::Any => 'gray',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Single => '🧑',
            self::Married => '💍',
            self::Any => '👥',
        };
    }
}
