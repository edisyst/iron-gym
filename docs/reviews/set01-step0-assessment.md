# SET01 Step 0 — Assessment configurabilità e area admin

Data: 2026-08-29  
Stato: READ-ONLY, nessuna modifica al codice

---

## Fase 1 — Pagina Feature Flags esistente

### Localizzazione

| Elemento | Valore |
|---|---|
| Componente | `app/Livewire/Backoffice/Admin/FeatureFlagManager.php` |
| View | `resources/views/livewire/backoffice/admin/feature-flag-manager.blade.php` |
| Route | `GET /backoffice/admin/feature-flags` → `backoffice.admin.feature-flags` |
| Middleware | `auth` + `role:gestore` (gruppo `Route::middleware('role:gestore')` in `routes/backoffice.php:203`) |
| Voce sidebar | **Assente** — la sezione ADMIN mostra solo "Inventario Dischi". Feature Flags e Feedback List sono raggiungibili solo per URL diretta. |

### Comportamento esatto

**Lettura stato:** `render()` chiama `Feature::active($flag)` per ogni flag nell'array `$flags`; il risultato
dipende dal definer registrato in `AppServiceProvider::defineFeatureFlags()`.

**Scrittura — due percorsi distinti:**

1. **Flag globale** (chiave in `GLOBAL_FLAGS`, attualmente solo `group_classes`):
   - `Setting::write('group_classes_enabled', bool)` → tabella `settings`
   - `Feature::purge('group_classes')` → rimuove le righe Pennant già risolte per tutti gli scope
   - Motivazione: `Feature::activateForEveryone()` aggiorna solo le righe già esistenti in `features`,
     lasciando fuori gli utenti che non avevano ancora risolto il flag.

2. **Flag per-utente** (`periodization_engine`, `push_notifications`, `financial_reports`):
   - `Feature::activateForEveryone()` / `Feature::deactivateForEveryone()` → tabella `features` Pennant
   - Nessun `purge()` aggiuntivo.

**Conferma:** il toggle richiede un `requestToggle()` + `confirmToggle()` (modal inline Livewire).
Nessuna invalidazione cache Redis esplicita (non necessaria: i flag non usano Redis).

**Scope management:** nessuno — il componente non espone toggle per-utente o per-ruolo.

**Test dedicati:** `tests/Feature/GlobalFeatureFlagTest.php` — 5 test:
- `usa il default da config quando settings non ha la chiave`
- `applica il flag globale anche a utenti che non lo hanno mai risolto`
- `il toggle del gestore ha effetto su tutti gli utenti`
- `nega il toggle a chi non e gestore`
- `rifiuta flag non presenti nella lista gestita`
- `Setting legge e scrive valori booleani con cache invalidata`

### Codice componente completo

```php
<?php

namespace App\Livewire\Backoffice\Admin;

use App\Models\Setting;
use Illuminate\View\View;
use Laravel\Pennant\Feature;
use Livewire\Component;

class FeatureFlagManager extends Component
{
    private const GLOBAL_FLAGS = [
        'group_classes' => 'group_classes_enabled',
    ];

    /** @var list<string> */
    public array $flags = [
        'periodization_engine',
        'push_notifications',
        'group_classes',
        'financial_reports',
    ];

    public bool $confirmActive = false;
    public string $pendingFlag = '';
    public bool $pendingState = false;

    public function requestToggle(string $flag, bool $activate): void
    {
        $this->pendingFlag = $flag;
        $this->pendingState = $activate;
        $this->confirmActive = true;
    }

    public function confirmToggle(): void
    {
        abort_unless(auth()->user()?->hasRole('gestore'), 403);
        abort_unless(in_array($this->pendingFlag, $this->flags, true), 403);

        if (isset(self::GLOBAL_FLAGS[$this->pendingFlag])) {
            Setting::write(self::GLOBAL_FLAGS[$this->pendingFlag], $this->pendingState);
            Feature::purge($this->pendingFlag);
        } elseif ($this->pendingState) {
            Feature::activateForEveryone($this->pendingFlag);
        } else {
            Feature::deactivateForEveryone($this->pendingFlag);
        }

        $this->reset(['confirmActive', 'pendingFlag', 'pendingState']);
        session()->flash('success', 'Feature flag aggiornato.');
    }

    public function cancelToggle(): void
    {
        $this->reset(['confirmActive', 'pendingFlag', 'pendingState']);
    }

    public function render(): View
    {
        $statuses = [];
        foreach ($this->flags as $flag) {
            $statuses[$flag] = Feature::active($flag);
        }

        return view('livewire.backoffice.admin.feature-flag-manager', [
            'statuses' => $statuses,
        ])->layout('layouts.backoffice', ['page_title' => 'Feature flags']);
    }
}
```

### Verdetto: estendere o riscrivere?

**Estendere il componente esistente, spostarlo sotto `/backoffice/settings/`.**

Motivazioni:
- 79 righe, logica GLOBAL_FLAGS già corretta e testata.
- I 5 test esistenti sono tutti a livello metodo Livewire — reggono un refactor di route/nome purché la classe resti.
- L'unica cosa mancante è la voce sidebar e, in futuro, la possibilità di aggiungere nuovi flag globali.
- Riscrivere introdurrebbe rischio di regressione sui test senza guadagno architetturale.

Azione minima: aggiungere voce "Impostazioni" nel menu adminlte.php e, se si crea una sezione SETTINGS
con più pagine, spostare la route da `/admin/feature-flags` a `/settings/feature-flags`
con redirect 301 dalla vecchia.

---

## Fase 2 — Copertura dei feature flag

### Inventario flag Pennant

| Flag | Definito in | Scope/logica default | Descrizione |
|---|---|---|---|
| `periodization_engine` | `AppServiceProvider:52` | per-utente: gestore OPPURE email in `config('features.beta_trainers')` | Abilita progressione automatica mesocicli |
| `push_notifications` | `AppServiceProvider:55` | per-utente: atleta o trainer | Abilita registrazione service worker + push |
| `group_classes` | `AppServiceProvider:62` | globale: legge `Setting::bool('group_classes_enabled')`, fallback `config('features.group_classes_enabled', false)` | Modulo corsi collettivi |
| `financial_reports` | `AppServiceProvider:69` | per-utente: solo gestore | Abilita report manager dashboard |

Flag extra via `config()` (non Pennant, non gestibile da FeatureFlagManager):

| Chiave | File | Consumo |
|---|---|---|
| `features.in_app_feedback_enabled` | `config/features.php:6` | `layouts/athlete.blade.php:297`, `layouts/backoffice.blade.php:53` |

### Punti di consumo per flag

**`group_classes`** — più diffuso:

| File | Riga | Tipo |
|---|---|---|
| `app/Livewire/Athlete/Booking.php` | 170, 221 | `abort_unless(Feature::active(...), 403)` su enroll/cancel |
| `app/Livewire/Athlete/Booking.php` | 291, 301 | Condizione query in `render()` |
| `app/Livewire/Athlete/Dashboard.php` | 62 | Condizione query `if (Feature::active(...))` |
| `app/Livewire/Athlete/Profile.php` | 157 | `$groupClassesEnabled = Feature::active(...)` passato alla view |
| `resources/views/livewire/athlete/booking.blade.php` | 32, 144 | `@feature` / `@endfeature` |
| `app/Providers/AppServiceProvider.php` | 76 | `Gate::define('view-group-classes', ...)` |
| `config/adminlte.php` | 414, 418, 425, 430 | `'can' => 'view-group-classes'` — sidebar |
| `routes/backoffice.php` | 81-83 | **Nessun gate a livello route** (vedi gating incompleto) |

**`periodization_engine`**:

| File | Riga | Tipo |
|---|---|---|
| `resources/views/livewire/backoffice/mesocycles/mesocycle-detail.blade.php` | 45 | `@feature` bottone "Applica progressione" |
| `app/Livewire/Backoffice/Mesocycles/MesocycleDetail.php` | 103 | Metodo `applyProgression()` — **nessun `abort_unless` Pennant** (vedi gating incompleto) |

**`push_notifications`**:

| File | Riga | Tipo |
|---|---|---|
| `resources/views/layouts/athlete.blade.php` | 303–336 | `@feature` script SW + push permission request |

**`financial_reports`**:

| File | Riga | Tipo |
|---|---|---|
| `resources/views/livewire/backoffice/reports/manager-dashboard.blade.php` | 2, 254 | `@feature` wrappa l'intera view |
| `routes/backoffice.php` | 113–115 | Route `/reports/manager` — **solo `role:gestore`, nessun Feature gate** |

### Gating incompleto — gap rilevati

**GAP-01 — Route backoffice corsi collettivi non protette da gate `view-group-classes`**

Le tre route:
```
GET /backoffice/group-classes          → GroupClassManager
GET /backoffice/group-classes/schedules → ClassScheduleManager
GET /backoffice/group-classes/catalog   → GroupClassCatalog
```
sono nel gruppo `role:gestore|trainer|receptionist` ma NON hanno `can:view-group-classes`.
Il menu le nasconde, ma la URL diretta bypassa il flag.

**GAP-02 — `applyProgression()` non gated a livello PHP**

`MesocycleDetail::applyProgression()` (riga 103) non chiama `abort_unless(Feature::active('periodization_engine'))`.
Solo la view nasconde il bottone. Un POST Livewire diretto esegue la progressione anche con flag off.

**GAP-03 — Route `/reports/manager` solo role-gated, non feature-gated**

La route `/reports/financial` e `/reports/manager` hanno `middleware('role:gestore')`.
Con `financial_reports` disattivato la view di ManagerDashboard mostra pagina vuota
(il `@feature` wrappa tutto), ma la route risponde 200 invece di 403.

### Funzioni non flaggabili ma candidabili

| Funzione | Punti di gating necessari | Sforzo |
|---|---|---|
| Messaggistica trainer-atleta | 1 route, 1 componente `MessageThread`, eventuale voce sidebar | Basso |
| Prenotazioni PT (atleta) | `Athlete\Booking` (metodi enroll/cancel PT), view PT in `Booking.blade.php`, componente `BookingList` backoffice | Medio |
| Readiness check pre-sessione | `WorkoutSession::submitReadiness()` + `skipReadiness()` + sezione view | Medio |
| Sostituzione esercizio | `WorkoutSession::openSubstitutionModal()` + `confirmSubstitution()` + sezione view | Medio |
| Tracking foto progressi (`ProgressPhoto`) | Nessun componente dedicato esposto nell'app atleta attuale; solo dati DB | Alto (funzione non ancora esposta) |
| Plate load calculator (atleta) | Non esposto nell'app atleta; solo backoffice admin | Basso (già dietro `role:gestore`) |
| Notifiche push | Già flaggato con `push_notifications` | — |
| Report finanziari | Già flaggato con `financial_reports` (con GAP-03) | Basso (chiudere GAP-03) |

### Pseudo-flag via config() non Pennant

| Meccanismo | Dove | Nota |
|---|---|---|
| `config('features.in_app_feedback_enabled')` | Entrambi i layout (atleta + backoffice) | Controllato da `.env FEATURE_IN_APP_FEEDBACK`; non gestibile da UI |
| `config('features.beta_trainers')` | `AppServiceProvider:52` (definer `periodization_engine`) | Lista email da `.env`; non gestibile da UI |
| `config('features.group_classes_enabled')` | `AppServiceProvider:65` (fallback definer `group_classes`) | Usato solo come default al primo avvio |
| `role:gestore` su route | Varie route | Non è un feature flag ma controlla visibilità funzionale |

---

## Fase 3 — Area admin del backoffice

### Route riservate al gestore

| Pattern | Route name | Componente/handler |
|---|---|---|
| `/backoffice/admin/feature-flags` | `backoffice.admin.feature-flags` | `FeatureFlagManager` |
| `/backoffice/admin/feedback` | `backoffice.admin.feedback` | `FeedbackList` |
| `/backoffice/admin/plate-inventory` | `backoffice.admin.plate-inventory` | `PlateInventoryManager` |
| `/backoffice/communications/campaign` | `backoffice.communications.campaign` | `CommunicationCampaign` |
| `/backoffice/reports/financial` | `backoffice.reports.financial` | `FinancialReport` |
| `/backoffice/reports/manager` | `backoffice.reports.manager` | `ManagerDashboard` |
| `/backoffice/subscriptions/export` | `backoffice.subscriptions.export` | closure CSV |
| `/backoffice/members/export` | `backoffice.members.export` | closure CSV |
| `/backoffice/reports/download/{file}` | `backoffice.reports.download` | closure download |

Gate `access-admin-section` (AppServiceProvider:87): `$user->hasRole('gestore')`.

### Menu AdminLTE — struttura attuale e punto di inserimento

Menu definito in `config/adminlte.php`, array `'menu'` (riga 310).
Sezioni presenti:

```
Dashboard
GESTIONALE — Tesserati / Abbonamenti / Scadenze / Check-in / Accessi
TRAINING    — Esercizi / Schede template / Mesocicli / Report allenamento
CALENDARIO  — Calendario / Disponibilità / Prenotazioni PT / Orari di apertura
CORSI COLLETTIVI (can: view-group-classes)
COMUNICAZIONE (can: send-campaigns)
ADMIN (can: access-admin-section) — Inventario Dischi
```

Feature Flags e Feedback List non hanno voce sidebar.

**Punto di inserimento "Impostazioni":** aggiungere header `IMPOSTAZIONI` con `'can' => 'access-admin-section'`
dopo la sezione ADMIN (o sostituirla/ampliarla). Le voci sotto:
- Feature Flags → `/backoffice/settings/feature-flags`
- Orari di apertura → già esistente sotto CALENDARIO, da valutare se spostare

### Permessi Spatie esistenti

Nessun permesso `settings.manage` definito. Tutto il controllo accessi usa ruoli:
`gestore`, `trainer`, `receptionist`, `atleta` (definiti via `role:X` middleware o `hasRole()`).

Nessuna tabella `permissions` popolata con permessi granulari per i settings.
Se si vuole distinzione futura (es. receptionist può modificare orari ma non flag), serve creare
il permesso `settings.manage` e assegnarlo ai ruoli pertinenti.

### Pattern tab nel backoffice

Pattern consolidato in `AthleteProfile` — Alpine.js con nav-tabs Bootstrap:

```html
<div x-data="{ tab: 'storico' }">
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link" :class="{ active: tab === 'storico' }"
               href="#" @click.prevent="tab = 'storico'">...</a>
        </li>
        ...
    </ul>
    <div x-show="tab === 'storico'">...</div>
    <div x-show="tab === 'altro'" x-cloak>...</div>
</div>
```

File di riferimento: `resources/views/livewire/backoffice/athletes/athlete-profile.blade.php:1`.

---

## Fase 4 — Prerequisiti manualistica

### league/commonmark

```
league/commonmark 2.8.2
```

Presente come dipendenza transitiva di Laravel. `Str::markdown()` utilizzabile senza
aggiungere pacchetti.

### Pagine Blade statiche nel backoffice

Nessuna trovata. Tutte le view backoffice sono Livewire component view (wrapper `<div>`).
L'unica view statica rilevante è `resources/views/profile.blade.php` (Breeze) e
`resources/views/welcome.blade.php`.

Pattern per pagine statiche: non esiste ancora. Creare una view con
`->layout('layouts.backoffice')` da un componente Livewire minimale (solo `render()`)
oppure, per contenuto puramente statico, usare `Route::view()` con il layout AdminLTE
che richiede il componente `@extends`.

### Sezioni funzionali del backoffice (futuro indice manualistica)

Derivate da route e menu:

1. Dashboard
2. Tesserati e anagrafica
3. Abbonamenti
4. Accessi e check-in
5. Scadenze
6. Esercizi e catalogo
7. Schede template e mesocicli
8. Progressione e volume landmarks
9. Calendario e disponibilità trainer
10. Prenotazioni PT
11. Corsi collettivi (flag-gated)
12. Comunicazione e campagne
13. Report allenamento
14. Report finanziari (flag-gated)
15. Inventario dischi
16. Feature flags / impostazioni sistema

---

## Rischi e dipendenze

| Rischio | Impatto | Note |
|---|---|---|
| Aggiungere gate `view-group-classes` sulle 3 route `/group-classes/*` | Basso — nessun test esistente testa quelle route con flag off | Chiude GAP-01, nessuna regressione attesa |
| Aggiungere `abort_unless(Feature::active('periodization_engine'))` in `applyProgression()` | Medio — i 6 test `MesocycleDetailTest` che testano `applyProgression` non attivano il flag → diventerebbero 403 | Bisogna aggiungere `Feature::activate('periodization_engine')` nei `beforeEach` dei test coinvolti |
| Chiudere GAP-03 (feature gate su route `/reports/manager`) | Basso — se esistono test ManagerDashboard che non attivano il flag, diventerebbero 403 | Verificare `tests/Feature/ManagerDashboardTest.php` |
| Spostare route `admin/feature-flags` → `settings/feature-flags` | Basso — i 5 test `GlobalFeatureFlagTest` usano `Livewire::test(FeatureFlagManager::class)` direttamente, non la route | Reggono senza modifiche; aggiungere redirect 301 dalla vecchia URL |
| Aggiungere flag globali via `GLOBAL_FLAGS` | Basso | Ogni nuovo flag globale richiede una riga iniziale in `settings` (via seeder o migration) altrimenti il default cade su `config()` |
| `in_app_feedback_enabled` non è su Pennant | Zero rischio immediato | Da decidere se migrarlo o lasciarlo su `.env` |
