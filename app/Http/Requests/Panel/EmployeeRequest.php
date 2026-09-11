<?php

namespace App\Http\Requests\Panel;

use App\Models\Employee;
use App\Support\LocalizedInputNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'personnel_code' => LocalizedInputNormalizer::digits($this->input('personnel_code')),
            'national_id' => LocalizedInputNormalizer::digits($this->input('national_id')),
            'mobile' => LocalizedInputNormalizer::digits($this->input('mobile')),
            'phone' => LocalizedInputNormalizer::digits($this->input('phone')),
            'postal_code' => LocalizedInputNormalizer::digits($this->input('postal_code')),
            'insurance_number' => LocalizedInputNormalizer::digits($this->input('insurance_number')),
            'birth_date' => LocalizedInputNormalizer::jalaliDate($this->input('birth_date')),
            'hire_date' => LocalizedInputNormalizer::jalaliDate($this->input('hire_date')),
            'iban' => $this->filled('iban')
                ? strtoupper(str_replace(' ', '', (string) $this->input('iban')))
                : null,
        ]);
    }

    public function rules(): array
    {
        $employee = $this->route('employee');
        $employeeId = $employee instanceof Employee ? $employee->getKey() : $employee;

        return [
            'personnel_code' => ['required', 'string', 'max:50', Rule::unique('employees')->ignore($employeeId)],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'national_id' => ['nullable', 'string', 'max:20', Rule::unique('employees')->ignore($employeeId)],
            'father_name' => ['nullable', 'string', 'max:120'],
            'birth_date' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'mobile' => ['nullable', 'string', 'max:32'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:3000'],
            'hire_date' => ['nullable', 'string', 'max:20'],
            'employment_type' => ['nullable', 'string', 'max:60'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'insurance_number' => ['nullable', 'string', 'max:80'],
            'iban' => ['nullable', 'string', 'max:34', 'regex:/^IR\d{24}$/'],
            'status' => ['required', Rule::in(array_keys(Employee::statusLabels()))],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
