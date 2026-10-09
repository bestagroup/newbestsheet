<?php

namespace App\Http\Requests\Panel;

use App\Rules\JalaliDate;
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
            'idempotency_key' => [$this->isMethod('POST') ? 'required' : 'nullable', 'uuid'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'amount' => ['required', 'regex:/^[1-9][0-9]{0,19}$/'],
            'serial' => ['nullable', 'integer', 'between:1,5'],
            'docserial' => ['required', 'string', 'max:255'],
            'date' => ['required', new JalaliDate],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
