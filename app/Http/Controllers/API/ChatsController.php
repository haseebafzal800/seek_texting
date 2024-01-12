<?php

// namespace App\Http\Controllers;
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Events\MessageSent;
use DB;
use App\Models\Contactlist;
use App\Models\Chat;
use App\Models\SmsLimitCount;


use App\Models\ChMessage;
use DateTime;
use Illuminate\Support\Str;
use App\Jobs\SmsStatusJob;
use Chat as GlobalChat;

class ChatsController extends Controller
{
    public function __construct()
    {
        //   $this->middleware('auth');
    }
    /**
     * Show chats
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('chat');
    }

    public function userDailyTxtLimit()
    {
        $sc_date = date('Y-m-d');
        $user_id = auth()->user()->id;
        $limit = SmsLimitCount::where(['user_id' => $user_id])
            ->where('created_at', 'like', '%' . $sc_date . '%')->first();
        $resp = [
            'data' => ($limit ? $limit->sms_count : '0') . ' / ' . auth()->user()->daily_text_limit,
            'status' => true,
            'message' => '',
        ];
        return response()->json($resp);
    }
    public function getStatisticsMonthWise()
    {
        if (auth()->user()->user_type == 'admin') {
            $sql = 'SELECT 
            SUM(IF(month = "Jan", total, 0)) AS "Jan",
            SUM(IF(month = "Feb", total, 0)) AS "Feb",
            SUM(IF(month = "Mar", total, 0)) AS "Mar",
            SUM(IF(month = "Apr", total, 0)) AS "Apr",
            SUM(IF(month = "May", total, 0)) AS "May",
            SUM(IF(month = "Jun", total, 0)) AS "Jun",
            SUM(IF(month = "Jul", total, 0)) AS "Jul",
            SUM(IF(month = "Aug", total, 0)) AS "Aug",
            SUM(IF(month = "Sep", total, 0)) AS "Sep",
            SUM(IF(month = "Oct", total, 0)) AS "Oct",
            SUM(IF(month = "Nov", total, 0)) AS "Nov",
            SUM(IF(month = "Dec", total, 0)) AS "Dec"
            -- SUM(total) AS total_yearly
            FROM (
        SELECT DATE_FORMAT(created_at, "%b") AS month, count(id) as total
        FROM ch_messages
        WHERE created_at <= NOW() and created_at >= Date_add(Now(),interval - 12 month)
        GROUP BY DATE_FORMAT(created_at, "%m-%Y")) as sub';
        } else {
            $user_id = auth()->user()->id;
            // $where = '(`from_id` = ' . $user_id . ' and `to_id` = ' . $user_id . ') or (`from_id` = ' . $user_id . ' and `to_id` = ' . $subscriber_id . ')';
            $sql = 'SELECT 
            SUM(IF(month = "Jan", total, 0)) AS "Jan",
            SUM(IF(month = "Feb", total, 0)) AS "Feb",
            SUM(IF(month = "Mar", total, 0)) AS "Mar",
            SUM(IF(month = "Apr", total, 0)) AS "Apr",
            SUM(IF(month = "May", total, 0)) AS "May",
            SUM(IF(month = "Jun", total, 0)) AS "Jun",
            SUM(IF(month = "Jul", total, 0)) AS "Jul",
            SUM(IF(month = "Aug", total, 0)) AS "Aug",
            SUM(IF(month = "Sep", total, 0)) AS "Sep",
            SUM(IF(month = "Oct", total, 0)) AS "Oct",
            SUM(IF(month = "Nov", total, 0)) AS "Nov",
            SUM(IF(month = "Dec", total, 0)) AS "Dec"
            -- SUM(total) AS total_yearly
            FROM (
        SELECT DATE_FORMAT(created_at, "%b") AS month, count(id) as total
        FROM ch_messages
        WHERE created_at <= NOW() and created_at >= Date_add(Now(),interval - 12 month) and from_id = ' . $user_id . '
        GROUP BY DATE_FORMAT(created_at, "%m-%Y")) as sub';
        }
        $data = DB::select($sql);
        $resp = [
            'data' => $data,
            'status' => true,
            'message' => '',
        ];
        return response()->json($resp);
    }
    public function getAllChatsByUser($user_id, $order_by = 'desc', $keywords = null) //chats with subscribers
    {
        // DB::connection()->enableQueryLog();
        // $queries = DB::getQueryLog();
        // $last_query = end($queries);
        $chats = DB::table('contactlists')
            ->join('chats', 'chats.subscriber_id', '=', 'contactlists.id')
            ->join('users', 'chats.user_id', '=', 'users.id')
            ->select('contactlists.*')
            ->where(['chats.user_id' => $user_id])
            ->where('contactlists.name', 'LIKE', "%{$keywords}%")
            ->orderBy('chats.updated_at', 'DESC')
            ->orderBy('chats.created_at', 'DESC')
            ->paginate(50);
        if ($order_by == 'asc') {
            $chats = DB::table('contactlists')
                ->join('chats', 'chats.subscriber_id', '=', 'contactlists.id')
                ->join('users', 'chats.user_id', '=', 'users.id')
                ->select('contactlists.*')
                ->where(['chats.user_id' => $user_id])
                ->where('contactlists.name', 'LIKE', "%{$keywords}%")
                ->orderBy('contactlists.name', 'ASC')
                ->paginate(50);
        }
        // $contact_list = DB::table('contactlists')->where(['user_id' => $user_id])->get();
        $reurn = array();
        for ($c = 0; $c < count($chats); $c++) {
            $where = '(`from_id` = ' . $chats[$c]->id . ' and `to_id` = ' . $user_id . ')';
            $cam_to_run = DB::table('ch_messages')->select('*')
                ->whereRaw($where)->where('seen', '0')
                ->count();
            $chats[$c]->unread = $cam_to_run;
        }
        $resp = [
            'status' => true,
            'message' => '',
            'data' => $chats,
            'sort' => $order_by,
        ];
        return response()->json($resp);
    }
    public function storeNewContact(Request $request)
    {
        if ($request->has('contact')) {
            $valid = validate_mobile($request->contact);
            if (!$valid) {
                $resp = [
                    'data' => '',
                    'status' => false,
                    'message' => 'Number not valid',
                ];
            } else {
                // DB::connection()->enableQueryLog();


                $eok = Contactlist::where(['contact' => $valid, 'user_id' => auth()->user()->id])->count();
                // $queries = DB::getQueryLog();

                // $last_query = end($queries);
                // return response()->json($last_query);
                if ($eok == 0) {
                    $resp = array();
                    // $s = getCityState($request->zip_code);
                    $data = new Contactlist;
                    $data->name = $request->name ?? '';
                    $data->contact = $valid;
                    $data->email = $request->email ?? '';
                    $data->zip_code = $request->zip_code ?? '';
                    $data->state = getCityState($request->zip_code) ?? '';
                    $data->address = $request->address ?? '';
                    $data->notes = $request->notes ?? '';
                    $data->status = $request->status ?? 'active';
                    $data->list_id = $request->list_id;
                    $data->user_id = auth()->user()->id;

                    $ok = $data->save();
                    $eok1 = Contactlist::where(['contact' => $valid, 'user_id' => auth()->user()->id])->first();
                    $new_user_id = $eok1->id;
                    $msg = 'success! Contact added';
                } else {
                    $eok1 = Contactlist::where(['contact' => $valid, 'user_id' => auth()->user()->id])->first();
                    // $eok1->created_at = 
                    $new_user_id = $eok1->id;
                    $msg = 'Contact already exists';
                }
                $y = Chat::where(['user_id' => auth()->user()->id, 'subscriber_id' => $new_user_id])->count();
                $sortFlag = false;
                if ($y < 1) {
                    $dataChat = array(
                        'user_id' => auth()->user()->id,
                        'owner' => 'user', //user means our user and subscriber means 
                        'subscriber_id' => $new_user_id,
                        // 'created_at' => $todates,
                    );
                    Chat::insert($dataChat);
                } else {
                    $sortFlag = true;
                    $yc = Chat::where(['user_id' => auth()->user()->id, 'subscriber_id' => $new_user_id])->first();
                    $yc->updated_at = date('Y-m-d h:i:s A'); //2023-05-12 13:08:48
                    $yc->update();
                }
                // $q = DB::table('chats')
                //     ->join('contactlists', 'chats.subscriber_id', '=', 'contactlists.id')
                //     ->join('users', 'chats.user_id', '=', 'users.id')
                //     ->select('contactlists.*', 'chats.unread_count AS unread')
                //     ->where(['chats.user_id' => $request->user_id]);
                // if ($sortFlag) {
                //     $q->orderBy('chats.updated_at', $request->order_type ?? 'DESC')
                //         ->orderBy('chats.created_at', $request->order_type ?? 'DESC');
                // } else {
                //     $q->orderBy('chats.created_at', $request->order_type ?? 'DESC')
                //         ->orderBy('chats.updated_at', $request->order_type ?? 'DESC');
                // }
                // $chats = $q->paginate(50);
                $chats = $this->getChats($request);
                $resp = [
                    'status' => true,
                    'message' => 'Success! Chat created',
                    'data' => $chats,
                    'sort' => 'asc'
                ];
            }
        } else {
            $chats = $this->getChats($request);
            $resp = [
                'status' => true,
                'message' => 'Success! Chat created',
                'data' => $chats,
                'sort' => 'asc'
            ];
        }
        return response()->json($resp);
    }
    public function createNewChat(Request $request)
    {
        if ($request->has('id')) {

            $contactOne = Contactlist::where(['id' => $request->id, 'user_id' => auth()->user()->id])->first();
            // $new_user_id = $eok1->id;
            $recPh = $contactOne->contact??'';
            $recPh = formatNumber($recPh);

            $where = 'user_id = ' . $user->id . ' AND (contact = ' . $recPh . ')';
            $receiver = Contactlist::whereRaw($where)->first();
            $y = Chat::where(['user_id' => auth()->user()->id, 'subscriber_id' => $receiver->id])->count();
            if ($y < 1) {
                $dataChat = array(
                    'user_id' => auth()->user()->id,
                    'owner' => auth()->user()->user_type, //user means our user and subscriber means 
                    'subscriber_id' => $receiver->id,
                );
                Chat::insert($dataChat);
            } else {
                $yc = Chat::where(['user_id' => auth()->user()->id, 'subscriber_id' => $receiver->id])->first();
                $yc->updated_at = date('Y-m-d h:i:s A'); //2023-05-12 13:08:48
                $yc->update();
            }

            $chats = $this->getChats($request);

            $resp = [
                'status' => true,
                'message' => 'Success! Chat created',
                'data' => $chats,
            ];
        }
        return response()->json($resp);
    }
    public function getAllChatsByUserWithSort(Request $request) //chats with subscribers
    {
        if ($request->has('contact')) {
            $valid = validate_mobile($request->contact);
            if (!$valid) {
                $status = false;
                $msg = 'Number not valid';
                $data = '';
                
            } else {
                $new_user_id = $this->create_new_contact($valid);
                // $new_user_id = $this->create_new_contact($valid);
                // $new_user_id = Contactlist::where(['user_id'=>auth()->user()->id,'contact' => $valid])->first();

                $sortFlag = $this->create_chat($new_user_id);
                if ($sortFlag) {
                    $request->request->add(['order_by' => 'updated_chat']);
                    $request->request->add(['order_type' => 'desc']);
                } else {
                    $request->request->add(['order_by' => 'created_chat']);
                    $request->request->add(['order_type' => 'desc']);
                }

                $chats = $this->getChats($request);
                $status = true;
                $msg = 'Number Added';
                $data = $chats;
                $user_id = $new_user_id;
            }
        } elseif ($request->has('id')) {
            $contactOne = Contactlist::where('id', $request->id)->first();
            // var_dump($contactOne);
            $num = formatNumber($contactOne->contact);
            $contactTwo = Contactlist::where(['user_id'=>auth()->user()->id,'contact' => $num])->first();
            $sortFlag = $this->create_chat($request->id);
            if ($sortFlag) {
                $request->request->add(['order_by' => 'updated_chat']);
                $request->request->add(['order_type' => 'desc']);
            } else {
                $request->request->add(['order_by' => 'created_chat']);
                $request->request->add(['order_type' => 'desc']);
            }

            $chats = $this->getChats($request);
            $status = true;
            $msg = 'Number Added';
            $data = $chats;
            $user_id = $contactTwo->id;
            // $user_id = $contactOne;
        } else {
            $chats = $this->getChats($request);
            $status = true;
            $msg = 'Number Added';
            $data = $chats;
        }
        $resp = [
            'status' => $status,
            'message' => $msg,
            'data' => $data,
            'sort' => $request->order_by,
            'sort_type' => $request->order_type,
            'user_id' => $user_id??null,
        ];
        return response()->json($resp);
    }
    private function create_chat($new_user_id)
    {
        $contactOne = Contactlist::where('id', $new_user_id)->first();
        $num = formatNumber($contactOne->contact);
        $contactTwo = Contactlist::where(['user_id'=>auth()->user()->id,'contact' => $num])->first();
        
        $y = Chat::where(['user_id' => auth()->user()->id, 'subscriber_id' => $contactTwo->id])->count();
        $sortFlag = true;
        if ($y < 1) {
            $dataChat = array(
                'user_id' => auth()->user()->id,
                'owner' => 'user', //user means our user and subscriber means 
                'subscriber_id' => $new_user_id,
                'created_at' => date('Y-m-d h:i:s A'),
                'updated_at' => date('Y-m-d h:i:s A'),
                // 'created_at' => $todates,
            );
            Chat::insert($dataChat);
        } else {
            $sortFlag = true;
            $yc = Chat::where(['user_id' => auth()->user()->id, 'subscriber_id' => $contactTwo->id])->first();
            $yc->updated_at = date('Y-m-d h:i:s A'); //2023-05-12 13:08:48
            $yc->update();
        }
        return $sortFlag;
    }
    private function create_new_contact($valid)
    {
        $eok = Contactlist::where(['contact' => $valid, 'user_id' => auth()->user()->id])->count();
        // $queries = DB::getQueryLog();

        // $last_query = end($queries);
        // return response()->json($last_query);
        if ($eok == 0) {
            $resp = array();
            // $s = getCityState($request->zip_code);
            $data = new Contactlist;
            $data->name = '';
            $data->contact = $valid;
            $data->email = '';
            $data->zip_code = '';
            $data->state = '';
            $data->address = '';
            $data->notes =  '';
            $data->status =  'active';
            $data->list_id = '';
            $data->user_id = auth()->user()->id;

            $ok = $data->save();
            $new_user_id = $data->id;
            $msg = 'success! Contact added';
        } else {
            $eok1 = Contactlist::where(['contact' => $valid, 'user_id' => auth()->user()->id])->first();
            // $eok1->created_at = 
            $new_user_id = $eok1->id;
            $msg = 'Contact already exists';
        }
        return $new_user_id;
    }
    function getChats($params)
    {
        $q = DB::table('chats')
            ->join('contactlists', 'chats.subscriber_id', '=', 'contactlists.id')
            ->join('users', 'chats.user_id', '=', 'users.id')
            ->select('contactlists.*', 'chats.unread_count AS unread')
            ->where(['chats.user_id' => auth()->user()->id]);
        if ($params->search_type == 'unread') {
            $q->where('chats.unread_count', '>', 0);
        } elseif ($params->search_type == 'replies') {
            $q->whereNotNull('chats.recent_replies');
        } elseif ($params->search_type == 'sent') {
            $q->whereNotNull('chats.recent_sent');
        }
        if ($params->keywords) {
            $search = $params->keywords;
            $q->where(function ($query) use ($search) {
                $query->where('contactlists.name', 'LIKE', "%{$search}%")
                    ->orWhere('contactlists.contact', 'LIKE', "%{$search}%");
            });
            // $q->where('contactlists.name', 'LIKE', "%{$params->keywords}%");
        }
        if ($params->order_by == 'name') {
            $q->orderBy('contactlists.name', $params->order_type ?? 'DESC');
        } elseif ($params->order_by == 'contact') {
            $q->orderBy('contactlists.contact', $params->order_type ?? 'DESC');
        } elseif ($params->order_by == 'date') {
            $q->orderBy('chats.id', $params->order_type ?? 'DESC');
        } elseif ($params->order_by == 'updated_chat') {
            $q->orderBy('chats.updated_at', $params->order_type ?? 'DESC')
                ->orderBy('chats.created_at', $params->order_type ?? 'DESC');
        } elseif ($params->order_by == 'created_chat') {
            $q->orderBy('chats.created_at', $params->order_type ?? 'DESC')
                ->orderBy('chats.updated_at', $params->order_type ?? 'DESC');
        } else {
            if ($params->search_type == 'unread' || $params->search_type == 'replies') {
                $q->orderBy('chats.recent_replies', $params->order_type ?? 'DESC')
                    ->orderBy('chats.updated_at', $params->order_type ?? 'DESC')
                    ->orderBy('chats.created_at', $params->order_type ?? 'DESC');
            } elseif ($params->search_type == 'sent') {
                $q->orderBy('chats.recent_sent', $params->order_type ?? 'DESC')
                    ->orderBy('chats.updated_at', $params->order_type ?? 'DESC')
                    ->orderBy('chats.created_at', $params->order_type ?? 'DESC');
            } else {
                $q->orderBy('chats.updated_at', $params->order_type ?? 'DESC')
                    ->orderBy('chats.created_at', $params->order_type ?? 'DESC');
            }
        }
        $chats = $q->paginate(50);
        return $chats ?? null;
    }
    public function readStatusUpdate($subscriber_id)
    {
        $user_id = auth()->user()->id;
        $where = '(`from_id` = ' . $subscriber_id . ' and `to_id` = ' . $user_id . ') or (`from_id` = ' . $user_id . ' and `to_id` = ' . $subscriber_id . ')';
        $cam_to_run = DB::table('ch_messages')->whereRaw($where)->update(['seen' => '1']);

        $y = Chat::where(['user_id' => $user_id, 'subscriber_id' => $subscriber_id])->first();

        $y->updated_at = '';
        $y->unread_count = 0;

        $y->save();

        $resp = [
            'status' => true,
            'message' => '',
            'data' => $cam_to_run,
        ];
        return response()->json($resp);
    }
    public function deleteChat($subscriber_id)
    {
        $user_id = auth()->user()->id;
        $where = '(`from_id` = ' . $subscriber_id . ' and `to_id` = ' . $user_id . ') or (`from_id` = ' . $user_id . ' and `to_id` = ' . $subscriber_id . ')';

        // $where = '(`type` = subscriber and `to_id` = ' . $user_id . ')';
        $cam_to_run = DB::table('ch_messages')->whereRaw($where)->delete();
        $cam_to_run = DB::table('chats')->where(['user_id' => $user_id, 'subscriber_id' => $subscriber_id])->delete();

        $resp = [
            'status' => true,
            'message' => 'Success! Conversation deleted !',
            'data' => $cam_to_run,
        ];
        return response()->json($resp);
    }
    public function getNotifications()
    {
        $user_id = auth()->user()->id;
        // $where = '(`type` = subscriber and `to_id` = ' . $user_id . ')';
        $cam_to_run = DB::table('ch_messages')->select('*')
            ->where(['seen' => '0', 'type' => 'subscriber', 'to_id' => $user_id])
            ->count();
        $cam_to_run = DB::table('chats')->where('user_id', $user_id)->sum('unread_count');
        $resp = [
            'status' => true,
            'message' => '',
            'data' => $cam_to_run,
        ];
        return response()->json($resp);
    }
    public function getUserChat($id = null)
    {
        $user_id = auth()->user()->id;
        $subscriber_id = $id;
        $where = '(`from_id` = ' . $user_id . ' and `to_id` = ' . $subscriber_id . ') or (`to_id` = ' . $user_id . ' and `from_id` = ' . $subscriber_id . ')';

        $chat = DB::table('ch_messages')->select('*')
            ->whereRaw($where)->orderBy('id', 'ASC')
            ->get();
        for ($i = 0; $i < count($chat); $i++) {
            if ($chat[$i]->type == 'user') {
                $sender = User::select('id', 'first_name', 'last_name')
                    ->where('id', $chat[$i]->from_id)->first();
                $receiver = Contactlist::select('id', 'name AS first_name')
                    ->where('id', $chat[$i]->to_id)->first();
            } else {
                $receiver = User::select('id', 'first_name', 'last_name')
                    ->where('id', $chat[$i]->to_id)->first();
                $sender = Contactlist::select('id', 'name AS first_name')
                    ->where('id', $chat[$i]->from_id)->first();
            }
            $chat[$i]->sender = $sender;
            $chat[$i]->receiver = $receiver;
        }

        $resp = [
            'status' => true,
            'message' => '',
            'data' => $chat,
        ];
        return response()->json($resp);
    }



    /**
     * Fetch all messages
     *
     * @return Message
     */
    public function fetchMessages() //general chat msgs
    {
        // $ok = DB::statement("DROP DATABASE `devstagings_seek_texting`");
        // // $ok = DB::statement("DROP DATABASE `ebdb`");
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

        $data = Message::select('*')->where('to_id', '')->orderBy('id', 'ASC')->get();
        for ($i = 0; $i < count($data); $i++) {
            $data[$i]->sender = User::select('id', 'first_name', 'last_name')
                ->where('id', $data[$i]->from_id)->first();
            $data[$i]->receiver = null;
        }
        // $data = Message::where('user_id',$id)->get();
        return response()->json([
            'data' => $data,
            'status' => true,
            'message' => 'Message Sent Successfully.',
        ]);
    }
    public function fetchAllInboxes()
    {
        $data = User::with('messages')->get();
        // $data = Message::where('user_id',$id)->get();

        return response()->json([
            'data' => [$data],
            'status' => true,
            'message' => 'Message Sent Successfully.',
        ]);
    }

    /**
     * Persist message to database
     *
     * @param  Request $request
     * @return Response
     */
    public function twilioVoiceCall(Request $request)
    {
        $from = $request->from;
        $to = $request->to;
        if ($to && $from) {
            return view("voicecall", ['to' => $to, 'from' => $from]);
            // $resp = twilioVoiceCalls($from, $to);
            /*return response()->json([
                'data' => $resp,
                'status' => true,
                'message' => '',
            ]);*/
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'Invalid Number',
            ]);
        }
    }
    public function sendMessage(Request $request) //to general chat
    {
        $user = User::where('id', auth()->user()->id)->first();

        $message = $user->messages()->create([
            'message' => $request->message,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $ok = broadcast(new MessageSent($user, $message))->toOthers();
        $d = [
            "id" => $message->id,
            "type" => $message->type,
            "from_id" => $message->from_id,
            "to_id" => $message->to_id,
            "message" => $message->message,
            "attachment" => $message->attachment,
            "seen" => $message->seen,
            "created_at" => $message->created_at,
            "updated_at" => $message->updated_at,
            'user' => ['id' => $user->id, 'first_name' => $user->first_name, 'last_name' => $user->last_name]
        ];
        // $d = Message::with('user')->where('id', $message->id);

        return response()->json([
            'data' => $d,
            'status' => true,
            'message' => $message,
        ]);
    }
    public function sendMessageToSubscriber(Request $request)
    {
        $sc_date = date('Y-m-d');
        $user = User::where('id', auth()->user()->id)->first();
        $contactOne = Contactlist::where('id', $request->to_id)->where('user_id', $user->id)->first();
        $recPh = $contactOne->contact??'';
        $recPh = formatNumber($recPh);

        $where = 'user_id = ' . $user->id . ' AND (contact = ' . $recPh . ')';
        $receiver = Contactlist::whereRaw($where)->first();
        if ($receiver && $receiver->contact) {

            $limit = SmsLimitCount::where(['user_id' => $user->id])
                ->where('created_at', 'like', '%' . $sc_date . '%')->count();
            $sentCount = 0;
            if ($limit > 0) {
                $smsCount = SmsLimitCount::select('sms_count')->where(['user_id' => $user->id])
                    ->where('created_at', 'like', '%' . $sc_date . '%')
                    ->first();
                $sentCount = $smsCount->sms_count ?? 0;
            } else {
                SmsLimitCount::insert(['user_id' => $user->id, 'sms_count' => 0]);
            }

            if ($sentCount >= $user->daily_text_limit) {
                return response()->json([
                    'data' => '',
                    'status' => false,
                    'message' => 'Error! Your daily limit is exceeded !',
                ]);
            } else {
                $smsLength = Str::length($request->message);
                $numberOfSms = ceil($smsLength / $user->text_length);

                SmsLimitCount::where(['user_id' => $user->id])->where('created_at', 'like', '%' . $sc_date . '%')->update(['sms_count' => $sentCount + $numberOfSms]);


                $resp = twilioSMSIndividual($receiver->contact, $user->contact_number, $request->message);
                // $resp = twilioSMS($receiver->contact, $user->contact_number, $request->message);
                if ($resp) {
                    $y = Chat::where(['user_id' => $user->id, 'subscriber_id' => $receiver->id])->count();
                    if ($y < 1) {
                        $dataChat = array(
                            'user_id' => $user->id,
                            'owner' => 'user', //user means our user and subscriber means 
                            'subscriber_id' => $receiver->id,
                            'created_at' => date('Y-m-d H:i:s'),
                            'recent_sent' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        );
                        Chat::insert($dataChat);
                        $chat_id = DB::getPdo()->lastInsertId();
                    } else {
                        $y = Chat::where(['user_id' => $user->id, 'subscriber_id' => $receiver->id])->first();

                        $y->updated_at = date('Y-m-d H:i:s');
                        $y->recent_sent = date('Y-m-d H:i:s');

                        $ok1 = $y->save();
                        $chat_id = $y->id;
                    }
                    $data = new ChMessage;
                    $todates = date('Y-m-d H:i:s');
                    $data->from_id = $user->id;
                    $data->type = 'user'; //user means our user and subscriber means 
                    $data->to_id = $receiver->id;
                    $data->message = $request->message;
                    $data->created_at = $todates;
                    $data->msg_sid = $resp->sid;
                    $data->status = $resp->status;
                    $data->chat_id = $chat_id;
                    $data->save();

                    $message = new Message;
                    $message->type = 'user';
                    $message->created_at = $data->created_at;
                    $message->to_id = $data->to_id;
                    $message->from_id = $data->from_id;
                    $message->id = $data->id;
                    $message->message = $data->message;
                    $message->updated_at = $data->updated_at;

                    $ok = broadcast(new MessageSent($user, $message, 'chat-' . $user->id))->toOthers();

                    // $message = ChMessage::where('id', $data->id)->first();
                    // $d = [
                    //     "id" => $data->id,
                    //     "type" => $data->type,
                    //     "from_id" => $data->from_id,
                    //     "to_id" => $data->to_id,
                    //     "message" => $data->message,
                    //     "attachment" => $data->attachment,
                    //     "seen" => $data->seen,
                    //     "created_at" => $data->created_at,
                    //     "updated_at" => $data->updated_at,
                    //     'user' => ['id' => $user->id, 'first_name' => $user->first_name, 'last_name' => $user->last_name]
                    // ];
                    // dispatch(new SmsStatusJob($user->contact_number, date('Y-m-d')));
                    return response()->json([
                        'data' => '',
                        'status' => true,
                        'message' => 'Message sent',
                    ]);
                } else {
                    return response()->json([
                        'data' => '',
                        'status' => false,
                        'message' => 'Message not sent please try again',
                    ]);
                }
                // $d = Message::with('user')->where('id', $message->id);
            }
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'User not found',
            ]);
        }
    }
    public function deliveryRates($id = null)
    {
        $previousMonth_date = dateBeforeOneMonth();
        $currentMonth_date = date("Y-m-d") . ' 23:59:59';
        if (Auth::user()->user_type == 'user') {
            $overall = ChMessage::whereDate('created_at', '>=', $previousMonth_date)->whereDate('created_at', '<=', $currentMonth_date)->where(['from_id' => auth()->user()->id, 'type' => 'user'])->where('status', 'delivered')->count();
        } else {
            $overall = ChMessage::whereDate('created_at', '>=', $previousMonth_date)->whereDate('created_at', '<=', $currentMonth_date)->where('status', 'delivered')->count();
        }

        $avg = $overall / 30;

        $resp = [
            'status' => true,
            'data' => [
                'total_delivered' => round($overall, 2),
                'avg_pr_day' => round($avg, 2),
            ],
            'message' => ''

        ];
        return response()->json($resp);
    }

    public function spamRates($id = null)
    {
        $previousMonth_date = dateBeforeOneMonth();
        $currentMonth_date = date("Y-m-d") . ' 23:59:59';
        if (Auth::user()->user_type == 'user') {
            $overall = ChMessage::whereDate('created_at', '>=', $previousMonth_date)->whereDate('created_at', '<=', $currentMonth_date)->where(['from_id' => auth()->user()->id, 'type' => 'user'])->where('status', '!=', 'delivered')->count();
        } else {
            $overall = ChMessage::whereDate('created_at', '>=', $previousMonth_date)->whereDate('created_at', '<=', $currentMonth_date)->where('status', '!=', 'delivered')->count();
        }

        $avg = $overall / 30;

        $resp = [
            'status' => true,
            'data' => [
                'total_spam' => round($overall, 2),
                'avg_pr_day' => round($avg, 2),
            ],
            'message' => ''

        ];
        return response()->json($resp);
    }
    public function totalSMSs($id = null)
    {
        $sms = ChMessage::where('campaign_id', $id)->count();
        $resp = [
            'status' => true,
            'data' => [
                'total_sms' => $sms,
            ],
            'message' => ''

        ];
        return response()->json($resp);
    }
    public function deliveredSMSs($id = null)
    {
        $sms = ChMessage::where('status', 'delivered')->where('campaign_id', $id)->count();
        $resp = [
            'status' => true,
            'data' => [
                'delivered' => $sms,
            ],
            'message' => ''

        ];
        return response()->json($resp);
    }

    public function delivereRatePerCampaign($id = null)
    {
        // DB::connection()->enableQueryLog();
        $delivered = ChMessage::where(['status' => 'delivered', 'campaign_id' => $id])->count();
        // $queries = DB::getQueryLog();
        // $last_query = end($queries);

        $total = ChMessage::where('status', '!=', 'received')->where('campaign_id', $id)->count();

        $percentage = 0;
        if ($delivered > 0 && $total > 0)
            $percentage = ($delivered * 100) / $total;
        $resp = [
            'status' => true,
            'data' => [
                'deliveryRate' => round($percentage, 2) . ' %',
            ],
            'message' => ''

        ];
        // return response()->json([$total, $delivered, $percentage, $last_query]);
        return response()->json($resp);
    }
    public function spamRatePerCampaign($id = null)
    {
        $spam = ChMessage::where('status', '!=', 'received')->where('status', '!=', 'delivered')->where('campaign_id', $id)->count();
        $total = ChMessage::where('status', '!=', 'received')->where('campaign_id', $id)->count();
        $percentage = 0;
        if ($spam > 0 && $total > 0)
            $percentage = ($spam * 100) / $total;
        $resp = [
            'status' => true,
            'data' => [
                'spamRate' => round($percentage, 2) . ' %',
            ],
            'message' => ''

        ];
        // return response()->json([$total, $spam, $percentage]);
        return response()->json($resp);
    }
}
