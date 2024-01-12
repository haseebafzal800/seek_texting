<?php

namespace App\Events;

use App\Models\User;
use App\Models\Contactlist;
use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * User that sent the message
     *
     * @var User
     */
    // public $user;

    /**
     * Message details
     *
     * @var Message
     */
    public $data;
    private $channel;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, Message $message, $channel = null)
    {
        $url = '/conversation';
        if ($channel) {
            $this->channel = $channel;
        }
        if ($message->type == '') {
            $sender = User::select('id', 'first_name', 'last_name')
                ->where('id', $message->from_id)->first();
            $receiver = null;
        } else {
            if ($message->type == 'user') {
                $sender = User::select('id', 'first_name', 'last_name')
                    ->where('id', $message->from_id)->first();
                $receiver = Contactlist::select('id', 'name AS first_name')
                    ->where('id', $message->to_id)->first();
                // $url='/conversation/'.$receiver->id;
            } else {
                $receiver = User::select('id', 'first_name', 'last_name')
                    ->where('id', $message->to_id)->first();
                $sender = Contactlist::select('id', 'name AS first_name')
                    ->where('id', $message->from_id)->first();
                $url = '/conversation/' . $sender->id;
            }
        }

        $this->data = [
            "id" => $message->id,
            "type" => $message->type,
            "from_id" => $message->from_id,
            "to_id" => $message->to_id,
            "message" => $message->message,
            "attachment" => $message->attachment,
            "seen" => $message->seen,
            "created_at" => $message->created_at,
            "updated_at" => $message->updated_at,
            'url' => $url,
            'sender' => $sender ?? '',
            'receiver' => $receiver,
        ];
    }


    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        if ($this->channel) {
            return new Channel($this->channel);
        } else {
            return new Channel('chat');
        }
    }

    public function broadcastAs()
    {
        if ($this->channel) {
            return 'g-' . $this->channel;
        } else {
            return 'g-chat';
        }
    }
}
