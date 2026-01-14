<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();

Schedule::command('publish:guard-roaster')->daily();
Schedule::command('publish:employee-payroll')->daily();
Schedule::command('send-sms:send-sms-in-group')->everyFiveMinutes();