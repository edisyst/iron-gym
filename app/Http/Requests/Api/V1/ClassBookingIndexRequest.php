<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ClassBookingIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'member_id' => 'sometimes|integer|min:1',
            'occurrence_id' => 'sometimes|integer|min:1',
            'status' => 'sometimes|string|in:confirmed,waitlisted,cancelled_by_athlete,cancelled_by_gym,no_show',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }
}
