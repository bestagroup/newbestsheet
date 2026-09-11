<?php

namespace App\Http\Requests\Panel;

use App\Support\LocalizedInputNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvesteeCompanyProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['registration_number', 'national_id', 'economic_code', 'tel', 'postal_code'] as $field) {
            if ($this->exists($field)) {
                $normalized[$field] = LocalizedInputNormalizer::digits(
                    is_scalar($this->input($field)) ? (string) $this->input($field) : null
                );
            }
        }

        if ($this->exists('registration_date')) {
            $normalized['registration_date'] = LocalizedInputNormalizer::date(
                is_scalar($this->input('registration_date')) ? (string) $this->input('registration_date') : null
            );
        }

        $this->merge($normalized);
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'registration_date' => ['nullable', 'date_format:Y-m-d'],
            'national_id' => ['nullable', 'string', 'max:255'],
            'economic_code' => ['nullable', 'string', 'max:255'],
            'legal_type' => ['nullable', 'string', 'max:255'],
            'tel' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'state' => ['nullable', 'integer', 'exists:states,id'],
            'city' => [
                'nullable',
                'integer',
                Rule::exists('cities', 'id')->where(fn ($query) => $query->where('state_id', $this->input('state'))),
            ],
            'address' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
