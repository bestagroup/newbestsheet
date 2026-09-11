<?php

namespace App\Http\Requests\Panel;

use App\Support\LocalizedInputNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class ProjectMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['national_code', 'start_date', 'end_date'] as $field) {
            if (! $this->exists($field)) {
                continue;
            }

            $value = is_scalar($this->input($field)) ? (string) $this->input($field) : null;
            $normalized[$field] = $field === 'national_code'
                ? LocalizedInputNormalizer::digits($value)
                : LocalizedInputNormalizer::digits($value);
        }

        if ($this->exists('is_active')) {
            $normalized['is_active'] = $this->boolean('is_active');
        }

        // ستون قدیمی role_type در دیتابیس اجباری است؛ در رابط کاربری «سمت» تنها مرجع نقش عضو است.
        if ($this->exists('position')) {
            $normalized['role_type'] = is_scalar($this->input('position'))
                ? (string) $this->input('position')
                : null;
        }

        $this->merge($normalized);
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'national_code' => ['required', 'string', 'max:20'],
            'position' => ['required', 'string', 'max:255'],
            'role_type' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'string', 'max:50'],
            'end_date' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
