<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'telegram_user_id' => $this->telegram_user_id,
            'plan_id' => $this->plan_id,
            'plan' => $this->whenLoaded('plan', fn () => $this->plan
                ? (new PlanResource($this->plan))->toArray($request)
                : null),
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'provider' => $this->provider?->value,
            'provider_label' => $this->provider?->getLabel(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->getLabel(),
            'external_id' => $this->external_id,
            'description' => $this->description,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
