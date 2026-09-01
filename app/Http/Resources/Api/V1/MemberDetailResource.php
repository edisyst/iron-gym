<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Member */
class MemberDetailResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $hasMedicalRead = $request->user()?->tokenCan('members:medical-read');

        $data = [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'city' => $this->city,
            'height_cm' => $this->height_cm,
            'is_active' => $this->is_active,
            'medical_cert_valid' => $this->has_medical_cert_valid,
            'active_subscription' => $this->whenLoaded('activeSubscription', function () {
                $sub = $this->activeSubscription;
                if ($sub === null) {
                    return null;
                }

                return [
                    'id' => $sub->id,
                    'plan_name' => $sub->plan->name ?? null,
                    'status' => $sub->status,
                    'expires_at' => $sub->expires_at?->toDateString(),
                    'accesses_remaining' => $sub->accesses_remaining,
                ];
            }),
        ];

        if ($hasMedicalRead) {
            $data['medical_cert_expiry'] = $this->medical_cert_expiry?->toDateString();
        }

        return $data;
    }
}
