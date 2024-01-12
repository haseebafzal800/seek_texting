<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign_send_to_list extends Model
{
    use HasFactory;
    protected $table = 'campaign_send_to_list';

    protected $fillable = [
        'campaign_id', 'list_id'	 
    ];

    public function campaign() {
        return $this->belongsTo('App\Models\Campaigns', 'campaign_id', 'id');
    }

    public function lists() {
        return $this->belongsTo('App\Models\Lists', 'list_id', 'id');
    }
}
