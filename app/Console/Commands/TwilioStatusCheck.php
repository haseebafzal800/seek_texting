<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\ChMessage;

use Twilio\Rest\Client;
use Illuminate\Console\Command;
use DB;


class TwilioStatusCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twilio:twilioStatusUpdate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $toDate = date('Y-m-d');
        $toTime = date('H:i');
        // $sc_date = date('Y-m-d H:i');
        $sc_date = date("Y-m-d H:i", strtotime('-2 hours'));
        $oneHourAgo= date('Y-m-d H:i', strtotime('-1 hour'));
        // DB::connection()->enableQueryLog();
        $cam_to_run = DB::table('campaign_details')
            ->join('campaigns', 'campaign_details.campaign_id', '=', 'campaigns.id')
            ->select('campaign_details.*', 'campaigns.id AS campaigns_id', 'campaigns.title', 'campaigns.message', 'campaigns.user_id', 'campaigns.campaign_send_to_emails', 'campaigns.campaign_not_send_to_emails', 'campaigns.campaign_send_to_list_ids')
            ->whereDate('campaign_details.schedule_date', '<', $oneHourAgo)
            ->where('campaign_details.status', 'in-progress')
            ->get();
        // $queries = DB::getQueryLog();
        // $last_query = end($queries);
        // print_r($last_query);
        // die;
        if ($cam_to_run) {
            foreach ($cam_to_run as $cam_key) {
                $statusData = ['status' => 'success'];
                DB::table('campaign_details')->where('id', $cam_key->id)->update($statusData);
                DB::table('campaigns')->where('id', $cam_key->campaigns_id)->update($statusData);
                // sendSMS($cam_key);
            }
        }
        $yesterdayDate = date('Y-m-d',strtotime("-2 days"));
        $users = User::all();
        $sid = getenv("TWILIO_SID");
        $token = getenv("TWILIO_TOKEN");
        foreach ($users as $user) {

            $twilio = new Client($sid, $token);

            $messages = $twilio->messages
                ->read(
                    [
                        // "dateSent" => new \DateTime($toDate),
                        // 'dateSentBefore' => new \DateTime('Y-m-d'),
                        'dateSentAfter' => new \DateTime($yesterdayDate),
                        "from" => $user->contact_number,
                        // "to" => "+15558675310"
                    ]
                );
            foreach ($messages as $record) {
                // print($record->sid);
                $sms = ChMessage::where('msg_sid', $record->sid)->first();
                if (isset($sms) && $sms->msg_sid != '') {
                    $sms->status = $record->status;
                    $sms->save();
                }
            } 
        }
        
    }
}
