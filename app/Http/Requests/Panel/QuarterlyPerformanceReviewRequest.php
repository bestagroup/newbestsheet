<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class QuarterlyPerformanceReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:approved,rejected'],
            'review_comment' => ['required', 'string', 'max:10000'],
        ];
    }
}
