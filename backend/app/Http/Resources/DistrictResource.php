<?php

namespace App\Http\Resources;

use App\Models\District;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin District
 */
class DistrictResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->query('lang') ?: app()->getLocale();

        return [
            'id' => $this->id,
            'region_id' => $this->region_id,
            'slug' => $this->slug,
            'name' => $this->name($lang),
            'has_metro' => (bool) $this->has_metro,
        ];
    }
}
