<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AccessLogIndexRequest extends FormRequest
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
            'date_from' => 'sometimes|date_format:Y-m-d',
            'date_to' => 'sometimes|date_format:Y-m-d|after_or_equal:date_from',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $from = $this->date('date_from') ?? now()->startOfDay();
            $to = $this->date('date_to') ?? $from;

            if ($to->diffInDays($from) > 31) {
                $v->errors()->add('date_to', 'Il range di date non può superare 31 giorni.');
            }
        });
    }
}
