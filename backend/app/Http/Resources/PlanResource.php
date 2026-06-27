<?php

namespace App\Http\Resources;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Plan
 */
class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->query('lang') ?: app()->getLocale();

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name($lang),
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'period_days' => $this->period_days,
            'searches_limit' => $this->searches_limit,
            'features' => $this->features ?? [],
            'is_active' => (bool) $this->is_active,
        ];
    }
}
