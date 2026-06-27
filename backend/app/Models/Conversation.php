<?php

namespace App\Models;

use App\Enums\ConversationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'search_request_id', 'listing_id', 'listing_owner_id', 'telegram_account_id',
        'channel', 'status', 'is_simulation', 'outcome', 'summary',
        'contacted_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ConversationStatus::class,
            'is_simulation' => 'boolean',
            'contacted_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function searchRequest(): BelongsTo
    {
        return $this->belongsTo(SearchRequest::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(ListingOwner::class, 'listing_owner_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(TelegramAccount::class, 'telegram_account_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('sent_at')->orderBy('id');
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(SearchMatch::class, 'id', 'conversation_id');
    }
}
