<?php

namespace App\Http\Requests\Backoffice;

use Illuminate\Foundation\Http\FormRequest;

class SubscriptionExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('gestore') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'filter' => ['sometimes', 'string', 'in:all,active,expired,expiring,suspended'],
        ];
    }

    public function filter(): string
    {
        return $this->query('filter', 'all');
    }
}
