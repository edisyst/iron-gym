<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InactiveMembersCommand extends Command
{
    protected $signature = 'reports:inactive-members';

    protected $description = 'Lista tesserati con abbonamento attivo che non entrano da più di 30 giorni. Output CSV su stdout.';

    public function handle(): int
    {
        $cutoff = now()->subDays(30)->toDateTimeString();
        $today = now()->toDateString();

        $members = DB::table('members as m')
            ->join('subscriptions as s', function ($j) use ($today) {
                $j->on('s.member_id', '=', 'm.id')
                    ->where('s.status', 'active')
                    ->where('s.expires_at', '>=', $today);
            })
            ->leftJoin(
                DB::raw('(SELECT member_id, MAX(checked_in_at) as last_access FROM access_logs GROUP BY member_id) as al'),
                'al.member_id',
                '=',
                'm.id',
            )
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('al.last_access')
                    ->orWhere('al.last_access', '<', $cutoff);
            })
            ->select(
                'm.last_name as cognome',
                'm.first_name as nome',
                'm.email',
                'm.phone as telefono',
                's.expires_at as scadenza_abbonamento',
                'al.last_access as ultimo_accesso',
            )
            ->orderBy('al.last_access')
            ->distinct()
            ->get();

        // CSV su stdout
        $this->line('cognome;nome;email;telefono;scadenza_abbonamento;ultimo_accesso');
        foreach ($members as $m) {
            $this->line(implode(';', [
                $this->csvEscape($m->cognome),
                $this->csvEscape($m->nome),
                $this->csvEscape($m->email ?? ''),
                $this->csvEscape($m->telefono ?? ''),
                $m->scadenza_abbonamento ?? '',
                $m->ultimo_accesso ?? 'mai',
            ]));
        }

        return self::SUCCESS;
    }

    private function csvEscape(string $value): string
    {
        if (str_contains($value, ';') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }
}
