<?php

namespace App\Livewire\Backoffice\Settings;

use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Livewire\Component;

class ArtisanRunner extends Component
{
    public string $output = '';

    public string $lastLabel = '';

    public bool $hasOutput = false;

    private const COMMANDS = [
        'optimize-clear' => [
            'label' => 'Svuota tutte le cache',
            'command' => 'optimize:clear',
            'args' => [],
            'group' => 'Cache & Ottimizzazione',
            'description' => 'Svuota cache applicazione, config, route e view in una sola operazione.',
            'icon' => 'fas fa-broom',
            'style' => 'warning',
        ],
        'cache-clear' => [
            'label' => 'Cache applicazione',
            'command' => 'cache:clear',
            'args' => [],
            'group' => 'Cache & Ottimizzazione',
            'description' => 'Svuota la cache applicazione (tag Redis inclusi, es. kpi, exercises).',
            'icon' => 'fas fa-trash-alt',
            'style' => 'warning',
        ],
        'config-cache' => [
            'label' => 'Rigenera cache config',
            'command' => 'config:cache',
            'args' => [],
            'group' => 'Cache & Ottimizzazione',
            'description' => 'Ricrea il file di cache delle configurazioni per migliorare le prestazioni.',
            'icon' => 'fas fa-cogs',
            'style' => 'secondary',
        ],
        'route-cache' => [
            'label' => 'Rigenera cache route',
            'command' => 'route:cache',
            'args' => [],
            'group' => 'Cache & Ottimizzazione',
            'description' => 'Ricrea il file di cache delle route. Non usare se ci sono route con closure.',
            'icon' => 'fas fa-sitemap',
            'style' => 'secondary',
        ],
        'view-clear' => [
            'label' => 'Svuota cache view',
            'command' => 'view:clear',
            'args' => [],
            'group' => 'Cache & Ottimizzazione',
            'description' => 'Elimina le view Blade compilate. Utile dopo modifiche ai template.',
            'icon' => 'fas fa-eye-slash',
            'style' => 'secondary',
        ],
        'migrate-status' => [
            'label' => 'Stato migrazioni',
            'command' => 'migrate:status',
            'args' => [],
            'group' => 'Database',
            'description' => 'Elenca tutte le migrazioni con il relativo stato (eseguita / non eseguita).',
            'icon' => 'fas fa-database',
            'style' => 'info',
        ],
        'seed-settings-flags' => [
            'label' => 'Inizializza flag impostazioni',
            'command' => 'db:seed',
            'args' => ['--class' => 'SettingsFlagSeeder', '--force' => true],
            'group' => 'Database',
            'description' => 'Riesegue SettingsFlagSeeder (idempotente): crea le chiavi flag mancanti in settings senza sovrascrivere i valori esistenti.',
            'icon' => 'fas fa-toggle-on',
            'style' => 'info',
        ],
        'schedule-list' => [
            'label' => 'Comandi schedulati',
            'command' => 'schedule:list',
            'args' => [],
            'group' => 'Sistema',
            'description' => 'Elenca tutti i comandi configurati nello scheduler con orari e prossima esecuzione.',
            'icon' => 'fas fa-calendar-alt',
            'style' => 'secondary',
        ],
        'queue-restart' => [
            'label' => 'Riavvia worker coda',
            'command' => 'queue:restart',
            'args' => [],
            'group' => 'Sistema',
            'description' => 'Invia segnale di riavvio ai worker attivi. I job in corso vengono completati prima del riavvio.',
            'icon' => 'fas fa-redo',
            'style' => 'warning',
        ],
        'classes-generate-occurrences' => [
            'label' => 'Genera occorrenze corsi',
            'command' => 'classes:generate-occurrences',
            'args' => [],
            'group' => 'Corsi collettivi',
            'description' => 'Genera le istanze datate dei corsi collettivi a partire dal palinsesto attivo.',
            'icon' => 'fas fa-calendar-plus',
            'style' => 'primary',
        ],
        'classes-send-reminders' => [
            'label' => 'Invia reminder corsi',
            'command' => 'classes:send-reminders',
            'args' => [],
            'group' => 'Corsi collettivi',
            'description' => 'Invia notifica di promemoria agli atleti iscritti ai corsi del giorno successivo.',
            'icon' => 'fas fa-bell',
            'style' => 'primary',
        ],
    ];

    public function runCommand(string $key): void
    {
        abort_unless(auth()->user()?->hasRole('gestore'), 403);
        abort_unless(array_key_exists($key, self::COMMANDS), 422);

        $cmd = self::COMMANDS[$key];

        Artisan::call($cmd['command'], $cmd['args']);

        $raw = Artisan::output();
        $this->output = $raw !== '' ? $raw : '(nessun output)';
        $this->lastLabel = $cmd['label'];
        $this->hasOutput = true;
    }

    public function clearOutput(): void
    {
        $this->output = '';
        $this->lastLabel = '';
        $this->hasOutput = false;
    }

    public function render(): View
    {
        $groups = [];
        foreach (self::COMMANDS as $key => $cmd) {
            $groups[$cmd['group']][$key] = $cmd;
        }

        return view('livewire.backoffice.settings.artisan-runner', [
            'groups' => $groups,
        ])->layout('layouts.backoffice', ['page_title' => 'Comandi Artisan']);
    }
}
