<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\CampaignNotificationEvent;

use App\model\Campaign_detail;
use DB;
use App\Models\Contactlist;
use Illuminate\Support\Arr;
use App\Models\User;

use App\Jobs\SendSmsJob;

class CampaignNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaignNotification:runCampaignNotification';

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
        $sc_date = date('Y-m-d H:i', strtotime('+15 minutes'));
        dispatch(new SendSmsJob($sc_date, null, 'notification'));
    }
}
