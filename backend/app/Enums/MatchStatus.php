<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MatchStatus: string implements HasLabel, HasColor
{
    case Candidate = 'candidate';
    case Contacting = 'contacting';
    case Agreed = 'agreed';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return __('crm.enums.match_status.'.$this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Candidate => 'gray',
            self::Contacting => 'info',
            self::Agreed => 'success',
            self::Rejected => 'danger',
        };
    }
}
