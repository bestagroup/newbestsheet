<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'personnel_code',
        'first_name',
        'last_name',
        'national_id',
        'father_name',
        'birth_date',
        'gender',
        'mobile',
        'phone',
        'email',
        'postal_code',
        'address',
        'hire_date',
        'employment_type',
        'job_title',
        'department',
        'insurance_number',
        'iban',
        'status',
        'notes',
        'created_by',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class)->latest();
    }

    public function assets(): HasMany
    {
        return $this->hasMany(AdministrativeAsset::class, 'custodian_employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public static function statusLabels(): array
    {
        return [
            'active' => 'فعال',
            'on_leave' => 'مرخصی',
            'inactive' => 'غیرفعال',
            'terminated' => 'قطع همکاری',
        ];
    }
}
