<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsLimitCount extends Model
{
    use HasFactory;
    protected $table = 'sms_limit_count';

    protected $fillable = ['user_id', 'sms_count'];
}
