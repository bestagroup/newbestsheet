<?php

namespace App\Http\Requests\Panel;

use App\Support\LocalizedInputNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Morilog\Jalali\Jalalian;
use Throwable;

class ProjectCommitmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'commitment_id' => LocalizedInputNormalizer::unsignedInteger(
                is_scalar($this->input('commitment_id')) ? (string) $this->input('commitment_id') : null
            ),
            'due_date' => LocalizedInputNormalizer::jalaliDate(
                is_scalar($this->input('due_date')) ? (string) $this->input('due_date') : null
            ),
        ]);
    }

    public function rules(): array
    {
        $projectId = (int) $this->route('project');
        $projectCommitmentId = $this->route('projectCommitment') ? (int) $this->route('projectCommitment') : null;

        return [
            'commitment_id' => [
                'required',
                'integer',
                'exists:commitments,id',
                Rule::unique('project_commitments', 'commitment_id')
                    ->where(static fn ($query) => $query->where('project_id', $projectId))
                    ->ignore($projectCommitmentId),
            ],
            'due_date' => ['required', 'string', 'max:20'],
            'status' => ['nullable', Rule::in(['pending', 'completed', 'waived'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('due_date')) {
                return;
            }

            try {
                Jalalian::fromFormat('Y/m/d', (string) $this->input('due_date'))->toCarbon();
            } catch (Throwable) {
                $validator->errors()->add('due_date', 'تاریخ سررسید تعهد معتبر نیست.');
            }
        });
    }
}
