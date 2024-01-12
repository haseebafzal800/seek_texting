<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Campaigns;
use App\Models\Campaign_notsend_to;
use App\Models\Campaign_send_to;
use App\Models\Campaign_send_to_list;
use App\Models\Campaign_detail;
use App\Jobs\SendSmsJob;
// use Artisan;
use DB;
use Carbon\CarbonPeriod;

class CampaignsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

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
        FROM campaigns
        WHERE created_at <= NOW() and created_at >= Date_add(Now(),interval -12 month)
        GROUP BY DATE_FORMAT(created_at, "%m-%Y")) as sub';
        } else {
            $user_id = auth()->user()->id;
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
        FROM campaigns
        WHERE created_at <= NOW() and created_at >= Date_add(Now(),interval -12 month)  and user_id = ' . $user_id . '
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
    public function index()
    {
        $resp = array();
        $d = Campaigns::select('*')->with('user', function ($q) {
            return $q->select('id', 'first_name', 'last_name');
        })->where(['user_id' => auth()->user()->id])->get();
        if ($d) {
            $resp = [
                'data' => $d,
                'status' => true,
                'message' => '',
            ];
        } else {
            $resp = [
                'data' => '',
                'status' => true,
                'message' => 'No Data found',
            ];
        }
        return response()->json($resp);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function runCampaignNow($id = '')
    {
        $cam_to_run = DB::table('campaign_details')
            ->join('campaigns', 'campaign_details.campaign_id', '=', 'campaigns.id')
            ->select('campaign_details.*', 'campaigns.id AS campaigns_id', 'campaigns.title', 'campaigns.message', 'campaigns.user_id', 'campaigns.campaign_send_to_emails', 'campaigns.campaign_not_send_to_emails', 'campaigns.campaign_send_to_list_ids')
            ->where('campaign_details.id', $id)
            ->first();

        // return $cam_to_run;
        if ($cam_to_run) {
            $statusData = ['status' => 'success'];
            $do = sendSMS($cam_to_run);
            if ($do != 'limit') {
                $statusData = ['status' => 'success'];
            } else {
                $statusData = ['status' => 'cancel'];
            }
            DB::table('campaign_details')->where('id', $cam_to_run->id)->update($statusData);
            DB::table('campaigns')->where('id', $cam_to_run->campaigns_id)->update($statusData);


            return $do;
        }
    }

    public function funcName($val = null)
    {
        return $val ?? null;
    }
    public function store(Request $request)
    {
        ini_set('max_execution_time', '600');
        $resp = array();
        // DB::beginTransaction();
        try {
            $tags = NULL;
            $campaign_send_to_emails = NULL;
            $campaign_not_send_to_emails = NULL;
            $campaign_send_to_list_ids = NULL;
            if ($request->has('tags')) {
                $tags = implode(',', $request->tags);
            }
            if ($request->has('campaign_send_to_emails')) {
                $campaign_send_to_emails = implode(',', $request->campaign_send_to_emails);
            }
            if ($request->has('campaign_not_send_to_emails')) {
                $campaign_not_send_to_emails = implode(',', $request->campaign_not_send_to_emails);
            }
            if ($request->has('campaign_send_to_list_ids')) {
                $campaign_send_to_list_ids = implode(',', $request->campaign_send_to_list_ids);
            }
            $status = 'pending';
            // if ($request->send_now && $request->send_now == 'true') {
            //     $status = 'success';
            // }
            $data = new Campaigns;
            $data->title = $request->title;
            $data->type = $request->type; //sms OR email
            $data->status = 'pending'; //pending, success or cancel
            $data->campaign_interval = $request->campaign_interval ?? 'daily'; //it may be daily, weekly, monthly or annually 
            $data->send_now = $request->send_now ?? 'false'; //it may be true or false 
            $data->campaign_start_time = $request->campaign_start_time; //when to run send i.e. 10:00, 16:30, etc. 
            $data->campaign_start_date = $request->campaign_start_date; //from which date campaign has to be run
            $data->campaign_end_date = $request->campaign_end_date; //campaign end date
            $data->campaign_time_zone = $request->campaign_time_zone; //according to which time zone campaign has to be run
            $data->tags = $tags; //comma separated tags
            $data->subject_line = $request->subject_line; // for email campaign
            $data->preview_text = $request->preview_text; // for email campaign
            $data->message = $request->message; // formated address
            $data->sender_name = $request->sender_name; //for email campaign
            $data->sender_email = $request->sender_email ?? ''; // for email campaign
            $data->user_id = auth()->user()->id;
            $data->sender_email_as_reply_to = $request->sender_email_as_reply_to ?? 'no'; // for email campaign
            $data->campaign_send_to_emails = $campaign_send_to_emails;
            $data->campaign_send_to_list_ids = $campaign_send_to_list_ids;
            $data->campaign_not_send_to_emails = $campaign_not_send_to_emails;
            $data->save();
            $last_id = null;
            if ($request->send_now && $request->send_now == 'true') {
                $details = array('campaign_id' => $data->id, 'status' => 'pending', 'schedule_date' => $request->campaign_start_date . ' ' . $request->campaign_start_time);
                Campaign_detail::insert($details);
                $last_id = DB::getPdo()->lastInsertId();
                dispatch(new SendSmsJob(null, $last_id, null));
                // \Artisan::call(queue: work);
                // Artisan::call('queue:work --stop-when-empty');


                // dispatch(new SendSmsJob($from, $last_id, null));
                // $ok = $this->runCampaignNow($last_id);
                $resp = [
                    'data' => $data->id,

                    'status' => true,
                    'message' => 'Your campaign is in queue',
                ];
                // Campaign_detail::insert($details);
                // $last_id = DB::getPdo()->lastInsertId();
                // $ok = $this->runCampaignNow($last_id);

                // if ($ok == 'limit') {
                //     $resp = [
                //         'data' => $data->id,
                //         'detail' => $ok,
                //         'status' => true,
                //         'message' => 'Campaign is created but not running as your daily text limit exceeded',
                //     ];
                // } else {
                //     $resp = [
                //         'data' => $data->id,
                //         'detail' => $ok,
                //         'status' => true,
                //         'message' => 'Data added successfully',
                //     ];
                // }
                // return response()->json($resp);
            } else {
                $campaign_start_date = $request->campaign_start_date;
                $campaign_end_date = $request->campaign_end_date;
                if ($request->campaign_interval == 'one time') {
                    $campaign_end_date = $campaign_start_date;
                }

                $period = CarbonPeriod::create($campaign_start_date, $campaign_end_date);

                $dates = [];
                $mult = 0;
                foreach ($period as $date) {

                    if ($mult == 0) {
                        $d = $date->format('Y-m-d');
                    } else {

                        if ($request->campaign_interval == 'weekly') {
                            $d = date('Y-m-d', strtotime("+1 week", strtotime($dates[$mult - 1])));
                        } elseif ($request->campaign_interval == 'monthly') {
                            $d = date('Y-m-d', strtotime("+1 month", strtotime($dates[$mult - 1])));
                        } elseif ($request->campaign_interval == 'annually') {
                            $d = date('Y-m-d', strtotime("+1 year", strtotime($dates[$mult - 1])));
                        } else {
                            $d = $date->format('Y-m-d');
                        }
                    }
                    if ($d > $campaign_end_date)
                        break;
                    $dates[] = $d;
                    $mult++;
                }
                foreach ($dates as $dkey) {
                    $details[] = array('campaign_id' => $data->id, 'status' => 'pending', 'schedule_date' => $dkey . ' ' . $request->campaign_start_time);
                }
                Campaign_detail::insert($details);
                $resp = [
                    'data' => $data->id,
                    // 'detail' => $ok,
                    'status' => true,
                    'message' => 'Data added successfully',
                ];
            }

            // DB::commit();

        } catch (Exception $e) {
            // DB::rollback();
            $resp = [
                'data' => '',
                'status' => false,
                'message' => 'Data added fail',
            ];
        }
        return response()->json($resp);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $d = Campaigns::where(['id' => $id])->first();


        if ($d) {

            $d->tags = ($d->tags ? explode(',', $d->tags) : array());
            $d->campaign_send_to_emails = ($d->campaign_send_to_emails ? explode(',', $d->campaign_send_to_emails) : array());
            $d->campaign_not_send_to_emails = ($d->campaign_not_send_to_emails ? explode(',', $d->campaign_not_send_to_emails) : array());
            $d->campaign_send_to_list_ids = ($d->campaign_send_to_list_ids ? explode(',', $d->campaign_send_to_list_ids) : array());
            return response()->json([
                'data' => $d,
                'status' => true,
                'message' => '',
            ]);
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'data not found',
            ]);
        }
    }

    public function getCampaignLists($id)
    {
        $d = Campaign_send_to_list::select('*')->with([
            'lists' => function ($q) {
                return $q->select('id', 'name');
            }
        ])->where('campaign_id', $id)->get();
        if ($d) {
            return response()->json([
                'data' => $d,
                'status' => true,
                'message' => '',
            ]);
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'data not found',
            ]);
        }
    }

    public function getCampaignSendTo($id)
    {
        $d = Campaign_send_to::where(['campaign_id' => $id])->get();
        if ($d) {
            return response()->json([
                'data' => $d,
                'status' => true,
                'message' => '',
            ]);
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'data not found',
            ]);
        }
    }

    public function getCampaignNotSendTo($id)
    {
        $d = Campaign_notsend_to::where(['campaign_id' => $id])->get();
        if ($d) {
            return response()->json([
                'data' => $d,
                'status' => true,
                'message' => '',
            ]);
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'data not found',
            ]);
        }
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $ok = Campaigns::where(['id' => $request->id])->first();
        if ($ok) {

            // DB::beginTransaction();
            try {
                $tags = NULL;
                $campaign_send_to_emails = NULL;
                $campaign_not_send_to_emails = NULL;
                $campaign_send_to_list_ids = NULL;
                if ($request->has('tags')) {
                    $tags = implode(',', $request->tags);
                }
                if ($request->has('campaign_send_to_emails')) {
                    $campaign_send_to_emails = implode(',', $request->campaign_send_to_emails);
                }
                if ($request->has('campaign_not_send_to_emails')) {
                    $campaign_not_send_to_emails = implode(',', $request->campaign_not_send_to_emails);
                }
                if ($request->has('campaign_send_to_list_ids')) {
                    $campaign_send_to_list_ids = implode(',', $request->campaign_send_to_list_ids);
                }
                // $data = new Campaigns;
                $data = array();
                if ($request->has('title'))
                    $data['title'] = $request->title;
                if ($request->has('type'))
                    $data['type'] = $request->type; //sms OR email
                if ($request->has('status'))
                    $data['status'] = $request->status ?? 'pending'; //pending, success or cancel
                if ($request->has('campaign_interval'))
                    $data['campaign_interval'] = $request->campaign_interval ?? 'daily'; //it may be daily, weekly, monthly or annually 
                if ($request->has('send_now'))
                    $data['send_now'] = $request->send_now; //it may be daily, weekly, monthly or annually 
                if ($request->has('campaign_start_time'))
                    $data['campaign_start_time'] = $request->campaign_start_time; //when to run send i.e. 10:00, 16:30, etc. 
                if ($request->has('campaign_start_date'))
                    $data['campaign_start_date'] = $request->campaign_start_date; //from which date campaign has to be run
                if ($request->has('campaign_end_date'))
                    $data['campaign_end_date'] = $request->campaign_end_date; //campaign end date
                if ($request->has('campaign_time_zone'))
                    $data['campaign_time_zone'] = $request->campaign_time_zone; //according to which time zone campaign has to be run
                if ($request->has('tags'))
                    $data['tags'] = $tags; //comma separated tags
                if ($request->has('subject_line'))
                    $data['subject_line'] = $request->subject_line; // for email campaign
                if ($request->has('preview_text'))
                    $data['preview_text'] = $request->preview_text; // for email campaign
                if ($request->has('message'))
                    $data['message'] = $request->message; // formated address
                if ($request->has('sender_name'))
                    $data['sender_name'] = $request->sender_name; //for email campaign
                if ($request->has('sender_email'))
                    $data['sender_email'] = $request->sender_email; // for email campaign
                if ($request->has('user_id'))
                    $data['user_id'] = auth()->user()->id;
                if ($request->has('sender_email_as_reply_to'))
                    $data['sender_email_as_reply_to'] = $request->sender_email_as_reply_to ?? 'no'; // for email campaign
                if ($request->has('campaign_send_to_emails'))
                    $data['campaign_send_to_emails'] = $campaign_send_to_emails;
                if ($request->has('campaign_send_to_list_ids'))
                    $data['campaign_send_to_list_ids'] = $campaign_send_to_list_ids;
                if ($request->has('campaign_not_send_to_emails'))
                    $data['campaign_not_send_to_emails'] = $campaign_not_send_to_emails;

                $done = Campaigns::where('id', $ok->id)->update($data);

                // if($done){
                if ($request->send_now || $request->has('campaign_interval') || $request->has('campaign_start_date') || $request->has('campaign_end_date')) {

                    Campaign_detail::where('campaign_id', $ok->id)->delete();
                    if ($request->send_now && $request->send_now == 'true') {
                        $details = array('campaign_id' => $ok->id, 'status' => 'success', 'schedule_date' => $request->campaign_start_date . ' ' . $request->campaign_start_time);
                        $last_id = DB::getPdo()->lastInsertId();
                        if ($ok->send_now == 'false') { //if campaign already not done then it will run 
                            $this->runCampaignNow($last_id);
                        }
                    } else {
                        $start_date = $request->campaign_start_date;
                        $end_date = $request->campaign_end_date;
                        $interval = $request->campaign_interval;
                        if ($interval == 'one time')
                            $end_date = $request->campaign_start_date;

                        $period = CarbonPeriod::create($start_date, $end_date);

                        $dates = [];
                        $mult = 0;
                        foreach ($period as $date) {

                            if ($mult == 0) {
                                $d = $date->format('Y-m-d');
                            } else {

                                if ($interval == 'weekly') {
                                    $d = date('Y-m-d', strtotime("+1 week", strtotime($dates[$mult - 1])));
                                } elseif ($interval == 'monthly') {
                                    $d = date('Y-m-d', strtotime("+1 month", strtotime($dates[$mult - 1])));
                                } elseif ($interval == 'annually') {
                                    $d = date('Y-m-d', strtotime("+1 year", strtotime($dates[$mult - 1])));
                                } else {
                                    $d = $date->format('Y-m-d');
                                }
                            }
                            if ($d > $end_date)
                                break;
                            $dates[] = $d;
                            $mult++;
                        }
                        foreach ($dates as $dkey) {
                            $details[] = array('campaign_id' => $ok->id, 'status' => 'pending', 'schedule_date' => $dkey . ' ' . ($request->campaign_start_time ?? $ok->campaign_start_time));
                        }


                        Campaign_detail::insert($details);
                    }
                }

                // DB::commit();

                return response()->json([
                    'data' => '',
                    'status' => true,
                    'message' => 'Data updated successfully',
                ]);
            } catch (Exception $e) {
                // DB::rollback();
                return response()->json([
                    'data' => '',
                    'status' => false,
                    'message' => 'Data added fail',
                ]);
            }
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'Data not found',
            ]);
        }
    }
    public function updateLists(Request $request)
    {
        $ok = Campaigns::where(['id' => $request->campaign_id])->first();
        if ($ok) {
            DB::beginTransaction();
            try {
                Campaign_send_to_list::where('id', $request->campaign_id)->delete();
                if ($request->has('send_to_list_id')) {
                    $sendToArr = array();
                    foreach ($request->send_to_list_id as $sendToListkey) {
                        $sendToArr[] = [
                            'campaign_id' => $request->campaign_id,
                            'list_id' => $sendToListkey
                        ];
                    }
                    Campaign_send_to_list::insert($sendToArr);
                    return response()->json([
                        'data' => '',
                        'status' => true,
                        'message' => 'Data Update Success',
                    ]);
                }
                DB::commit();
            } catch (Exception $e) {
                DB::rollback();
                return response()->json([
                    'data' => '',
                    'status' => false,
                    'message' => 'Data Update fail',
                ]);
            }
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'Data not found',
            ]);
        }
    }

    public function updateCampaignSendTo(Request $request)
    {
        $ok = Campaigns::where(['id' => $request->campaign_id])->first();
        if ($ok) {
            DB::beginTransaction();
            try {
                Campaign_send_to::where('id', $request->campaign_id)->delete();
                if ($request->has('send_to')) {
                    $sendToArr = array();
                    foreach ($request->send_to as $sendTo) {
                        $sendToArr[] = [
                            'campaign_id' => $request->campaign_id,
                            $ok->type == 'email' ? 'email' : 'contact' => $sendTo
                        ];
                    }
                    Campaign_send_to::insert($sendToArr);
                    return response()->json([
                        'data' => '',
                        'status' => true,
                        'message' => 'Data Update Success',
                    ]);
                }
                DB::commit();
            } catch (Exception $e) {
                DB::rollback();
                return response()->json([
                    'data' => '',
                    'status' => false,
                    'message' => 'Data Update fail',
                ]);
            }
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'Data not found',
            ]);
        }
    }

    public function updateCampaignNotSendTo(Request $request)
    {
        $ok = Campaigns::where(['id' => $request->campaign_id])->first();
        if ($ok) {
            DB::beginTransaction();
            try {
                Campaign_notsend_to::where('id', $request->campaign_id)->delete();
                if ($request->has('not_send_to')) {
                    $notSendToArr = array();
                    foreach ($request->not_send_to as $notSendTo) {
                        $notSendToArr[] = [
                            'campaign_id' => $request->campaign_id,
                            $ok->type == 'email' ? 'email' : 'contact' => $notSendTo
                        ];
                    }
                    Campaign_notsend_to::insert($notSendToArr);
                    return response()->json([
                        'data' => '',
                        'status' => true,
                        'message' => 'Data Update Success',
                    ]);
                }
                DB::commit();
            } catch (Exception $e) {
                DB::rollback();
                return response()->json([
                    'data' => '',
                    'status' => false,
                    'message' => 'Data Update fail',
                ]);
            }
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'Data not found',
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (Campaigns::find($id)->delete()) {
            Campaign_detail::where('campaign_id', $id)->delete();
            return response()->json([
                'data' => '',
                'status' => true,
                'message' => 'Data delete successfully',
            ]);
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'Data delete fail',
            ]);
        }
    }

    public function campaignRates($id = null)
    {
        $previousMonth_date = dateBeforeOneMonth();
        $currentMonth_date = date("Y-m-d") . ' 23:59:59';

        if (Auth::user()->user_type == 'user') {
            $overall = Campaigns::whereDate('created_at', '>=', $previousMonth_date)->whereDate('created_at', '<=', $currentMonth_date)->where(['user_id' => auth()->user()->id])->count();
        } else {
            $overall = Campaigns::whereDate('created_at', '>=', $previousMonth_date)->whereDate('created_at', '<=', $currentMonth_date)->count();
        }

        $avg = $overall / 30;

        $resp = [
            'status' => true,
            'data' => [
                'total_campaigns' => round($overall, 2),
                'avg_pr_day' => round($avg, 2),
            ],
            'message' => ''

        ];
        return response()->json($resp);
    }
}
