<?php

namespace App\Http\Resources\Api\V1;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SubscriptionPlan */
class SubscriptionPlanResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price_cents' => $this->price_cents,
            'duration_days' => $this->duration_days,
            'max_accesses' => $this->max_accesses,
            'is_active' => $this->is_active,
        ];
    }
}
