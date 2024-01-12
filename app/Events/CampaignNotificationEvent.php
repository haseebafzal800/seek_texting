<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Notifications;

class CampaignNotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */

    private $channel;
    public $data;

    public function __construct($campaign, $channel, $type=null)
    {
        $this->channel = $channel;
        
        // $notification = new Notifications;
        if($type=='cf'){
            $notification['title'] = 'Campaign Fail Notification';
            $notification['user_id'] = $campaign->user_id;
            $notification['description'] = 'Your campaign ' . $campaign->title . ' failed to complete due to daily text limit exceed! please contact admin to resolve the issue';
            $ok = Notifications::insert($notification);
        }else{
            $notification['title'] = 'Campaign Notification';
            $notification['user_id'] = $campaign->user_id;
            $notification['description'] = 'Your campaign ' . $campaign->title . ' is ready to run';
            $ok = Notifications::insert($notification);
        }
        
        // $notification->id = $ok->last_id;
        // print_r($notification);
        $d['notification'] = $notification;
        $d['total_unread'] = Notifications::where(['user_id' => $campaign->user_id, 'seen'=>'0'])->count();
        $this->data = $d;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel($this->channel);
    }

    public function broadcastAs()
    {
        return 'g-' . $this->channel;
    }
}
