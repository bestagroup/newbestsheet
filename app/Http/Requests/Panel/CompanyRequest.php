<?php

namespace App\Http\Requests\Panel;

use App\Support\LocalizedInputNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class CompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->exists('registration_date')) {
            return;
        }

        $this->merge([
            'registration_date' => LocalizedInputNormalizer::date(
                is_scalar($this->input('registration_date')) ? (string) $this->input('registration_date') : null
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'commercial_name' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'registration_date' => ['nullable', 'date_format:Y-m-d'],
            'national_id' => ['nullable', 'string', 'max:255'],
            'economic_code' => ['nullable', 'string', 'max:255'],
            'legal_type' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'ceo_name' => ['nullable', 'string', 'max:255'],
            'ceo_national_code' => ['nullable', 'string', 'max:255'],
        ];
    }
}
