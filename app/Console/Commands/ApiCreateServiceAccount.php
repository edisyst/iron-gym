<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class ApiCreateServiceAccount extends Command
{
    protected $signature = 'api:create-service-account {consumer : Nome slug del consumer (es. totem, gestionale)}';

    protected $description = 'Crea un account di servizio API per un consumer. Idempotente: rieseguire non duplica nulla.';

    public function handle(): int
    {
        $consumer = Str::slug((string) $this->argument('consumer'));
        $email = "api-{$consumer}@service.iron-gym.internal";

        Role::firstOrCreate(['name' => 'api_client']);

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => "API: {$consumer}",
                'password' => Hash::make(Str::random(64)),
                'is_service_account' => true,
                'email_verified_at' => now(),
            ]
        );

        if (! $user->hasRole('api_client')) {
            $user->assignRole('api_client');
        }

        if ($user->wasRecentlyCreated) {
            $this->info("Account di servizio creato: {$email}");
        } else {
            $this->info("Account di servizio esistente: {$email} (nessuna modifica)");
        }

        $this->line("ID utente: {$user->id}");
        $this->line("Emetti un token con: php artisan api:issue-token {$consumer}");

        return self::SUCCESS;
    }
}
