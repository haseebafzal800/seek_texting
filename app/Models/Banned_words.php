<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Banned_words extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $fillable = [
        'phrase', 'status', 'is_deleted'
    ];
    protected $dates = ['deleted_at'];
}
