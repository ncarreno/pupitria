<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Worker de la cola en servidores sin Supervisor (Plesk): un cron cada minuto lanza `schedule:run`.
 * En segundo plano para no bloquear el scheduler y sin withoutOverlapping, para que varias generaciones
 * vayan en paralelo; por eso retry_after (460 s) es mayor que el timeout más largo de los jobs.
 */
Schedule::command('queue:work --stop-when-empty --tries=1 --timeout=420')
    ->everyMinute()
    ->runInBackground();

Schedule::command('queue:prune-failed --hours=720')->daily();
