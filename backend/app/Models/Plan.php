<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;

class Plan extends Model
{
    protected $fillable = [
        'slug', 'name_uz', 'name_ru', 'name_en', 'price', 'currency',
        'period_days', 'searches_limit', 'features', 'is_active', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function name(?string $locale = null): string
    {
        $locale = $locale ?: App::getLocale();

        return $this->{'name_'.$locale} ?? $this->name_uz;
    }
}
