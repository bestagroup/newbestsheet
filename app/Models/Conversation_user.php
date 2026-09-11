<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation_user extends Model
{
    use HasFactory;

    protected $table = 'conversation_user';

    protected $guarded = [];
}
