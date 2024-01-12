<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Contactlist;
use App\Models\Categories;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lists extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $fillable = [
        'name', 'status', 'user_id', 'category_id'
    ];
    protected $dates = ['deleted_at'];
    public function campaign_send_to_list()
    {
        return $this->hasMany('App\Models\Campaign_send_to_list', 'list_id', 'id');
    }

    public function contactlist()
    {
        return $this->hasMany(Contactlist::class, 'list_id', 'id');
    }
    public function categories()
    {
        return $this->belongsTo(Categories::class, 'category_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }
}
