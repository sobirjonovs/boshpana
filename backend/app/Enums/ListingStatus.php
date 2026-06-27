<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ListingStatus: string implements HasLabel, HasColor
{
    case Active = 'active';
    case Rented = 'rented';
    case Expired = 'expired';
    case Hidden = 'hidden';

    public function getLabel(): string
    {
        return __('crm.enums.listing_status.'.$this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Active => 'success',
            self::Rented => 'warning',
            self::Expired => 'gray',
            self::Hidden => 'danger',
        };
    }
}
