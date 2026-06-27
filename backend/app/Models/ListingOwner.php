<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ListingOwner extends Model
{
    protected $fillable = [
        'name', 'telegram_username', 'telegram_id', 'phone', 'is_realtor', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'telegram_id' => 'integer',
            'is_realtor' => 'boolean',
        ];
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
