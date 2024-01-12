<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

// use App\Console\Commands\FindDriverForRegularRide;
use App\Console\Commands\CampaignSchedule;
use App\Console\Commands\campaignNotification;
use Illuminate\Queue\Jobs\Job;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected $command = [
        CampaignSchedule::class,
        CampaignNotification::class,
        TwilioStatusCheck::class,
        job::class,
    ];
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        // $schedule->command('find_driver:for_regular_ride')->everyMinute();
        $schedule->command('campaign:campaignRun')->everyMinute();
        $schedule->command('campaignNotification:runCampaignNotification')->everyMinute();
        $schedule->command('twilio:twilioStatusUpdate')->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
