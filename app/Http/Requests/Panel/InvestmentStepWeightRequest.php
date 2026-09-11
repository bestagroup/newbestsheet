<?php

namespace App\Http\Requests\Panel;

use App\Models\Investstep;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class InvestmentStepWeightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'weights' => ['required', 'array', 'min:1'],
            'weights.*' => ['required', 'numeric', 'min:0.001', 'max:100000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $activeIds = Investstep::query()
                ->where('status', 4)
                ->orderBy('id')
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->values();

            $submittedIds = collect(array_keys((array) $this->input('weights', [])))
                ->map(static fn ($id): int => (int) $id)
                ->sort()
                ->values();

            if ($activeIds->sort()->values()->all() !== $submittedIds->all()) {
                $validator->errors()->add(
                    'weights',
                    'وزن تمام مراحل فعال باید به‌صورت یکجا ارسال شود.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'weights.required' => 'وزن مراحل ارسال نشده است.',
            'weights.*.numeric' => 'وزن هر مرحله باید عددی باشد.',
            'weights.*.min' => 'وزن هر مرحله باید بزرگ‌تر از صفر باشد.',
        ];
    }
}
