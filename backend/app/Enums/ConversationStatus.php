<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ConversationStatus: string implements HasLabel, HasColor
{
    case Pending = 'pending';
    case Contacted = 'contacted';
    case Replied = 'replied';
    case Agreed = 'agreed';
    case Declined = 'declined';
    case NoResponse = 'no_response';

    public function getLabel(): string
    {
        return __('crm.enums.conversation_status.'.$this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Contacted => 'info',
            self::Replied => 'warning',
            self::Agreed => 'success',
            self::Declined => 'danger',
            self::NoResponse => 'gray',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Agreed, self::Declined, self::NoResponse], true);
    }
}
