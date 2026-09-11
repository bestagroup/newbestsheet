<?php

namespace App\Http\Requests\Panel;

use App\Support\LocalizedInputNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class InvesteeSaleRequest extends FormRequest
{
    private const INTEGER_FIELDS = [
        'count_customers',
        'count_sales',
        'production_count',
        'amount_sales',
        'monthly_income',
        'current_cost',
        'financial_cost',
    ];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (self::INTEGER_FIELDS as $field) {
            if ($this->exists($field)) {
                $normalized[$field] = LocalizedInputNormalizer::unsignedInteger(
                    is_scalar($this->input($field)) ? (string) $this->input($field) : null
                );
            }
        }

        if ($this->exists('date')) {
            $normalized['date'] = LocalizedInputNormalizer::date(
                is_scalar($this->input('date')) ? (string) $this->input('date') : null
            );
        }

        $this->merge($normalized);
    }

    public function rules(): array
    {
        return [
            'count_customers' => ['required', 'integer', 'min:0'],
            'count_sales' => ['nullable', 'integer', 'min:0'],
            'production_count' => ['nullable', 'integer', 'min:0'],
            'amount_sales' => ['nullable', 'regex:/^\d{1,20}$/'],
            'monthly_income' => ['nullable', 'regex:/^\d{1,20}$/'],
            'current_cost' => ['nullable', 'regex:/^\d{1,20}$/'],
            'financial_cost' => ['nullable', 'regex:/^\d{1,20}$/'],
            'date' => ['required', 'date_format:Y-m-d'],
            'description' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
