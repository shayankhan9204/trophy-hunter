<?php

use App\Console\Commands\SendMissedCheckinAlerts;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/*
|--------------------------------------------------------------------------
| Missed Check-In SMS Alerts
|--------------------------------------------------------------------------
|
| Runs every minute so that an SMS fires as soon as an I'm Safe interval
| window closes without a check-in.
|
| The MissedCheckinAlertSent idempotency table guarantees each contact is
| only alerted once per interval, even if the scheduler overlaps.
|
| Production cron entry (add to server crontab):
|   * * * * * cd /path/to/your/app && php artisan schedule:run >> /dev/null 2>&1
|
*/
Schedule::command(SendMissedCheckinAlerts::class)
    ->everyMinute()
    ->withoutOverlapping()          // skip if previous run is still going
    ->runInBackground()             // don't block the scheduler loop
    ->appendOutputTo(storage_path('logs/missed-checkin.log'));

