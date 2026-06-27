<?php

namespace App\Http\Resources;

use App\Models\SearchRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SearchRequest
 */
class SearchRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->query('lang') ?: app()->getLocale();

        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'status_label' => $this->status?->getLabel(),
            'is_simulation' => (bool) $this->is_simulation,
            'current_step' => $this->current_step,

            // Criteria
            'region' => $this->region ? [
                'id' => $this->region->id,
                'name' => $this->region->name($lang),
            ] : null,
            'district' => $this->district ? [
                'id' => $this->district->id,
                'name' => $this->district->name($lang),
            ] : null,
            'price_min' => $this->price_min,
            'price_max' => $this->price_max,
            'currency' => $this->currency,
            'rooms' => $this->rooms,
            'condition' => $this->condition?->value,
            'has_furniture' => $this->has_furniture?->value,
            'has_commission' => $this->has_commission?->value,
            'area_min' => $this->area_min,
            'area_max' => $this->area_max,
            'mode' => $this->mode?->value,
            'partners_count' => $this->partners_count,
            'near_metro' => $this->near_metro?->value,
            'gender' => $this->gender?->value,
            'marital_status' => $this->marital_status?->value,
            'free_text' => $this->free_text,

            // Progress
            'progress' => (int) $this->progress,
            'scanned_count' => (int) $this->scanned_count,
            'matched_count' => (int) $this->matched_count,
            'contacted_count' => (int) $this->contacted_count,
            'agreed_count' => (int) $this->agreed_count,

            'summary' => $this->summary(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /** Human-readable multiline summary of the chosen criteria (Uzbek). */
    private function summary(): string
    {
        $lines = [];

        if ($this->region) {
            $place = $this->region->name('uz');
            if ($this->district) {
                $place .= ', '.$this->district->name('uz');
            }
            $lines[] = "📍 Hudud: {$place}";
        }

        if ($this->price_min || $this->price_max) {
            $lines[] = '💰 Narx: '.$this->priceLabel();
        }

        if (! empty($this->rooms)) {
            $lines[] = '🚪 Xonalar: '.implode(', ', $this->rooms);
        }

        if ($this->condition) {
            $lines[] = '🏠 Holati: '.$this->condition->getLabel();
        }

        if ($this->has_furniture) {
            $lines[] = '🛋 Mebel: '.$this->has_furniture->getLabel();
        }

        if ($this->has_commission) {
            $lines[] = '🤝 Maklerlik: '.$this->has_commission->getLabel();
        }

        if ($this->area_min || $this->area_max) {
            $lines[] = '📐 Maydon: '.$this->areaLabel();
        }

        if ($this->mode) {
            $mode = $this->mode->getLabel();
            if ($this->partners_count) {
                $mode .= " ({$this->partners_count} kishi)";
            }
            $lines[] = '👥 Rejim: '.$mode;
        }

        if ($this->near_metro) {
            $lines[] = '🚇 Metro yaqinida: '.$this->near_metro->getLabel();
        }

        if ($this->gender) {
            $lines[] = '🚻 Jinsi: '.$this->gender->getLabel();
        }

        if ($this->marital_status) {
            $lines[] = '💍 Oilaviy holati: '.$this->marital_status->getLabel();
        }

        if ($this->free_text) {
            $lines[] = '📝 Izoh: '.$this->free_text;
        }

        return $lines === [] ? 'Mezonlar hali tanlanmagan.' : implode("\n", $lines);
    }
}
