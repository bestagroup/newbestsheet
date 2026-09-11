<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    use HasFactory;

    protected $table = 'visits';

    protected $fillable = ['ip', 'page', 'device', 'browser', 'from_page', 'to_page', 'date'];
}
