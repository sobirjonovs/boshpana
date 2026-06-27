<?php

namespace App\Models;

use App\Enums\Condition;
use App\Enums\Gender;
use App\Enums\ListingStatus;
use App\Enums\MaritalStatus;
use App\Enums\SearchMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Listing extends Model
{
    protected $fillable = [
        'source_id', 'listing_owner_id', 'external_id', 'url', 'source_ref',
        'title', 'description', 'images', 'price', 'currency',
        'region_id', 'district_id', 'address', 'near_metro', 'metro_station', 'lat', 'lng',
        'rooms', 'area', 'floor', 'total_floors', 'condition', 'has_furniture', 'has_commission',
        'amenities', 'gender_pref', 'marital_pref', 'mode', 'partners_needed',
        'contact', 'posted_at', 'status',
        'ai_analyzed', 'ai_summary', 'ai_attributes', 'ai_confidence', 'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'amenities' => 'array',
            'contact' => 'array',
            'ai_attributes' => 'array',
            'price' => 'integer',
            'rooms' => 'integer',
            'area' => 'integer',
            'near_metro' => 'boolean',
            'has_furniture' => 'boolean',
            'has_commission' => 'boolean',
            'ai_analyzed' => 'boolean',
            'ai_confidence' => 'float',
            'lat' => 'float',
            'lng' => 'float',
            'condition' => Condition::class,
            'gender_pref' => Gender::class,
            'marital_pref' => MaritalStatus::class,
            'mode' => SearchMode::class,
            'status' => ListingStatus::class,
            'posted_at' => 'datetime',
            'analyzed_at' => 'datetime',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(ListingOwner::class, 'listing_owner_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(SearchMatch::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ListingStatus::Active->value);
    }

    public function scopeAnalyzed(Builder $query): Builder
    {
        return $query->where('ai_analyzed', true);
    }

    public function scopePostedSince(Builder $query, \DateTimeInterface $since): Builder
    {
        return $query->where('posted_at', '>=', $since);
    }
}
