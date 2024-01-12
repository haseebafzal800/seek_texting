<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Genneral_chat extends Model
{
    use HasFactory;
    protected $table = 'general_chat';
    
    protected $fillable = [
        'sender_id', 'message', 'status', 'is_deleted' 
    ];

    public function user() {
        return $this->belongsTo('App\Models\User', 'sender_id', 'id');
    }
}
