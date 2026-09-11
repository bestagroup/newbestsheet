<?php

namespace App\Http\Requests\Panel;

use App\Support\LocalizedInputNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Morilog\Jalali\Jalalian;
use Throwable;

class CalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $guests = collect($this->input('eventGuests', []))
            ->map(fn ($id) => LocalizedInputNormalizer::digits((string) $id))
            ->filter(fn ($id) => $id !== null && ctype_digit($id))
            ->map(fn ($id) => (string) ((int) $id))
            ->unique()
            ->values()
            ->all();

        $this->merge([
            'eventStartDate' => $this->normalizeDateTime($this->input('eventStartDate')),
            'eventEndDate' => $this->normalizeDateTime($this->input('eventEndDate')),
            'eventGuests' => $guests,
            'allDay' => $this->boolean('allDay'),
        ]);
    }

    public function rules(): array
    {
        return [
            'eventTitle' => ['required', 'string', 'max:255'],
            'eventLabel' => ['nullable', 'string', 'max:100'],
            'eventStartDate' => ['required', 'string', 'max:30'],
            'eventEndDate' => ['nullable', 'string', 'max:30'],
            'allDay' => ['boolean'],
            'eventURL' => ['nullable', 'url', 'max:2048'],
            'eventLocation' => ['nullable', 'string', 'max:255'],
            'eventDescription' => ['nullable', 'string', 'max:5000'],
            'eventGuests' => ['array'],
            'eventGuests.*' => ['distinct', 'exists:users,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $start = null;
            $end = null;

            try {
                $start = $this->toCarbon((string) $this->input('eventStartDate'));
            } catch (Throwable) {
                $validator->errors()->add('eventStartDate', 'فرمت تاریخ شروع رویداد معتبر نیست.');
            }

            if ($this->filled('eventEndDate')) {
                try {
                    $end = $this->toCarbon((string) $this->input('eventEndDate'));
                } catch (Throwable) {
                    $validator->errors()->add('eventEndDate', 'فرمت تاریخ پایان رویداد معتبر نیست.');
                }
            }

            if ($start && $end && $end->lt($start)) {
                $validator->errors()->add('eventEndDate', 'تاریخ پایان نمی‌تواند قبل از تاریخ شروع باشد.');
            }
        });
    }

    private function normalizeDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = LocalizedInputNormalizer::digits((string) $value);
        $value = trim(str_replace('T', ' ', $value));
        if (preg_match('/^\d{4}[\/-]\d{1,2}[\/-]\d{1,2}$/', $value)) {
            $value .= ' 00:00:00';
        }
        $value = str_replace('/', '-', $value);

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})\s+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?$/', $value, $m)) {
            return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $m[1], $m[2], $m[3], $m[4], $m[5], $m[6] ?? 0);
        }

        return $value;
    }

    private function toCarbon(string $value)
    {
        return Jalalian::fromFormat('Y-m-d H:i:s', $value)->toCarbon();
    }
}
