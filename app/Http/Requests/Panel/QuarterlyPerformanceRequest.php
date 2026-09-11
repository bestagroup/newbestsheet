<?php

namespace App\Http\Requests\Panel;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class QuarterlyPerformanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'project_contract_id' => ['nullable', 'integer', 'exists:project_contracts,id'],
            'year' => ['required', 'integer', 'between:1300,2200'],
            'quarter' => ['required', 'integer', 'between:1,4'],
            'period_starts_at' => ['required', 'date'],
            'period_ends_at' => ['required', 'date', 'after_or_equal:period_starts_at'],
            'executive_summary' => ['nullable', 'string', 'max:10000'],
            'achievements' => ['nullable', 'array', 'max:100'],
            'achievements.*' => ['string', 'max:2000'],
            'challenges' => ['nullable', 'array', 'max:100'],
            'challenges.*' => ['string', 'max:2000'],
            'risks' => ['nullable', 'array', 'max:100'],
            'risks.*' => ['string', 'max:2000'],
            'financial_snapshot' => ['nullable', 'array'],
            'operational_snapshot' => ['nullable', 'array'],
            'submit' => ['nullable', 'boolean'],
            'measurements' => ['nullable', 'array', 'max:200'],
            'measurements.*.kpi_id' => ['required', 'integer', 'distinct', 'exists:kpis,id'],
            'measurements.*.measured_value' => ['nullable', 'numeric'],
            'measurements.*.notes' => ['nullable', 'string', 'max:5000'],
            'measurements.*.evidence_media_file_id' => ['nullable', 'integer', 'exists:media_files,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $project = $this->route('project');
            $projectId = $project instanceof Project ? (int) $project->getKey() : (int) $project;

            if ($this->filled('project_contract_id') && ! \DB::table('project_contracts')
                ->where('id', $this->integer('project_contract_id'))
                ->where('project_id', $projectId)
                ->exists()) {
                $validator->errors()->add('project_contract_id', 'قرارداد انتخاب‌شده متعلق به این طرح نیست.');
            }

            foreach ((array) $this->input('measurements', []) as $index => $measurement) {
                $kpiId = (int) ($measurement['kpi_id'] ?? 0);
                if ($kpiId && ! \DB::table('kpis')->where('id', $kpiId)->where('project_id', $projectId)->exists()) {
                    $validator->errors()->add("measurements.{$index}.kpi_id", 'KPI انتخاب‌شده متعلق به این طرح نیست.');
                }

                $mediaId = (int) ($measurement['evidence_media_file_id'] ?? 0);
                if ($mediaId && ! \DB::table('media_files')->where('id', $mediaId)->where('project_id', $projectId)->exists()) {
                    $validator->errors()->add("measurements.{$index}.evidence_media_file_id", 'مستند انتخاب‌شده متعلق به این طرح نیست.');
                }
            }
        });
    }
}
