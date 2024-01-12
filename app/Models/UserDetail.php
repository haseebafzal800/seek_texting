<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
class UserDetail extends Model
{
    use HasFactory;
    // protected $fillable = [ 'user_id', 'car_model', 'car_color', 'car_plate_number', 'car_production_year', 'work_address', 'home_address', 'work_latitude', 'work_longitude', 'home_latitude', 'home_longitude' ];
    protected $guarded = [''];
    protected $casts = [
        'user_id' => 'integer',
        'cards' => 'json',
    ];
  

    // public function getCardsAttribute($value)
    // {
    //     return stripslashes($value);
    // }
}
