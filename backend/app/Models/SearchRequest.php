<?php

namespace App\Models;

use App\Enums\Condition;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\SearchMode;
use App\Enums\SearchStatus;
use App\Enums\TriState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SearchRequest extends Model
{
    protected $fillable = [
        'telegram_user_id', 'region_id', 'district_id',
        'price_min', 'price_max', 'currency', 'rooms', 'condition',
        'has_furniture', 'has_commission', 'area_min', 'area_max',
        'mode', 'partners_count', 'near_metro', 'gender', 'marital_status', 'free_text',
        'status', 'is_simulation', 'current_step',
        'progress', 'scanned_count', 'matched_count', 'contacted_count', 'agreed_count',
        'started_at', 'completed_at', 'last_progress_at',
    ];

    protected function casts(): array
    {
        return [
            'rooms' => 'array',
            'price_min' => 'integer',
            'price_max' => 'integer',
            'area_min' => 'integer',
            'area_max' => 'integer',
            'partners_count' => 'integer',
            'condition' => Condition::class,
            'has_furniture' => TriState::class,
            'has_commission' => TriState::class,
            'near_metro' => TriState::class,
            'gender' => Gender::class,
            'marital_status' => MaritalStatus::class,
            'mode' => SearchMode::class,
            'status' => SearchStatus::class,
            'is_simulation' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_progress_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class, 'telegram_user_id');
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

    public function agreedMatches(): HasMany
    {
        return $this->matches()->where('status', \App\Enums\MatchStatus::Agreed->value);
    }

    public function priceLabel(): string
    {
        return match (true) {
            $this->price_min && $this->price_max => "{$this->price_min}-{$this->price_max}$",
            (bool) $this->price_max => "≤{$this->price_max}$",
            (bool) $this->price_min => "≥{$this->price_min}$",
            default => '—',
        };
    }

    public function areaLabel(): string
    {
        return match (true) {
            $this->area_min && $this->area_max => "{$this->area_min}-{$this->area_max} m²",
            (bool) $this->area_max => "≤{$this->area_max} m²",
            (bool) $this->area_min => "≥{$this->area_min} m²",
            default => '—',
        };
    }
}
