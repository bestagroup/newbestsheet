<?php

namespace App\Http\Requests\Panel;

use App\Support\LocalizedInputNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['national_id', 'phone', 'postalcode'] as $field) {
            if ($this->exists($field)) {
                $normalized[$field] = LocalizedInputNormalizer::digits(
                    is_scalar($this->input($field)) ? (string) $this->input($field) : null
                );
            }
        }

        $this->merge($normalized);
    }

    public function rules(): array
    {
        $userId = $this->user()?->getKey();

        return [
            'name' => ['required', 'string', 'max:255'],
            'national_id' => ['nullable', 'string', 'max:10'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($userId)],
            'gender' => ['nullable', 'integer', Rule::in([1, 2])],
            'postalcode' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
