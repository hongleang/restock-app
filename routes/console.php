<?php

use App\Console\Commands\SendLowStockNotificationCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// Scheduling
Schedule::call(SendLowStockNotificationCommand::class)->daily();
