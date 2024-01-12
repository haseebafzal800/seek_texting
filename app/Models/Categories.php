<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Contactlist;
use App\Models\Lists;
class Categories extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $fillable = [
        'name', 'status' 
    ];
    // protected $dates = ['deleted_at'];

    /*public function contactlist() {
        return $this->hasMany('App\Models\Contactlist::class');
    }*/

    public function lists() {
        return $this->hasMany(Lists::class, 'category_id', 'id');
    }
    public function user() {
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }
}
