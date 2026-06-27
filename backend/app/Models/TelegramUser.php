<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\Language;
use App\Enums\MaritalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramUser extends Model
{
    protected $fillable = [
        'telegram_id', 'username', 'first_name', 'last_name', 'language', 'phone',
        'gender', 'marital_status', 'is_premium', 'is_blocked', 'balance',
        'free_searches_left', 'premium_until', 'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'telegram_id' => 'integer',
            'language' => Language::class,
            'gender' => Gender::class,
            'marital_status' => MaritalStatus::class,
            'is_premium' => 'boolean',
            'is_blocked' => 'boolean',
            'balance' => 'decimal:2',
            'premium_until' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function searchRequests(): HasMany
    {
        return $this->hasMany(SearchRequest::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function fullName(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? '')) ?: ($this->username ?? "User #{$this->telegram_id}");
    }

    public function hasActivePremium(): bool
    {
        return $this->is_premium && (! $this->premium_until || $this->premium_until->isFuture());
    }

    public function canSearch(): bool
    {
        return $this->hasActivePremium() || $this->free_searches_left > 0;
    }
}
