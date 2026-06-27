<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentProvider: string implements HasLabel
{
    case Payme = 'payme';
    case Click = 'click';
    case Uzum = 'uzum';
    case Balance = 'balance';
    case Manual = 'manual';

    public function getLabel(): string
    {
        return __('crm.enums.payment_provider.'.$this->value);
    }
}
