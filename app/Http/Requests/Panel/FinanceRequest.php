<?php

namespace App\Http\Requests\Panel;

use App\Support\LocalizedInputNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class FinanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'project_id' => LocalizedInputNormalizer::digits($this->input('project_id')),
            'amount' => LocalizedInputNormalizer::decimal($this->input('amount')),
            'serial' => LocalizedInputNormalizer::digits($this->input('serial')),
            'docserial' => LocalizedInputNormalizer::digits($this->input('docserial')),
            'date' => LocalizedInputNormalizer::jalaliDate($this->input('date')),
        ]);
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'serial' => ['nullable', 'integer', 'between:1,5'],
            'docserial' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'regex:/^\d{4}\/\d{2}\/\d{2}$/'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
