<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Campaign_detail;

class Campaigns extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'type',
        'status',
        'user_id',
        'is_deleted',
        'campaign_interval',
        'campaign_start_time',
        'campaign_start_date',
        'campaign_end_date',
        'campaign_time_zone',
        'tags',
        'subject_line',
        'preview_text',
        'message',
        'sender_name',
        'sender_email',
        'sender_email_as_reply_to'
    ];
    protected $dates = ['deleted_at'];
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }
    public function campaign_send_to()
    {
        return $this->hasMany('App\Models\Campaign_send_to', 'campaign_id', 'id');
    }
    public function campaign_notsend_to()
    {
        return $this->hasMany('App\Models\Campaign_notsend_to', 'campaign_id', 'id');
    }
    public function campaign_send_to_list()
    {
        return $this->hasMany('App\Models\Campaign_send_to_list', 'campaign_id', 'id');
    }
}
