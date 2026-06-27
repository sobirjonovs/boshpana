<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LeadType: string implements HasLabel, HasColor
{
    case Owner = 'owner';
    case Realtor = 'realtor';
    case Agency = 'agency';

    public function getLabel(): string
    {
        return __('crm.enums.lead_type.'.$this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Owner => 'info',
            self::Realtor => 'warning',
            self::Agency => 'success',
        };
    }
}
