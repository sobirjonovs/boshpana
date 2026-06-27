<?php

namespace App\Http\Resources;

use App\Models\TelegramUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TelegramUser
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'telegram_id' => $this->telegram_id,
            'username' => $this->username,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'language' => $this->language?->value,
            'phone' => $this->phone,
            'gender' => $this->gender?->value,
            'marital_status' => $this->marital_status?->value,
            'is_premium' => (bool) $this->is_premium,
            'balance' => (float) $this->balance,
            'free_searches_left' => (int) $this->free_searches_left,
            'can_search' => $this->canSearch(),
        ];
    }
}
