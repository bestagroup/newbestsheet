<?php

namespace App\Http\Requests\Panel;

use App\Models\Project;
use App\Support\LocalizedInputNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProjectContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $values = [];
        foreach (['amount', 'equity_percentage'] as $field) {
            if ($this->exists($field)) {
                $values[$field] = LocalizedInputNormalizer::decimal((string) $this->input($field));
            }
        }
        foreach (['signed_at', 'starts_at', 'ends_at'] as $field) {
            if ($this->exists($field)) {
                $values[$field] = LocalizedInputNormalizer::date((string) $this->input($field));
            }
        }
        $this->merge($values);
    }

    public function rules(): array
    {
        $project = $this->route('project');
        $projectId = $project instanceof Project ? (int) $project->getKey() : (int) $project;
        $contractId = $this->route('contract')?->getKey();

        return [
            'contract_number' => [
                'nullable', 'string', 'max:100',
                Rule::unique('project_contracts', 'contract_number')
                    ->where(static fn ($query) => $query->where('project_id', $projectId))
                    ->ignore($contractId),
            ],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['draft', 'active', 'suspended', 'terminated', 'completed'])],
            'signed_at' => ['nullable', 'date'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'equity_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'currency' => ['nullable', Rule::in(['IRR', 'IRT', 'USD', 'EUR'])],
            'contract_media_file_id' => ['nullable', 'integer', 'exists:media_files,id'],
            'terms' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $project = $this->route('project');
            $projectId = $project instanceof Project ? (int) $project->getKey() : (int) $project;
            if ($this->filled('contract_media_file_id') && ! \DB::table('media_files')
                ->where('id', $this->integer('contract_media_file_id'))
                ->where('project_id', $projectId)
                ->exists()) {
                $validator->errors()->add('contract_media_file_id', 'فایل قرارداد متعلق به این طرح نیست.');
            }
        });
    }
}
