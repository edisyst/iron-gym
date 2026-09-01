<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Member */
class MemberListResource extends JsonResource
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
            'is_active' => $this->is_active,
            'medical_cert_valid' => $this->has_medical_cert_valid,
        ];

        if ($hasMedicalRead) {
            $data['medical_cert_expiry'] = $this->medical_cert_expiry?->toDateString();
        }

        return $data;
    }
}
