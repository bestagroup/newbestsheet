<?php

namespace App\Http\Requests\Panel;

use App\Support\LocalizedInputNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MinuteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'date' => LocalizedInputNormalizer::jalaliDate($this->input('date')),
        ]);
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'date' => ['nullable', 'string', 'max:20'],
            'type' => ['nullable', 'string', 'max:100'],
            'file_path' => ['nullable', 'string', 'max:2048', Rule::exists('media_files', 'file_path')->where(fn ($q) => $q->where('project_id', $this->input('project_id'))->whereNull('deleted_at'))],
        ];
    }
}
