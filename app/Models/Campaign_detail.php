<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Campaigns;

class Campaign_detail extends Model
{
    use HasFactory;
    protected $table = 'campaign_details';
    protected $fillable = [
        'campaign_id', 'status', 'schedule_date'
    ];
    protected $dates = ['deleted_at'];
}
