<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Categories;
use Illuminate\Database\Eloquent\SoftDeletes;


class Contactlist extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = ['name', 'contact', 'email', 'zip_code', 'state', 'is_deleted', 'address', 'notes', 'user_id', 'status', 'list_id'];
    protected $dates = ['deleted_at'];
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function lists()
    {
        return $this->belongsTo('App\Models\Lists', 'list_id', 'id');
    }
}
