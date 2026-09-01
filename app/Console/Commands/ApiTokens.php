<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokens extends Command
{
    protected $signature = 'api:tokens
        {--consumer= : Filtra per consumer slug}
        {--revoke= : ID del token da revocare}';

    protected $description = 'Elenca i token API attivi degli account di servizio. Con --revoke=ID revoca il token specificato.';

    public function handle(): int
    {
        if ($revokeId = $this->option('revoke')) {
            return $this->revokeToken((int) $revokeId);
        }

        return $this->listTokens();
    }

    private function listTokens(): int
    {
        $query = User::where('is_service_account', true)->with('tokens');

        if ($consumer = $this->option('consumer')) {
            $slug = Str::slug($consumer);
            $email = "api-{$slug}@service.iron-gym.internal";
            $query->where('email', $email);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->info('Nessun account di servizio trovato.');

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($users as $user) {
            foreach ($user->tokens as $token) {
                $rows[] = [
                    $token->id,
                    $user->name,
                    $token->name,
                    implode(', ', $token->abilities),
                    $token->last_used_at?->format('Y-m-d H:i') ?? 'mai',
                    $token->created_at->format('Y-m-d'),
                ];
            }
        }

        if (empty($rows)) {
            $this->info('Nessun token emesso.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Consumer', 'Nome token', 'Abilities', 'Ultimo uso', 'Creato il'],
            $rows,
        );

        return self::SUCCESS;
    }

    private function revokeToken(int $tokenId): int
    {
        $token = PersonalAccessToken::find($tokenId);

        if ($token === null) {
            $this->error("Token ID {$tokenId} non trovato.");

            return self::FAILURE;
        }

        $user = User::find($token->tokenable_id);

        if (! $user?->is_service_account) {
            $this->error('Il token non appartiene a un account di servizio: revoca negata.');

            return self::FAILURE;
        }

        $token->delete();
        $this->info("Token ID {$tokenId} ({$token->name}) revocato.");

        return self::SUCCESS;
    }
}
