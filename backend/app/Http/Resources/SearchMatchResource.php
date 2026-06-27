<?php

namespace App\Http\Resources;

use App\Enums\MessageRole;
use App\Models\SearchMatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SearchMatch
 */
class SearchMatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'score' => (float) $this->score,
            'status' => $this->status?->value,
            'status_label' => $this->status?->getLabel(),
            'reason' => $this->reason,
            'notified' => (bool) $this->notified,
            'listing' => $this->listing
                ? (new ListingResource($this->listing))->toArray($request)
                : null,
            'owner_reply' => $this->ownerReply(),
        ];
    }

    private function ownerReply(): ?string
    {
        $conversation = $this->conversation;
        if (! $conversation) {
            return null;
        }

        if ($conversation->relationLoaded('messages')) {
            $reply = $conversation->messages
                ->where('role', MessageRole::Owner)
                ->last();

            if ($reply) {
                return $reply->content;
            }
        }

        return $conversation->summary;
    }
}
