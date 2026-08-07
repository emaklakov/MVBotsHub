<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

//Artisan::command('inspire', function () {
//    $this->comment(Inspiring::quote());
//})->purpose('Display an inspiring quote');

Schedule::command('broadcasts:dispatch-scheduled')->everyMinute()->withoutOverlapping();

Schedule::command('audiences:refresh-cached-count')->hourly();
