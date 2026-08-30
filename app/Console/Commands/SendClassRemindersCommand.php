<?php

namespace App\Console\Commands;

use App\Jobs\SendClassReminders;
use Illuminate\Console\Command;

class SendClassRemindersCommand extends Command
{
    protected $signature = 'classes:send-reminders {--sync : Esegue il job in linea invece di accodarlo}';

    protected $description = 'Invia i promemoria per i corsi collettivi di domani (schedulato ogni giorno alle 08:00).';

    public function handle(): int
    {
        if ($this->option('sync')) {
            SendClassReminders::dispatchSync();
            $this->info('Promemoria corsi inviati (esecuzione sincrona).');

            return self::SUCCESS;
        }

        SendClassReminders::dispatch();
        $this->info('Job SendClassReminders accodato. Serve un worker attivo: php artisan queue:work');

        return self::SUCCESS;
    }
}
