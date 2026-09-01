<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Subscription */
class SubscriptionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plan_id' => $this->plan_id,
            'plan_name' => $this->plan->name ?? null,
            'status' => $this->status,
            'started_at' => $this->started_at?->toDateString(),
            'expires_at' => $this->expires_at?->toDateString(),
            'accesses_used' => $this->accesses_used,
            'accesses_remaining' => $this->accesses_remaining,
            'notes' => $this->notes,
        ];
    }
}
