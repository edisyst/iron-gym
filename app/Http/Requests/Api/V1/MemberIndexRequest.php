<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class MemberIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => 'sometimes|string|max:100',
            'is_active' => 'sometimes|boolean',
            'cert_expiry_before' => 'sometimes|date_format:Y-m-d',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if ($this->has('cert_expiry_before') && ! $this->user()?->tokenCan('members:medical-read')) {
                $v->errors()->add('cert_expiry_before', 'Ability members:medical-read richiesta per filtrare per scadenza certificato.');
            }
        });
    }
}
