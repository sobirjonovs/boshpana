<?php

namespace App\Http\Resources;

use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Listing
 */
class ListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->query('lang') ?: app()->getLocale();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price,
            'currency' => $this->currency,
            'rooms' => $this->rooms,
            'area' => $this->area,
            'condition' => $this->condition?->value,
            'condition_label' => $this->condition?->getLabel(),
            'region' => $this->whenLoaded('region', fn () => $this->region ? [
                'id' => $this->region->id,
                'name' => $this->region->name($lang),
            ] : null),
            'district' => $this->whenLoaded('district', fn () => $this->district ? [
                'id' => $this->district->id,
                'name' => $this->district->name($lang),
            ] : null),
            'address' => $this->address,
            'near_metro' => (bool) $this->near_metro,
            'metro_station' => $this->metro_station,
            'url' => $this->url,
            'source' => $this->whenLoaded('source', fn () => $this->source ? [
                'name' => $this->source->name,
                'type' => $this->source->type?->value,
            ] : null),
            'images' => $this->images ?? [],
            'contact' => $this->contact ?? [],
        ];
    }
}
