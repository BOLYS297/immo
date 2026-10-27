<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Purge quotidienne des données à durée limitée (journal d'activité : 12 mois). En production : cron « php artisan schedule:run » chaque minute.
Schedule::command('model:prune')->daily();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
