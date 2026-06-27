<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;

class District extends Model
{
    protected $fillable = ['region_id', 'slug', 'name_uz', 'name_ru', 'name_en', 'has_metro', 'sort'];

    protected function casts(): array
    {
        return ['has_metro' => 'boolean'];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function name(?string $locale = null): string
    {
        $locale = $locale ?: App::getLocale();

        return $this->{'name_'.$locale} ?? $this->name_uz;
    }
}
