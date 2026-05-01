<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// hourly で発火し、コマンド側の冪等性（当月分生成済みチェック）に取りこぼし対策を任せる
Schedule::command('transactions:generate-recurring')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/recurring-transactions.log'));
