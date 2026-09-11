<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Owner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'tel', 'mobile', 'email', 'ceo', 'meli_code', 'eghtesadi_code',
        'date_sabt', 'address', 'social', 'image', 'favicon16', 'favicon32', 'summery', 'user_id',
    ];
}
