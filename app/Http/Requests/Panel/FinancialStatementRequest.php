<?php

namespace App\Http\Requests\Panel;

use App\Models\Financial_statement;
use App\Models\Project;
use App\Support\LocalizedInputNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinancialStatementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $payload = [
            'project_id' => LocalizedInputNormalizer::digits($this->input('project_id')),
            'year' => LocalizedInputNormalizer::digits($this->input('year')),
            'month' => LocalizedInputNormalizer::digits($this->input('month')),
        ];

        foreach (Financial_statement::monetaryFields() as $field) {
            $payload[$field] = LocalizedInputNormalizer::decimal($this->input($field));
        }

        $this->merge($payload);
    }

    public function rules(): array
    {
        $statementId = $this->route('financialstatement');
        $periodUnique = Rule::unique('financial_statements', 'project_id')
            ->where(fn ($query) => $query
                ->where('year', $this->input('year'))
                ->where('month', $this->input('month')))
            ->ignore($statementId);

        $rules = [
            'project_id' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')->where(
                    fn ($query) => $query->where('invest_step', '>=', Project::PORTFOLIO_MINIMUM_STEP)
                ),
                $periodUnique,
            ],
            'year' => ['required', 'integer', 'between:1300,1600'],
            'month' => ['required', 'integer', 'between:1,12'],
        ];

        foreach (Financial_statement::monetaryFields() as $field) {
            $rules[$field] = ['nullable', 'numeric'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'project_id.exists' => 'شرکت انتخاب‌شده در پورتفوی سرمایه‌گذاری قرار ندارد.',
            'project_id.unique' => 'برای این پروژه در سال و ماه انتخاب‌شده قبلاً صورت مالی ثبت شده است.',
        ];
    }
}
