# HK01 Audit Report — 2026-08-22

Branch: `release/hk01-housekeeping`
Tool: `shipmonk/dead-code-detector` v1.3, `shipmonk/composer-dependency-analyser` v1.8
PHPStan: 2.2.2, Larastan: 3.10.0, PHP: 8.3.29

---

## Riepilogo

| Categoria | CERTO | PROBABILE | FALSO_POSITIVO |
|---|---|---|---|
| A. Codice morto PHP | 2 | 5 | 28 |
| B. View / Componenti orfani | 3 | 0 | 0 |
| C. Dipendenze Composer | 3 | 1 | 4 |
| D. Documentazione | 6 | 1 | 0 |
| E. Config/Scope inutilizzati | 1 | 3 | 0 |

---

## A. Codice morto PHP (dead-code-detector + manuale)

PHPStan con dead-code-detector ha prodotto 30 segnalazioni. La quasi totalità riguarda componenti Livewire (vedi sezione **Falsi Positivi Livewire** in fondo). Riportate di seguito solo le segnalazioni non Livewire e le due Livewire confermate.

| File | Riga | Tipo | Classificazione | Evidenza |
|---|---|---|---|---|
| `app/Livewire/Athlete/Dashboard.php` | 144 | Metodo `sessionStatusClass` | **CERTO** | Non trovato in nessun Blade; la view usa inline ternaries per le classi CSS di stato sessione |
| `app/Livewire/Athlete/Dashboard.php` | 158 | Metodo `sessionStatusLabel` | **CERTO** | Non trovato in nessun Blade; le etichette stato sono gestite inline nel template |
| `app/Channels/WebPushChannel.php` | 12 | Metodo `send` | FALSO_POSITIVO | Registrato via `Notification::extend('webpush', ...)` in AppServiceProvider:102; chiamato dal framework quando una notification usa il canale 'webpush' (WaitlistPromotionNotification, SessionReminderNotification) |
| `app/Http/Controllers/ProgressPhotoController.php` | 14 | Metodo `show` | FALSO_POSITIVO | Registrato in route `athlete.photos.show` (routes/athlete.php:33); il detector non riconosce route binding su stringa |
| `app/Http/Controllers/PushSubscriptionController.php` | 12 | Metodo `store` | FALSO_POSITIVO | Registrato in route `athlete.push-subscribe` (routes/athlete.php); stesso motivo sopra |

**Note:** Le restanti 25 segnalazioni del detector riguardano componenti Livewire e sono tutti falsi positivi (vedi sezione apposita).

---

## B. View / Componenti orfani

| Path | Tipo | Classificazione | Evidenza |
|---|---|---|---|
| `resources/views/dashboard.blade.php` | Blade view | **CERTO** | La route `dashboard` è una closure che reindirizza sempre (atleta → `athlete.dashboard`, altri → `backoffice.dashboard`); la view non viene mai renderizzata. Stub Breeze residuo. |
| `app/Livewire/Athlete/Progress.php` | Componente Livewire | **CERTO** | La route `athlete.progress` (routes/athlete.php:30) è un redirect a `athlete.history`. Il componente `Progress` non è mai istanziato da nessuna route né da `@livewire`. |
| `resources/views/livewire/athlete/progress.blade.php` | Blade view | **CERTO** | View compagna del componente `Athlete\Progress` (orfano). Renderizzata solo da `Progress::render()` che non viene mai chiamato. |

---

## C. Dipendenze Composer

### Unused (dichiarate in composer.json, nessun uso trovato dallo scanner)

| Pacchetto | Sezione | Classificazione | Note |
|---|---|---|---|
| `laravel/tinker` | `require` (prod) | **CERTO** | Tool di debug, nessuna classe PHP referenziata nel codebase. Da spostare in `require-dev` o rimuovere. |
| `jeroennoten/laravel-adminlte` | `require` | FALSO_POSITIVO | Il pacchetto fornisce Blade views e config usati estensivamente nel backoffice. Lo scanner non analizza `config/adminlte.php` né le view del vendor. |
| `predis/predis` | `require` | FALSO_POSITIVO | Client Redis PHP alternativo a `phpredis`. Usato quando `REDIS_CLIENT=predis` in `.env`. La dipendenza è legittima anche se non c'è uso diretto di classi PHP. |
| `spatie/laravel-backup` | `require` | FALSO_POSITIVO | Usato via comandi artisan schedulati in `routes/console.php:23-24` (`backup:clean`, `backup:run`). Lo scanner non analizza chiamate via stringa. |

### Shadow (usate ma non dichiarate in composer.json)

| Pacchetto | Classificazione | Evidenza |
|---|---|---|
| `ext-gd` | **CERTO** | Usato in `app/Console/Commands/GeneratePwaIcons.php:48` (`imagealphablending`, +11 chiamate). Deve essere aggiunto a `require`. |
| `ext-mbstring` | **CERTO** | Usato in `app/Livewire/Backoffice/Search/GlobalSearch.php:32` (`mb_strlen`, +1). Deve essere aggiunto a `require`. |
| `nesbot/carbon` | PROBABILE | 77+ utilizzi nel codebase (`Carbon\Carbon`). È dipendenza transitiva di Laravel; aggiungerla esplicitamente garantisce version pinning. Decisione non urgente. |
| `symfony/http-foundation` | FALSO_POSITIVO | `BinaryFileResponse` usato in `ProgressPhotoController`. Dipendenza transitiva di Laravel, non necessario dichiararla. |

### Misplaced (require-dev usata in codice di produzione)

| Pacchetto | Classificazione | Evidenza |
|---|---|---|
| `laravel/telescope` | **CERTO** | `app/Providers/TelescopeServiceProvider.php` importa direttamente `Laravel\Telescope\*` a livello di classe. Il provider è registrato **incondizionatamente** in `bootstrap/providers.php:9`. In un deploy senza `require-dev` (es. `composer install --no-dev`) il server crasha con class-not-found. Il fix corretto è aggiungere un controllo `class_exists` in register() o spostare telescope in `require`. |

---

## D. Documentazione

| Doc | Sezione | Problema | Classificazione |
|---|---|---|---|
| `docs/domain/glossary.md` | Riga 28, colonna "Session" | Dice `tabella 'sessions'` — il nome canonico è `training_sessions` | **CERTO** |
| `docs/domain/step-0-discovery.md` | Riga 253 | Dice `Su 'sessions' e tabelle figlie no` — stesso errore | **CERTO** |
| `CHANGELOG.md` | Riga 307 | `e1RM calcolato da WeeklyVolumeCalculator con formula Epley` — ERRATO. La formula Epley vive in `app/Services/E1rmCalculator.php::epley()`. `WeeklyVolumeCalculator` calcola hard set per muscolo, non e1RM. | **CERTO** |
| `docs/architecture/component-map.md` | Riga 177 | `WeeklyVolumeCalculator ... Calcola e1RM (Epley)` — stessa attribuzione errata | **CERTO** |
| `docs/test/01-gestore.md` | Riga 152 | URL `/backoffice/athletes/{id}/sessions` non esiste come route. La session history è embedded in `AthleteProfile` all'URL `/backoffice/athletes/{athleteId}/profile` | **CERTO** |
| `CHANGELOG.md` | Intestazione e ordine voci | Il file non ha un'intestazione che dichiara l'ordine. Le voci usano tre schemi di numerazione incompatibili (`Step 0N`, `Release 0N`, `UX0N`) più voci senza numero con data (2026-06-22, 2026-06-25, 2026-06-27) e voci senza numero né data ("Audit funzionale", "Audit receptionist"). L'ordine non è né cronologico crescente né decrescente in modo coerente (es. Step 0-10 appare dopo Release 08 e UX07). | **CERTO** — discrepanza documentale, nessun impatto runtime |
| `CLAUDE.md` | Sezione "FASE 0 — Perimetro" | Elenca `STRUTTURA.md` nel perimetro ma il file non esiste nel repository | PROBABILE — da chiarire se il file è stato rimosso o mai creato |
| `CLAUDE.md` | Sezione "Cosa NON fare" | Vieta `model Workout/WorkoutExercise` ma non chiarisce che `app/Livewire/Athlete/WorkoutSession.php` è un componente Livewire per il logging live, non un Model Eloquent. Il nome "WorkoutSession" è diverso dai model vietati ma può generare confusione nei team. | PROBABILE — aggiungere una nota esplicativa |

---

## E. Config / Scope / Relazioni Eloquent inutilizzati

| File | Elemento | Classificazione | Evidenza |
|---|---|---|---|
| `config/barbell.php` | Intero file | **CERTO** | Nessuna occorrenza di `config('barbell.`)` in `app/`, `resources/`, `routes/`, `tests/`. Probabilmente residuo di una feature plate-calculator non completamente integrata con la selezione peso barra. |
| `app/Models/DumbbellInventory.php` | `scopeActive()` (riga 42) | PROBABILE | `PlateInventoryManager` query `DumbbellInventory::orderBy(...)->paginate(...)` senza scope. Nessuna altra occorrenza di `->active()` su questo model. |
| `app/Models/PtBooking.php` | `scopeActive()` (riga 82) | PROBABILE | `PtBooking` è interrogato via `where('status', ...)` diretto in BookingList e PtBookingService. Nessuna occorrenza di `PtBooking::...->active()` nel codebase. |
| `app/Models/PtBooking.php` | `scopeForDate()` (riga 93) | PROBABILE | `->forDate()` trovato solo su `TrainerAvailability` (in TrainerCalendar e nel modello stesso). Nessuna occorrenza di `PtBooking::...->forDate()`. |

---

## Falsi Positivi Livewire

Il dead-code-detector ha segnalato 25 metodi/proprietà in componenti Livewire come "non usati". Tutti sono FALSO_POSITIVO: il framework Livewire risolve i riferimenti a runtime via stringa (wire:click, wire:model, wire:submit, lifecycle hooks) e il detector non analizza HTML/Blade. Dettaglio:

| Componente | Elemento segnalato | Motivo falso positivo |
|---|---|---|
| `BodyMeasurementForm` | `mount()` | Lifecycle hook Livewire |
| `BodyMeasurementForm` | `save()` | `wire:submit="save"` in body-measurement-form.blade.php:10 |
| `BodyMeasurementForm` | `render()` | Lifecycle hook Livewire |
| `BodyMeasurementForm` | `$recentMeasurements` | Proprietà pubblica Livewire, scritta da `loadRecentMeasurements()` |
| `BodyMeasurementForm` | Tutte le `$*Cm`, `$notes`, `$measuredAt`, ecc. | `wire:model` in Blade |
| `Booking` | `mount()` | Lifecycle hook Livewire |
| `Booking` | `updatedSelectedDate()` | Lifecycle hook `updated*` — triggered da `wire:model.live="selectedDate"` |
| `Booking` | `updatedSelectedTrainerId()` | Lifecycle hook `updated*` — triggered da `wire:model.live="selectedTrainerId"` |
| `Booking` | `selectSlot()` | `wire:click="selectSlot()"` in booking.blade.php:87 |
| `Booking` | `bookPt()` | `wire:click="bookPt"` in booking.blade.php:100 |
| `Booking` | `cancelPtBooking()` | `wire:click="cancelPtBooking()"` in booking.blade.php:128 |
| `Booking` | `enrollClass()` | `wire:click="enrollClass()"` in booking.blade.php:195 |
| `Booking` | `cancelClassBooking()` | `wire:click="cancelClassBooking()"` in booking.blade.php:187 |
| `Booking` | `render()` | Lifecycle hook Livewire |
| `Booking` | `$activeTab`, `$availableSlots` | Usate via `wire:click="$set('activeTab','pt')"` e template Alpine; `$availableSlots` popola la lista slot |
| `Dashboard` | `restoreSession()` | `wire:click="restoreSession()"` in dashboard.blade.php:215 |
| `Dashboard` | `goalLabel()` | Chiamato direttamente nel Blade come `$this->goalLabel($activeMesocycle->goal)` (riga 129) |
| `Dashboard` | `mount()` | Lifecycle hook Livewire |
| `Dashboard` | `render()` | Lifecycle hook Livewire |
| `Dashboard` | `$certWarningLevel`, `$weekSessions`, `$nextSession`, `$lastTonnage`, `$lastSetsCompleted` | Proprietà pubbliche Livewire usate nel template dashboard.blade.php (righe 2, 65, 170, 176, 193) |
| `ExerciseCatalog` | `updatingSearch()` | Lifecycle hook `updating*` — triggered prima dell'aggiornamento della prop `$search` via `wire:model.live` |
| `ExerciseCatalog` | `$queryString` | Proprietà speciale Livewire per URL query string binding — non è codice utente |

---

## Appendice — Esecuzione tool

```
# Dead-code-detector (con rules.neon incluso in phpstan.neon)
php vendor/bin/phpstan analyse --memory-limit=512M app tests
# Exit: 1, 30 segnalazioni dead code + errori PHPStan livello 6 pre-esistenti

# Composer dependency analyser
php vendor/bin/composer-dependency-analyser
# Exit: 1
# Shadow: ext-gd, ext-mbstring, nesbot/carbon, symfony/http-foundation
# Unused: jeroennoten/laravel-adminlte, laravel/tinker, predis/predis, spatie/laravel-backup
# Misplaced: laravel/telescope (req-dev usato in prod)
```
