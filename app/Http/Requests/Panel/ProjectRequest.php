<?php

namespace App\Http\Requests\Panel;

use App\Support\LocalizedInputNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
{
    private const MONEY_FIELDS = [
        'amount_request_accept',
        'amount_commitment_first_stage',
        'amount_commitment_second_stage',
        'amount_commitment_third_stage',
        'amount_commitment_fourth_stage',
        'amount_commitment_fifth_stage',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (self::MONEY_FIELDS as $field) {
            if ($this->exists($field)) {
                $normalized[$field] = LocalizedInputNormalizer::unsignedInteger(
                    is_scalar($this->input($field)) ? (string) $this->input($field) : null
                );
            }
        }

        if ($this->exists('start_date')) {
            $normalized['start_date'] = LocalizedInputNormalizer::date(
                is_scalar($this->input('start_date')) ? (string) $this->input('start_date') : null
            );
        }

        if ($this->exists('percentageshare')) {
            $normalized['percentageshare'] = LocalizedInputNormalizer::decimal(
                is_scalar($this->input('percentageshare')) ? (string) $this->input('percentageshare') : null
            );
        }

        foreach (['registration_number', 'national_id', 'economic_code', 'ceo_national_code', 'ceo_phone', 'tel', 'postal_code'] as $field) {
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
        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'title' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'registration_date' => ['nullable', 'string', 'max:50'],
            'national_id' => ['nullable', 'string', 'max:255'],
            'economic_code' => ['nullable', 'string', 'max:255'],
            'legal_type' => ['nullable', 'string', 'max:255'],
            'tel' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'state' => ['nullable', 'integer', 'exists:states,id'],
            'city' => ['nullable', 'integer', 'exists:cities,id'],
            'CEO' => ['nullable', 'string', 'max:255'],
            'ceo_national_code' => ['nullable', 'string', 'max:255'],
            'ceo_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'logo' => ['nullable', 'string', 'max:2048'],
            'description' => ['nullable', 'string'],
            'percentageshare' => ['nullable', 'numeric', 'between:0,100'],
            'portfo_status' => ['nullable', 'string', 'max:255'],
            'activity_status' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'amount_request_accept' => ['nullable', 'regex:/^\d{1,20}$/'],
            'amount_commitment_first_stage' => ['nullable', 'regex:/^\d{1,20}$/'],
            'amount_commitment_second_stage' => ['nullable', 'regex:/^\d{1,20}$/'],
            'amount_commitment_third_stage' => ['nullable', 'regex:/^\d{1,20}$/'],
            'amount_commitment_fourth_stage' => ['nullable', 'regex:/^\d{1,20}$/'],
            'amount_commitment_fifth_stage' => ['nullable', 'regex:/^\d{1,20}$/'],
        ];
    }
}
