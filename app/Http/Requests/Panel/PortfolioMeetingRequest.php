<?php

namespace App\Http\Requests\Panel;

use App\Models\Project;
use App\Rules\JalaliDate;
use App\Support\LocalizedInputNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PortfolioMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['held_on' => LocalizedInputNormalizer::jalaliDate($this->input('held_on'))]);
        if (is_string($this->input('agenda'))) {
            $this->merge(['agenda' => array_values(array_filter(array_map('trim', explode("\n", $this->input('agenda')))))]);
        }
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', Rule::exists('projects', 'id')->where(fn ($q) => $q->where('invest_step', '>=', Project::PORTFOLIO_MINIMUM_STEP))],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['ordinary', 'extraordinary', 'board'])],
            'held_on' => ['required', new JalaliDate],
            'location' => ['nullable', 'string', 'max:255'],
            'agenda' => ['required', 'array', 'min:1', 'max:50'],
            'agenda.*' => ['required', 'string', 'max:2000'],
            'attendees' => ['required', 'string', 'max:10000'],
            'media_file_id' => ['nullable', 'integer', 'exists:media_files,id'],
            'lock_version' => [$this->isMethod('POST') ? 'nullable' : 'required', 'integer', 'min:0'],
        ];
    }
}
