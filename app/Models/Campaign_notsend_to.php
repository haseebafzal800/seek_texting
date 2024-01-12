<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign_notsend_to extends Model
{
    use HasFactory;
    protected $table = 'campaign_notsend_to';

    protected $fillable = [
        'campaign_id', 'email', 'contact'	 
    ];

    public function campaign() {
        return $this->belongsTo('App\Models\Campaigns', 'campaign_id', 'id');
    }
}
