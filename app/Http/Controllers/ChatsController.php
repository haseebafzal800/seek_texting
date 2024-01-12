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

use App\Models\ChMessage;
use DateTime;


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
    public function getStatisticsMonthWise()
    {
        // $sql = 'SELECT DATE_FORMAT(created_at, "%b") AS month, COUNT(id) as total
        // FROM campaigns WHERE created_at <= NOW() AND created_at >= Date_add(Now(),interval - 12 month)
        // GROUP BY DATE_FORMAT(created_at, "%m-%Y")';
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
        $data = DB::select($sql);
        $resp = [
            'data' => $data,
            'status' => true,
            'message' => '',
        ];
        return response()->json($resp);
    }

    public function getNotifications()
    {
        $user_id = auth()->user()->id;
        // $where = '(`type` = subscriber and `to_id` = ' . $user_id . ')';
        // ->whereRaw($where)
        $cam_to_run = DB::table('ch_messages')->select('*')->where(['seen' => '0', 'type' => 'subscriber', 'to_id' => $user_id])->count();
        $resp = [
            'status' => true,
            'message' => '',
            'data' => $cam_to_run,
        ];
        return response()->json($resp);
    }
    /*public function getAllChatsByUser($user_id = null) //chats with subscribers
    {
        $chats = DB::table('contactlists')
            ->join('chats', 'chats.subscriber_id', '=', 'contactlists.id')
            ->join('users', 'chats.user_id', '=', 'users.id')
            ->select('contactlists.*')
            ->where(['chats.user_id' => $user_id])
            ->where(['contactlists.is_deleted' => '0'])->get();


        $reurn = array();
        // $a = '';
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
        ];
        return response()->json($resp);
    }*/
    public function getAllChatsByUser($user_id, $order_by = 'desc') //chats with subscribers
    {
        // DB::connection()->enableQueryLog();
        // $queries = DB::getQueryLog();
        // $last_query = end($queries);
        $chats = DB::table('contactlists')
            ->join('chats', 'chats.subscriber_id', '=', 'contactlists.id')
            ->join('users', 'chats.user_id', '=', 'users.id')
            ->select('contactlists.*')
            ->where(['chats.user_id' => $user_id])
            // ->orderBy('contactlists.name', 'ASC')
            ->orderBy('chats.updated_at', 'DESC')
            ->get();
        if ($order_by == 'asc') {
            $chats = DB::table('contactlists')
                ->join('chats', 'chats.subscriber_id', '=', 'contactlists.id')
                ->join('users', 'chats.user_id', '=', 'users.id')
                ->select('contactlists.*')
                ->where(['chats.user_id' => $user_id])
                ->orderBy('contactlists.name', 'ASC')
                // ->orderBy('chats.updated_at', 'DESC')
                ->get();
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
    public function readStatusUpdate($subscriber_id)
    {
        // DB::enableQueryLog();
        $user_id = auth()->user()->id;
        $where = '(`from_id` = ' . $subscriber_id . ' and `to_id` = ' . $user_id . ') or (`from_id` = ' . $user_id . ' and `to_id` = ' . $subscriber_id . ')';
        $cam_to_run = DB::table('ch_messages')->whereRaw($where)->update(['seen' => 1]);
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
        $resp = twilioVoiceCalls($from, $to);
        return response()->json([
            'data' => $resp,
            'status' => true,
            'message' => '',
        ]);
    }
    public function sendMessage(Request $request) //to general chat
    {
        $user = User::where('id', auth()->user()->id)->first();

        $message = $user->messages()->create([
            'message' => $request->message
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
        $user = User::where('id', auth()->user()->id)->first();
        $receiver = Contactlist::where('id', $request->to_id)->first();
        $resp = twilioSMS($receiver->contact, $user->contact, $request->message);
        if ($resp) {
            $y = Chat::where(['user_id' => $user->id, 'subscriber_id' => $receiver->id])->count();
            if ($y < 1) {
                $dataChat = array(
                    'user_id' => $user->id,
                    'owner' => 'user', //user means our user and subscriber means 
                    'subscriber_id' => $receiver->id,
                    'created_at' => $todates,
                );
                Chat::insert($dataChat);
            }
            $data = new ChMessage;
            $todates = date('Y-m-d h:i:s');
            $data->from_id = $user->id;
            $data->type = 'user'; //user means our user and subscriber means 
            $data->to_id = $receiver->id;
            $data->message = $request->message;
            $data->created_at = $todates;
            $data->msg_sid = $resp->sid;
            $data->status = $resp->status;
            $data->save();

            $last_id =  $data->id;
            $m = ChMessage::where('id', $data->id)->first();
            $message = new Message;
            $message->type = 'user';
            $message->created_at = $m->created_at;
            $message->to_id = $m->to_id;
            $message->from_id = $m->from_id;
            $message->id = $m->id;
            $message->message = $m->message;
            $message->updated_at = $m->updated_at;
            $ok = broadcast(new MessageSent($user, $message, 'chat-' . $user->id))->toOthers();



            // $message = ChMessage::where('id', $data->id)->first();
            $d = [
                "id" => $data->id,
                "type" => $data->type,
                "from_id" => $data->from_id,
                "to_id" => $data->to_id,
                "message" => $data->message,
                "attachment" => $data->attachment,
                "seen" => $data->seen,
                "created_at" => $data->created_at,
                "updated_at" => $data->updated_at,
                'user' => ['id' => $user->id, 'first_name' => $user->first_name, 'last_name' => $user->last_name]
            ];
            return response()->json([
                'data' => $d,
                'status' => true,
                'message' => $ok,
            ]);
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'Message not sent pleasetry again',
            ]);
        }
        // $d = Message::with('user')->where('id', $message->id);


    }
    public function deliveryRates($id = null)
    {
        $previousMonth_date = date("Y-m", strtotime("-1 month"));
        $currentMonth_date = date("Y-m");
        $avg = 0;
        $days = 0;
        $percentage = 0;
        $overall = 0;
        $datediff = 0;
        $prevAvg = 0;

        if (Auth::user()->user_type == 'user') {
            $fdate = ChMessage::where(['from_id' => auth()->user()->id, 'type' => 'user'])->select('created_at')->orderBy('id', 'asc')->where('status', 'delivered')->first();
            $tdate = ChMessage::where(['from_id' => auth()->user()->id, 'type' => 'user'])->select('created_at')->orderBy('id', 'desc')->where('status', 'delivered')->first();
            $overall = ChMessage::where(['from_id' => auth()->user()->id, 'type' => 'user'])->where('status', 'delivered')->count();
            $previousMonth = ChMessage::where(['from_id' => auth()->user()->id, 'type' => 'user'])->where('created_at', 'like', '%' . $previousMonth_date . '%')
                ->where('status', 'delivered')
                ->count();
        } else {
            $fdate = ChMessage::select('created_at')->orderBy('id', 'asc')->where('status', 'delivered')->first();
            $tdate = ChMessage::select('created_at')->orderBy('id', 'desc')->where('status', 'delivered')->first();
            $overall = ChMessage::where('status', 'delivered')->count();
            $previousMonth = ChMessage::where('created_at', 'like', '%' . $previousMonth_date . '%')
                ->where('status', 'delivered')
                ->count();
        }

        if ($fdate && $tdate) {
            $datetime1 = strtotime($fdate->created_at);
            $datetime2 = strtotime($tdate->created_at);

            $datediff = $datetime1 - $datetime2;

            $days = round($datediff / (60 * 60 * 24));
        }

        if ($days > 0)
            $avg = $overall / $days;

        $previousMonthDays = date("t", mktime(0, 0, 0, date("n") - 1));
        if ($previousMonth) {
            $prevAvg = $previousMonth / $previousMonthDays;
        }
        if ($avg > 0)
            $percentage = ($prevAvg * 100) / $avg;

        $resp = [
            'status' => true,
            'data' => [
                'total_delivered' => round($overall, 2),
                'avg_pr_day' => round($avg, 2),
                'percentage' => round($percentage, 2),
            ],
            'message' => ''

        ];
        return response()->json($resp);
    }

    public function spamRates($id = null)
    {
        $previousMonth_date = date("Y-m", strtotime("-1 month"));
        $currentMonth_date = date("Y-m");
        $avg = 0;
        $days = 0;
        $percentage = 0;
        $overall = 0;
        $datediff = 0;
        $prevAvg = 0;

        if (Auth::user()->user_type == 'user') {
            $fdate = ChMessage::where(['from_id' => auth()->user()->id, 'type' => 'user'])->select('created_at')->orderBy('id', 'asc')->where('status', '!=', 'delivered')->where('status', '!=', 'received')->first();
            $tdate = ChMessage::where(['from_id' => auth()->user()->id, 'type' => 'user'])->select('created_at')->orderBy('id', 'desc')->where('status', '!=', 'delivered')->where('status', '!=', 'received')->first();
            $overall = ChMessage::where(['from_id' => auth()->user()->id, 'type' => 'user'])->where('status', '!=', 'delivered')->where('status', '!=', 'received')->count();
            $previousMonth = ChMessage::where(['from_id' => auth()->user()->id, 'type' => 'user'])->where('created_at', 'like', '%' . $previousMonth_date . '%')
                ->where('status', '!=', 'delivered')->where('status', '!=', 'received')
                ->count();
        } else {
            $fdate = ChMessage::select('created_at')->orderBy('id', 'asc')->where('status', '!=', 'delivered')->where('status', '!=', 'received')->first();
            $tdate = ChMessage::select('created_at')->orderBy('id', 'desc')->where('status', '!=', 'delivered')->where('status', '!=', 'received')->first();
            $overall = ChMessage::where('status', '!=', 'delivered')->count();
            $previousMonth = ChMessage::where('created_at', 'like', '%' . $previousMonth_date . '%')
                ->where('status', '!=', 'delivered')->where('status', '!=', 'received')
                ->count();
        }

        if ($fdate && $tdate) {
            $datetime1 = strtotime($fdate->created_at);
            $datetime2 = strtotime($tdate->created_at);
            $datediff = $datetime1 - $datetime2;
            $days = round($datediff / (60 * 60 * 24));
        }
        if ($days > 0)
            $avg = $overall / $days;


        $previousMonthDays = date("t", mktime(0, 0, 0, date("n") - 1));
        if ($previousMonth) {
            $prevAvg = $previousMonth / $previousMonthDays;
        }

        if ($avg > 0)
            $percentage = ($prevAvg * 100) / $avg;

        $resp = [
            'status' => true,
            'data' => [
                'total_spam' => round($overall, 2),
                'avg_pr_day' => round($avg, 2),
                'percentage' => round($percentage, 2),
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
    public function deleteChat($subscriber_id)
    {
        $user_id = auth()->user()->id;
        $where = '(`from_id` = ' . $subscriber_id . ' and `to_id` = ' . $user_id . ') or (`from_id` = ' . $user_id . ' and `to_id` = ' . $subscriber_id . ')';
        DB::table('ch_messages')->whereRaw($where)->delete();
        DB::table('chats')->where(['user_id' => $user_id, 'subscriber_id' => $subscriber_id])->delete();
        $resp = [
            'status' => true,
            'data' => '',
            'message' => 'Chat deleted'

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
