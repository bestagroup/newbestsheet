<?php

namespace App\Http\Requests\Panel;

use App\Support\KpiOptions;
use App\Support\LocalizedInputNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Morilog\Jalali\Jalalian;
use Throwable;

class ProjectKpiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        if ($this->exists('kpi_number')) {
            $normalized['kpi_number'] = LocalizedInputNormalizer::unsignedInteger(
                is_scalar($this->input('kpi_number')) ? (string) $this->input('kpi_number') : null
            );
        }

        if ($this->exists('value')) {
            $normalized['value'] = LocalizedInputNormalizer::digits(
                is_scalar($this->input('value')) ? (string) $this->input('value') : null
            );
        }

        foreach (['baseline_value', 'target_value', 'weight', 'tolerance'] as $field) {
            if ($this->exists($field)) {
                $normalized[$field] = LocalizedInputNormalizer::decimal(
                    is_scalar($this->input($field)) ? (string) $this->input($field) : null
                );
            }
        }

        foreach (['starts_at', 'ends_at'] as $field) {
            if ($this->exists($field)) {
                $normalized[$field] = LocalizedInputNormalizer::date(
                    is_scalar($this->input($field)) ? (string) $this->input($field) : null
                );
            }
        }

        if ($this->exists('deadline')) {
            $normalized['deadline'] = LocalizedInputNormalizer::jalaliDate(
                is_scalar($this->input('deadline')) ? (string) $this->input('deadline') : null
            );
        }

        if ($this->exists('completed')) {
            $normalized['completed'] = $this->boolean('completed');
        }

        $this->merge($normalized);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $projectId = (int) $this->route('project');

            if ($this->filled('project_contract_id') && ! \DB::table('project_contracts')
                ->where('id', $this->integer('project_contract_id'))
                ->where('project_id', $projectId)
                ->exists()) {
                $validator->errors()->add('project_contract_id', 'قرارداد انتخاب‌شده متعلق به این طرح نیست.');
            }

            if ($this->filled('deadline')) {
                try {
                    Jalalian::fromFormat('Y/m/d', (string) $this->input('deadline'))->toCarbon();
                } catch (Throwable) {
                    $validator->errors()->add('deadline', 'تاریخ مهلت KPI معتبر نیست.');
                }
            }
        });
    }

    public function rules(): array
    {
        $projectId = (int) $this->route('project');
        $kpiId = $this->route('kpi') ? (int) $this->route('kpi') : null;

        $numberRule = Rule::unique('kpis', 'kpi_number')
            ->where(static function ($query) use ($projectId, $kpiId) {
                $query->where('project_id', $projectId);

                if ($kpiId) {
                    $query->where('is_current', true);
                }

                return $query;
            })
            ->ignore($kpiId);

        return [
            'project_contract_id' => ['nullable', 'integer', 'exists:project_contracts,id'],
            'code' => [
                'nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9_.-]+$/',
                Rule::unique('kpis', 'code')
                    ->where(static fn ($query) => $query->where('project_id', $projectId))
                    ->ignore($kpiId),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'type' => ['nullable', Rule::in(KpiOptions::TYPES)],
            'direction' => ['nullable', Rule::in(['increase', 'decrease', 'maintain'])],
            'type_value' => ['nullable', Rule::in(KpiOptions::BASES)],
            'baseline_value' => ['nullable', 'numeric'],
            'target_value' => ['nullable', 'numeric'],
            'weight' => ['nullable', 'numeric', 'gt:0', 'max:100'],
            'tolerance' => ['nullable', 'numeric', 'min:0'],
            'value' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', Rule::in(KpiOptions::UNITS)],
            'deadline' => ['nullable', 'string', 'max:255'],
            'period_time' => ['nullable', Rule::in(KpiOptions::PERIODS)],
            'measurement_frequency' => ['nullable', Rule::in(['daily', 'monthly', 'quarterly', 'semiannual', 'annual'])],
            'time_step' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', Rule::in(['draft', 'active', 'suspended', 'completed'])],
            'kpi_number' => [
                'required',
                'integer',
                'min:1',
                $numberRule,
            ],
            'file_link' => ['nullable', 'string', 'max:2048'],
            'completed' => ['nullable', 'boolean'],
        ];
    }
}
