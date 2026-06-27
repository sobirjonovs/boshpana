<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;

class Region extends Model
{
    protected $fillable = ['slug', 'name_uz', 'name_ru', 'name_en', 'sort'];

    public function districts(): HasMany
    {
        return $this->hasMany(District::class)->orderBy('sort');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    /** Localised name for the current (or given) locale. */
    public function name(?string $locale = null): string
    {
        $locale = $locale ?: App::getLocale();

        return $this->{'name_'.$locale} ?? $this->name_uz;
    }
}
