<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class InvesteeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $fileRules = [
            'file',
            'max:'.config('investment.documents.max_size_kb', 51200),
            'mimetypes:'.implode(',', config('investment.documents.allowed_mimes', [])),
        ];

        return [
            // Keep the singular field for backwards compatibility with older clients.
            'file' => ['required_without:files', ...$fileRules],
            'files' => ['required_without:file', 'array', 'min:1', 'max:20'],
            'files.*' => $fileRules,
            'document_requirement_id' => [
                'nullable',
                'required_without:subject_id',
                'integer',
                'exists:invest_step_document_requirements,id',
            ],
            'subject_id' => [
                'nullable',
                'required_without:document_requirement_id',
                'integer',
                'exists:subject_files,id',
            ],
        ];
    }
}
