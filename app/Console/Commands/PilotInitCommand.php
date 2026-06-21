<?php

namespace App\Console\Commands;

use Database\Seeders\PilotSeeder;
use Illuminate\Console\Command;

class PilotInitCommand extends Command
{
    protected $signature = 'pilot:init';

    protected $description = 'Inizializza l\'ambiente per il go-live: piani abbonamento reali e account gestore.';

    public function handle(): int
    {
        if (! $this->confirm('Sicuro? Questa operazione modifica il database di produzione.')) {
            $this->info('Operazione annullata.');

            return self::SUCCESS;
        }

        $this->call('db:seed', ['--class' => PilotSeeder::class]);

        $this->info('Ambiente pilota inizializzato.');

        return self::SUCCESS;
    }
}
