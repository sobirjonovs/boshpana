<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum SearchStatus: string implements HasLabel, HasColor, HasIcon
{
    case Draft = 'draft';
    case Queued = 'queued';
    case Searching = 'searching';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return __('crm.enums.search_status.'.$this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Queued => 'warning',
            self::Searching => 'info',
            self::Completed => 'success',
            self::Cancelled => 'gray',
            self::Failed => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-pencil',
            self::Queued => 'heroicon-o-clock',
            self::Searching => 'heroicon-o-magnifying-glass',
            self::Completed => 'heroicon-o-check-circle',
            self::Cancelled => 'heroicon-o-x-circle',
            self::Failed => 'heroicon-o-exclamation-triangle',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Queued, self::Searching], true);
    }
}
