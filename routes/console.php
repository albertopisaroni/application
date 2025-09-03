<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Comando per il calcolo delle tasse regime forfettario
// Eseguito il 31 gennaio di ogni anno alle 2:00
Schedule::command('taxes:calculate-forfettario')
    ->yearlyOn(1, 31, '02:00')
    ->withoutOverlapping()
    ->sendOutputTo(storage_path('logs/taxes-calculation.log'))
    ->emailOutputOnFailure(config('mail.admin_email'))
    ->description('Calcolo annuale tasse regime forfettario');

// Comando per processare le fatture ricorrenti
// Eseguito ogni giorno alle 9:00
Schedule::command('recurring-invoices:process')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->sendOutputTo(storage_path('logs/recurring-invoices.log'))
    ->emailOutputOnFailure(config('mail.admin_email'))
    ->description('Elaborazione giornaliera fatture ricorrenti');

