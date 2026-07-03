<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'operations' => ['required', 'array', 'min:1', 'max:200'],
            'operations.*.client_uuid' => ['required', 'string', 'size:36'],
            'operations.*.operation' => ['required', 'string', 'in:quick_log,complete_set,generate_warmup,delete_warmup'],
            'operations.*.client_timestamp' => ['required', 'integer', 'min:0'],
            'operations.*.payload' => ['required', 'array'],
            'operations.*.payload.set_id' => ['required_unless:operations.*.operation,generate_warmup', 'integer'],
            'operations.*.payload.session_exercise_id' => ['required_if:operations.*.operation,generate_warmup', 'integer'],
            'operations.*.payload.reps' => ['nullable', 'integer', 'min:0'],
            'operations.*.payload.weight' => ['nullable', 'numeric', 'min:0'],
            'operations.*.payload.rir' => ['nullable', 'integer', 'min:0', 'max:10'],
            'operations.*.payload.duration' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
