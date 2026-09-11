<?php

namespace App\Http\Requests\Panel;

use App\Support\LocalizedInputNormalizer;
use Illuminate\Foundation\Http\FormRequest;

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
            'file_path' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
