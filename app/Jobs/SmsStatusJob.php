<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Twilio\Rest\Client;
use App\Models\ChMessage;


class SmsStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $contact;
    public $smsDate;
    public $campaign_id;
    public function __construct($contact, $toDate, $campaign_id=null)
    {
        $this->contact = $contact;
        $this->smsDate = $toDate;
        $this->campaign_id = $campaign_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->campaign_id){
            $statusData = ['status' => 'success'];
                // DB::table('campaign_details')->where('id', $cam_key->id)->update($statusData);
                DB::table('campaigns')->where('id', $this->campaign_id)->update($statusData);
        }
        $sid = getenv("TWILIO_SID");
        $token = getenv("TWILIO_TOKEN");

        $twilio = new Client($sid, $token);

        $messages = $twilio->messages
            ->read(
                [
                    "dateSent" => new \DateTime($this->smsDate),
                    "from" => $this->contact,
                    // "to" => "+15558675310"
                ]
            );

        foreach ($messages as $record) {
            // print($record->sid);
            $sms = ChMessage::where('msg_sid', $record->sid)->first();
            $sms->status = $record->status;
            $sms->save();
        }
    }
}
