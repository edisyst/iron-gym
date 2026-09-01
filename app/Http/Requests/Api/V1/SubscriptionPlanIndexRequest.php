<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SubscriptionPlanIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'active' => 'sometimes|boolean',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }
}
