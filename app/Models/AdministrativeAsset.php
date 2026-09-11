<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdministrativeAsset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'asset_code',
        'name',
        'category',
        'brand',
        'model',
        'serial_number',
        'property_tag',
        'quantity',
        'unit',
        'acquisition_date',
        'purchase_cost',
        'location',
        'custodian_employee_id',
        'condition',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'purchase_cost' => 'decimal:0',
        'quantity' => 'integer',
    ];

    public function custodian(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'custodian_employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function conditionLabels(): array
    {
        return [
            'good' => 'سالم',
            'fair' => 'قابل استفاده',
            'damaged' => 'آسیب‌دیده',
            'under_repair' => 'در تعمیر',
            'retired' => 'اسقاطی',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            'in_use' => 'در حال استفاده',
            'in_stock' => 'موجود در انبار',
            'assigned' => 'تحویل پرسنل',
            'maintenance' => 'تعمیر و نگهداری',
            'disposed' => 'واگذار/اسقاط شده',
            'lost' => 'مفقودی',
        ];
    }
}
