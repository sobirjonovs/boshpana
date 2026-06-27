<?php

namespace App\Models;

use App\Enums\MatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchMatch extends Model
{
    protected $table = 'search_matches';

    protected $fillable = [
        'search_request_id', 'listing_id', 'conversation_id',
        'score', 'score_breakdown', 'status', 'reason', 'notified', 'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'score_breakdown' => 'array',
            'status' => MatchStatus::class,
            'notified' => 'boolean',
            'notified_at' => 'datetime',
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

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
