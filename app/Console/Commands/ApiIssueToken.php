<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ApiIssueToken extends Command
{
    protected $signature = 'api:issue-token
        {consumer : Nome slug del consumer (deve esistere un account di servizio)}
        {--name= : Nome del token (default: consumer-YYYYMMDD)}
        {--abilities= : Abilities separate da virgola (default: *)}';

    protected $description = 'Emette un personal access token per un account di servizio. Il plain text e\' stampato una sola volta.';

    public function handle(): int
    {
        $consumer = Str::slug((string) $this->argument('consumer'));
        $email = "api-{$consumer}@service.iron-gym.internal";

        $user = User::where('email', $email)->where('is_service_account', true)->first();

        if ($user === null) {
            $this->error("Account di servizio non trovato per consumer \"{$consumer}\".");
            $this->line("Crea prima l'account con: php artisan api:create-service-account {$consumer}");

            return self::FAILURE;
        }

        $tokenName = $this->option('name') ?: "{$consumer}-".now()->format('Ymd');
        $abilitiesRaw = $this->option('abilities') ?: '*';
        $abilities = array_map('trim', explode(',', $abilitiesRaw));

        $token = $user->createToken($tokenName, $abilities);

        $this->info("Token emesso per consumer \"{$consumer}\":");
        $this->line("Nome: {$tokenName}");
        $this->line('Abilities: '.implode(', ', $abilities));
        $this->newLine();
        $this->warn('Plain text token (copialo ora, non sara\' piu\' visibile):');
        $this->line($token->plainTextToken);

        return self::SUCCESS;
    }
}
