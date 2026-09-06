<?php

namespace App\Http\Requests\Backoffice;

use Illuminate\Foundation\Http\FormRequest;

class MemberExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('gestore') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'certFilter' => ['sometimes', 'nullable', 'string', 'in:missing,expired,expiring_soon'],
        ];
    }

    public function search(): string
    {
        return $this->query('search', '');
    }

    public function certFilter(): string
    {
        return $this->query('certFilter', '');
    }
}
