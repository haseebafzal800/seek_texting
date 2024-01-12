<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;
    protected $table = 'ch_messages';
    public $fillable = ['message'];
    public function user()
    {
        return $this->belongsTo('App\Models\User','from_id','id');
    }
}
