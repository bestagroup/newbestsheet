<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'category',
        'title',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public static function categoryLabels(): array
    {
        return [
            'identity' => 'مدارک هویتی',
            'employment' => 'مدارک استخدامی',
            'contract' => 'قرارداد و احکام',
            'education' => 'مدارک تحصیلی',
            'insurance' => 'بیمه و سوابق',
            'other' => 'سایر',
        ];
    }
}
