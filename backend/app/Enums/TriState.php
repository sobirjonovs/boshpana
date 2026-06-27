<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * A yes / no / "doesn't matter" answer used by several search criteria
 * (furniture, commission/realtor, proximity to metro, ...).
 */
enum TriState: string implements HasLabel, HasColor
{
    case Yes = 'yes';
    case No = 'no';
    case Any = 'any';

    public function getLabel(): string
    {
        return __('crm.enums.tristate.'.$this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Yes => 'success',
            self::No => 'danger',
            self::Any => 'gray',
        };
    }

    public function toBool(): ?bool
    {
        return match ($this) {
            self::Yes => true,
            self::No => false,
            self::Any => null,
        };
    }
}
