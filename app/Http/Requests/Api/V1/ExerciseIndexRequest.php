<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ExerciseIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'muscle_slug' => 'sometimes|string|max:100',
            'equipment_slug' => 'sometimes|string|max:100',
            'measurement_type' => 'sometimes|string|in:reps_weight,reps_only,time,time_weight,isometric_hold',
            'mechanic' => 'sometimes|string|in:compound,isolation',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }
}
