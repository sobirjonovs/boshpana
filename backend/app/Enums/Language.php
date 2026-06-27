<?php

namespace App\Enums;

enum Language: string
{
    case Uz = 'uz';
    case Ru = 'ru';
    case En = 'en';

    public function label(): string
    {
        return __('crm.enums.language.'.$this->value);
    }

    public function flag(): string
    {
        return match ($this) {
            self::Uz => '🇺🇿',
            self::Ru => '🇷🇺',
            self::En => '🇬🇧',
        };
    }
}
