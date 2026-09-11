<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'user_id',
        'company_name',
        'commercial_name',
        'registration_number',
        'registration_date',
        'national_id',
        'economic_code',
        'legal_type',
        'phone',
        'email',
        'website',
        'province',
        'city',
        'address',
        'postal_code',
        'ceo_name',
        'ceo_national_code',
        'is_verified',
    ];

    public function members()
    {
        return $this->hasMany(CompanyMembers::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->hasMany(Project::class, 'company_id');
    }

    public function minute()
    {
        return $this->hasMany(Minute::class, 'company_id');
    }

    public function MediaFile()
    {
        return $this->hasMany(MediaFile::class);
    }
}
