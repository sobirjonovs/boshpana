<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SourceType: string implements HasLabel, HasColor
{
    case Marketplace = 'marketplace';
    case TelegramChannel = 'telegram_channel';
    case TelegramGroup = 'telegram_group';

    public function getLabel(): string
    {
        return __('crm.enums.source_type.'.$this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Marketplace => 'info',
            self::TelegramChannel => 'primary',
            self::TelegramGroup => 'success',
        };
    }
}
