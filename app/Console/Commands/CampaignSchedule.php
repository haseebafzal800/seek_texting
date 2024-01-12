<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\model\Campaign_detail;
use DB;
use App\Models\Contactlist;
use Illuminate\Support\Arr;
use App\Models\User;
use Artisan;
use App\Jobs\SendSmsJob;

class CampaignSchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaign:campaignRun';

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
    // public function handle()
    // {
    //     // sendSMS();
    //     $sc_date = date('Y-m-d H:i');
    //     // dispatch(new SendSmsJob($sc_date, null, 'run'));
    //     // Artisan::call('queue:work --stop-when-empty');

    //     $cam_to_run = DB::table('campaign_details')
    //         ->join('campaigns', 'campaign_details.campaign_id', '=', 'campaigns.id')
    //         ->select('campaign_details.*', 'campaigns.id AS campaigns_id', 'campaigns.title', 'campaigns.message', 'campaigns.user_id', 'campaigns.campaign_send_to_emails', 'campaigns.campaign_not_send_to_emails', 'campaigns.campaign_send_to_list_ids')
    //         // ->select('campaign_details.id')
    //         ->where('campaign_details.schedule_date', 'like', '%' . $sc_date . '%')
    //         ->where('campaign_details.status', 'pending')
    //         ->get();

    //     if ($cam_to_run) {
    //         foreach ($cam_to_run as $cam_key) {
    //             // dispatch(new SendSmsJob(null, $cam_key->id, null));
    //             //         // $last_id = DB::getPdo()->lastInsertId();
    //             $statusData = ['status' => 'success'];
    //             DB::table('campaign_details')->where('id', $cam_key->id)->update($statusData);
    //             DB::table('campaigns')->where('id', $cam_key->campaigns_id)->update($statusData);
    //             sendSMS($cam_key);
    //         }
    //     }
    // }


    public function handle()
    {
        // sendSMS();
        $sc_date = date('Y-m-d H:i');
        $cam_to_run = DB::table('campaign_details')
            ->join('campaigns', 'campaign_details.campaign_id', '=', 'campaigns.id')
            ->select('campaign_details.*', 'campaigns.id AS campaigns_id', 'campaigns.title', 'campaigns.message', 'campaigns.user_id', 'campaigns.campaign_send_to_emails', 'campaigns.campaign_not_send_to_emails', 'campaigns.campaign_send_to_list_ids')
            ->where('campaign_details.schedule_date', 'like', '%' . $sc_date . '%')
            ->where('campaign_details.status', 'pending')
            ->get();
        if ($cam_to_run) {
            foreach ($cam_to_run as $cam_key) {
                $statusData = ['status' => 'in-progress'];
                DB::table('campaign_details')->where('id', $cam_key->id)->update($statusData);
                DB::table('campaigns')->where('id', $cam_key->campaigns_id)->update($statusData);
                $do = sendSMS($cam_key);
                if($do != 'limit'){
                $statusData = ['status' => 'success'];
                DB::table('campaign_details')->where('id', $cam_key->id)->update($statusData);
                DB::table('campaigns')->where('id', $cam_key->campaigns_id)->update($statusData);
                }else{
                    $statusData = ['status' => 'cancel'];
                DB::table('campaign_details')->where('id', $cam_key->id)->update($statusData);
                DB::table('campaigns')->where('id', $cam_key->campaigns_id)->update($statusData);
                
                }
            }
        }
    }
}
