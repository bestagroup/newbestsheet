<?php

namespace App\Http\Requests\Panel;

use App\Rules\JalaliDate;
use App\Support\LocalizedInputNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ExternalLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['issued_on' => LocalizedInputNormalizer::jalaliDate($this->input('issued_on')), 'confidential' => $this->boolean('confidential')]);
    }

    public function rules(): array
    {
        return [
            'direction' => ['required', Rule::in(['incoming', 'outgoing'])],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'correspondent' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'issued_on' => ['required', new JalaliDate],
            'due_on' => ['nullable', 'date_format:Y-m-d'],
            'assigned_to' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($q) => $q->where('status', 4)->where('level', '!=', 'applicant'))],
            'confidential' => ['boolean'],
            'media_file_id' => ['nullable', 'integer', 'exists:media_files,id'],
            'lock_version' => [$this->isMethod('POST') ? 'nullable' : 'required', 'integer', 'min:0'],
        ];
    }
}
