<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class ProjectKpiReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:approved,rejected'],
            'review_comment' => ['nullable', 'required_if:decision,rejected', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'review_comment.required_if' => 'ثبت دلیل رد KPI الزامی است.',
        ];
    }
}
