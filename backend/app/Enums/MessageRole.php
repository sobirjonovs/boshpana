<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MessageRole: string implements HasLabel
{
    case Ai = 'ai';
    case Owner = 'owner';
    case System = 'system';

    public function getLabel(): string
    {
        return __('crm.enums.message_role.'.$this->value);
    }
}
