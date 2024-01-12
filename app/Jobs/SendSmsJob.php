<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Events\CampaignNotificationEvent;
use DB;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $campaign_date;
    protected $campaign_id;
    protected $type;

    public function __construct($campaign_date = null, $campaign_id = null, $type = null)
    {
        // die('ddddd');
        $this->campaign_date = $campaign_date;
        $this->campaign_id = $campaign_id;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $sc_date = $this->campaign_date;
        $id = $this->campaign_id;
        $type = $this->type;
        if ($this->campaign_id) {
            $cam_to_run = DB::table('campaign_details')
                ->join('campaigns', 'campaign_details.campaign_id', '=', 'campaigns.id')
                ->select('campaign_details.*', 'campaigns.id AS campaigns_id', 'campaigns.title', 'campaigns.message', 'campaigns.user_id', 'campaigns.campaign_send_to_emails', 'campaigns.campaign_not_send_to_emails', 'campaigns.campaign_send_to_list_ids')
                ->where('campaign_details.id', $this->campaign_id)
                ->first();

            // return $cam_to_run;
            if ($cam_to_run) {
                $statusData = ['status' => 'in-progress'];
                DB::table('campaign_details')->where('id', $cam_to_run->id)->update($statusData);
                DB::table('campaigns')->where('id', $cam_to_run->campaigns_id)->update($statusData);
                $do = sendSMS($cam_to_run);
                if ($do != 'limit') {
                    $statusData = ['status' => 'success'];
                } else {
                    $statusData = ['status' => 'cancel'];
                }
                DB::table('campaign_details')->where('id', $cam_to_run->id)->update($statusData);
                DB::table('campaigns')->where('id', $cam_to_run->campaigns_id)->update($statusData);


                // return $do;
            }
        } elseif ($this->campaign_date) {
            // $sc_date = date('Y-m-d H:i');

            if ($type == 'run') {

                $cam_to_run = DB::table('campaign_details')
                    ->join('campaigns', 'campaign_details.campaign_id', '=', 'campaigns.id')
                    ->select('campaign_details.*', 'campaigns.id AS campaigns_id', 'campaigns.title', 'campaigns.message', 'campaigns.user_id', 'campaigns.campaign_send_to_emails', 'campaigns.campaign_not_send_to_emails', 'campaigns.campaign_send_to_list_ids')
                    ->where('campaign_details.schedule_date', 'like', '%' . $sc_date . '%')
                    ->where('campaign_details.status', 'pending')
                    ->get();
                // var_dump($cam_to_run); die;
                if ($cam_to_run) {
                    foreach ($cam_to_run as $cam_key) {
                        $statusData = ['status' => 'in-progress'];
                        DB::table('campaign_details')->where('id', $cam_key->id)->update($statusData);
                        DB::table('campaigns')->where('id', $cam_key->campaigns_id)->update($statusData);
                    
                        $do = sendSMS($cam_key);
                        if ($do != 'limit') {
                            $statusData = ['status' => 'success'];
                            DB::table('campaign_details')->where('id', $cam_key->id)->update($statusData);
                            DB::table('campaigns')->where('id', $cam_key->campaigns_id)->update($statusData);
                        } else {
                            $statusData = ['status' => 'cancel'];
                            DB::table('campaign_details')->where('id', $cam_key->id)->update($statusData);
                            DB::table('campaigns')->where('id', $cam_key->campaigns_id)->update($statusData);
                            break;
                        }
                        // $statusData = ['status' => 'success'];

                        unset($statusData);
                    }
                }
            } elseif ($type == 'notification') {
                $cam_to_run = DB::table('campaign_details')
                    ->join('campaigns', 'campaign_details.campaign_id', '=', 'campaigns.id')
                    ->select('campaign_details.*', 'campaigns.id AS campaigns_id', 'campaigns.title', 'campaigns.message', 'campaigns.user_id', 'campaigns.campaign_send_to_emails', 'campaigns.campaign_not_send_to_emails', 'campaigns.campaign_send_to_list_ids')
                    ->where('campaign_details.schedule_date', 'like', '%' . $sc_date . '%')
                    ->where('campaign_details.status', 'pending')
                    ->get();

                if ($cam_to_run) {
                    foreach ($cam_to_run as $cam_key) {
                        broadcast(new CampaignNotificationEvent($cam_key, 'campaignNotification-' . $cam_key->user_id))->toOthers();
                    }
                }
            }
        }
    }
}
