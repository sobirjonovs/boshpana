<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramAccount extends Model
{
    protected $fillable = [
        'label', 'phone', 'username', 'session', 'is_active', 'is_simulation',
        'daily_limit', 'sent_today', 'last_used_at',
    ];

    protected $hidden = ['session'];

    protected function casts(): array
    {
        return [
            'session' => 'encrypted',
            'is_active' => 'boolean',
            'is_simulation' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function hasQuota(): bool
    {
        return $this->sent_today < $this->daily_limit;
    }
}
