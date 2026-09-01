<?php

namespace App\Http\Resources\Api\V1;

use App\Models\AccessLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AccessLog */
class AccessLogResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member_id' => $this->member_id,
            'member_name' => $this->whenLoaded('member', fn () => $this->member->first_name.' '.$this->member->last_name),
            'subscription_id' => $this->subscription_id,
            'checked_in_at' => $this->checked_in_at->toIso8601ZuluString(),
            'note' => $this->note,
        ];
    }
}
