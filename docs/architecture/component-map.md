# iron-gym — Mappa componenti, route, observers, comandi

Riferimento rapido per navigare il codice senza dover leggere tutti i file.
Aggiornare dopo ogni aggiunta significativa.

---

## Route backoffice

Prefisso `/backoffice`, middleware `auth + role:gestore|trainer|receptionist`.

| Route name | URL | Componente Livewire | Ruoli aggiuntivi |
|---|---|---|---|
| `backoffice.dashboard` | `/backoffice/dashboard` | `Backoffice\Dashboard` | |
| `backoffice.members.index` | `/backoffice/members` | `Backoffice\Members\MemberList` | |
| `backoffice.members.create` | `/backoffice/members/create` | `Backoffice\Members\MemberForm` | |
| `backoffice.members.edit` | `/backoffice/members/{member}/edit` | `Backoffice\Members\MemberForm` | |
| `backoffice.subscriptions.index` | `/backoffice/subscriptions` | `Backoffice\Subscriptions\SubscriptionList` | |
| `backoffice.subscriptions.create` | `/backoffice/subscriptions/create` | `Backoffice\Subscriptions\SubscriptionForm` | |
| `backoffice.access-logs.index` | `/backoffice/access-logs` | `Backoffice\Access\AccessLogList` | |
| `backoffice.exercises.index` | `/backoffice/exercises` | `Backoffice\Exercises\ExerciseList` | |
| `backoffice.exercises.create` | `/backoffice/exercises/create` | `Backoffice\Exercises\ExerciseForm` | |
| `backoffice.exercises.show` | `/backoffice/exercises/{exercise:slug}` | `Backoffice\Exercises\ExerciseDetail` | |
| `backoffice.exercises.edit` | `/backoffice/exercises/{exercise:slug}/edit` | `Backoffice\Exercises\ExerciseForm` | |
| `backoffice.templates.index` | `/backoffice/templates` | `Backoffice\Templates\TemplateList` | |
| `backoffice.templates.create` | `/backoffice/templates/create` | `Backoffice\Templates\TemplateForm` | |
| `backoffice.templates.builder` | `/backoffice/templates/{template}/builder` | `Backoffice\Templates\TemplateBuilder` | |
| `backoffice.mesocycles.index` | `/backoffice/mesocycles` | `Backoffice\Mesocycles\MesocycleList` | |
| `backoffice.mesocycles.assign` | `/backoffice/mesocycles/assign` | `Backoffice\Mesocycles\MesocycleAssign` | |
| `backoffice.mesocycles.show` | `/backoffice/mesocycles/{mesocycle}` | `Backoffice\Mesocycles\MesocycleDetail` | |
| `backoffice.athletes.volume-landmarks` | `/backoffice/athletes/{athleteId}/volume-landmarks` | `Backoffice\Mesocycles\VolumeLandmarkManager` | |
| `backoffice.athletes.measurements` | `/backoffice/athletes/{athleteId}/measurements` | `Backoffice\Athletes\BodyMeasurementForm` | |
| `backoffice.athletes.analytics` | `/backoffice/athletes/{athleteId}/analytics` | `Backoffice\Athletes\AthleteAnalytics` | |
| `backoffice.athletes.profile` | `/backoffice/athletes/{athleteId}/profile` | `Backoffice\Athletes\AthleteProfile` | `gestore\|trainer` |
| `backoffice.calendar.index` | `/backoffice/calendar` | `Backoffice\Calendar\TrainerCalendar` | |
| `backoffice.calendar.availability` | `/backoffice/calendar/availability` | `Backoffice\Calendar\AvailabilityManager` | |
| `backoffice.bookings.index` | `/backoffice/bookings` | `Backoffice\Calendar\BookingList` | |
| `backoffice.group-classes.index` | `/backoffice/group-classes` | `Backoffice\Calendar\GroupClassManager` | |
| `backoffice.athletes.messages` | `/backoffice/athletes/{athleteId}/messages` | `Backoffice\Messages\MessageThread` | |
| `backoffice.communications.campaign` | `/backoffice/communications/campaign` | `Backoffice\Communications\CommunicationCampaign` | |
| `backoffice.reports.manager` | `/backoffice/reports/manager` | `Backoffice\Reports\ManagerDashboard` | `gestore` |
| `backoffice.reports.financial` | `/backoffice/reports/financial` | `Backoffice\Reports\FinancialReport` | `gestore` |
| `backoffice.reports.training` | `/backoffice/reports/training` | `Backoffice\Reports\TrainingReport` | `gestore\|trainer` |
| `backoffice.admin.feature-flags` | `/backoffice/admin/feature-flags` | `Backoffice\Admin\FeatureFlagManager` | `gestore` |
| `backoffice.admin.feedback` | `/backoffice/admin/feedback` | `Backoffice\Admin\FeedbackList` | `gestore` |
| `backoffice.reports.download` | `/backoffice/reports/download/{file}` | closure | `gestore` |

---

## Route atleta

Prefisso `/athlete`, middleware `auth + role:atleta`.

| Route name | URL | Componente / Handler |
|---|---|---|
| `athlete.dashboard` | `/athlete` | `Athlete\Dashboard` |
| `athlete.session` | `/athlete/session/{session}` | `Athlete\WorkoutSession` |
| `athlete.history` | `/athlete/history` | `Athlete\TrainingHub` |
| `athlete.progress` | `/athlete/progress` | redirect → `athlete.history` |
| `athlete.measurements` | `/athlete/measurements` | `Athlete\BodyMeasurementForm` |
| `athlete.photos.upload` | `/athlete/photos/upload` | `Athlete\ProgressPhotoUpload` |
| `athlete.photos.show` | `/athlete/photos/{progressPhoto}` | `ProgressPhotoController@show` |
| `athlete.exercises.index` | `/athlete/exercises` | `Athlete\ExerciseCatalog` |
| `athlete.exercises.show` | `/athlete/exercises/{exercise:slug}` | `Athlete\ExerciseDetail` |
| `athlete.bookings` | `/athlete/bookings` | `Athlete\Booking` |
| `athlete.profile` | `/athlete/profile` | `Athlete\Profile` |
| `athlete.messages` | `/athlete/messages` | `Athlete\Messages` |
| `athlete.messages.unread-count` | `/athlete/messages-unread-count` | closure JSON |
| `athlete.push-subscribe` | POST `/athlete/push-subscribe` | `PushSubscriptionController@store` |
| `athlete.volume` | `/athlete/volume` | `Athlete\WeeklyVolume` |
| `athlete.records` | `/athlete/records` | `Athlete\PersonalRecords` |
| `athlete.session.recap` | `/athlete/session/{session}/recap` | `Athlete\SessionRecap` |
| `athlete.session.sync` | POST `/athlete/session/sync` | `SyncBatchController@handle` |

---

## Componenti Livewire — backoffice

Tutti in `app/Livewire/Backoffice/`. Layout: `->layout('layouts.backoffice')`.

| Namespace | Componente | Funzione |
|---|---|---|
| (root) | `Dashboard` | Schermata iniziale backoffice |
| `Access` | `AccessLogList` | Registro accessi struttura |
| `Admin` | `FeatureFlagManager` | Toggle feature flags (solo gestore) |
| `Admin` | `FeedbackList` | Gestione feedback in-app (solo gestore) |
| `Athletes` | `AthleteProfile` | Contenitore profilo atleta con tab Alpine (storico, analytics, misurazioni, landmarks, messaggi) |
| `Athletes` | `AthleteSessionHistory` | Storico sessioni atleta lato backoffice, dettaglio inline con e1RM |
| `Athletes` | `AthleteAnalytics` | Grafici e1RM, volume settimanale, IMC/BF% |
| `Athletes` | `BodyMeasurementForm` | Misurazioni corporee lato backoffice |
| `Calendar` | `TrainerCalendar` | Vista settimanale FullCalendar.js, drag-and-drop slot |
| `Calendar` | `AvailabilityManager` | CRUD ricorrenze settimanali trainer |
| `Calendar` | `BookingList` | Lista prenotazioni PT con filtri e azioni |
| `Calendar` | `GroupClassManager` | CRUD corsi collettivi, lista iscritti |
| `Communications` | `CommunicationCampaign` | Campagne comunicazione con segmentazione e invio batch |
| `Exercises` | `ExerciseList` | Lista esercizi con filtri e paginazione; cache Redis tag `exercises` |
| `Exercises` | `ExerciseDetail` | Scheda tecnica esercizio (breadcrumb, muscoli con progress bar, video). Binding su slug. |
| `Exercises` | `ExerciseForm` | CRUD esercizio con pivot exercise_muscle e exercise_equipment |
| `Members` | `MemberList` | Lista tesserati con link a profilo allenamento |
| `Members` | `MemberForm` | CRUD anagrafica tesserato |
| `Mesocycles` | `MesocycleList` | Lista mesocicli con link a profilo atleta e dettaglio |
| `Mesocycles` | `MesocycleDetail` | Tabella volume per muscolo, progressione, forza deload; gated su `periodization_engine` |
| `Mesocycles` | `MesocycleAssign` | Assegnazione template a atleta con data inizio e numero settimane |
| `Mesocycles` | `VolumeLandmarkManager` | CRUD MEV/MAV/MRV per atleta-muscolo |
| `Messages` | `MessageThread` | Chat real-time trainer↔atleta (polling ogni 3s) |
| `Reports` | `ManagerDashboard` | KPI gestore: info-box, grafici Chart.js fatturato/piano/occupancy, churn. Solo gestore. |
| `Reports` | `FinancialReport` | Report mensile/trimestrale/annuale, export CSV e PDF. Solo gestore. |
| `Reports` | `TrainingReport` | Sessioni completate, volume medio, aderenza schede |
| `Shared` | `NotificationBell` | Campanella con contatore notifiche non lette |
| `Subscriptions` | `SubscriptionList` | Lista abbonamenti attivi |
| `Subscriptions` | `SubscriptionForm` | CRUD abbonamento |
| `Templates` | `TemplateList` | Lista template schede gym-wide |
| `Templates` | `TemplateForm` | CRUD template |
| `Templates` | `TemplateBuilder` | Builder drag-and-drop sessioni ed esercizi, set prescrittivi |

**Nota architetturale:** le view Livewire usano wrapper `<div>` (non `@extends`).
Il layout è gestito con `->layout('layouts.backoffice')` nel `render()`. Questo
è necessario per embeddare i componenti via `@livewire` in `AthleteProfile`.

---

## Componenti Livewire — atleta

Tutti in `app/Livewire/Athlete/`. Layout: `layouts.athlete` (dark, mobile-first, PWA).

| Componente | Funzione |
|---|---|
| `Dashboard` | Sessione odierna, prossimi allenamenti, accesso rapido |
| `WorkoutSession` | Logging live: peso/reps/RIR per set, timer riposo, note; sostituzione esercizio guidata in sessione |
| `SessionFeedbackForm` | Feedback post-sessione (pump, soreness, effort, joint pain, performance) scala 0-3 |
| `TrainingHub` | Hub storico: tab History + Progress + Measurements |
| `History` | Storico sessioni completate (embedded in TrainingHub) |
| `Progress` | Grafici Chart.js peso e BF% nel tempo (embedded in TrainingHub) |
| `BodyMeasurementForm` | Form misurazioni con storico tabellare |
| `ProgressPhotoUpload` | Upload foto progressi con preview |
| `ExerciseCatalog` | Catalogo esercizi in sola lettura per atleta |
| `ExerciseDetail` | Scheda esercizio per atleta |
| `Booking` | Lista slot disponibili, form prenotazione PT, iscrizione corsi |
| `Profile` | Profilo atleta: dati personali, cambio password, preferenze |
| `Messages` | Chat atleta↔trainer con badge messaggi non letti |
| `WeeklyVolume` | Body map SVG fronte/retro, barre volume vs landmark MEV/MAV/MRV, selettore settimana |
| `PersonalRecords` | Elenco PR e1RM paginato per esercizio, ordinati per data decrescente |
| `SessionRecap` | Card riepilogativa post-sessione (durata, tonnellaggio, set ratio, PR, top muscoli); export PNG via html-to-image + Web Share API |

## Componenti Livewire — shared

| Componente | Funzione | Incluso in |
|---|---|---|
| `Shared\InAppFeedback` | Widget flottante feedback in-app (tipo, testo, page URL) | Tutti i layout |
| `Backoffice\Shared\NotificationBell` | Campanella notifiche | Layout backoffice |

---

## Observers

Tutti in `app/Observers/`. Registrati in `AppServiceProvider`.

| Observer | Modello | Azioni |
|---|---|---|
| `ExerciseObserver` | `Exercise` | `create/update/delete` → flush cache tag `exercises` |
| `PtBookingObserver` | `PtBooking` | `update` (confermato/cancellato) → invia notifica atleta e trainer |
| `SubscriptionObserver` | `Subscription` | `create/update` → invalida cache KPI tag `kpi` |
| `TrainerAvailabilityObserver` | `TrainerAvailability` | `saved/deleted` → ricalcola slot disponibili |
| `TrainingSessionObserver` | `TrainingSession` | `update` → aggiorna `status`, `started_at`, `completed_at` |

---

## Servizi di dominio

Tutti in `app/Services/`.

| Servizio | Funzione |
|---|---|
| `MesocycleInstantiationService` | Crea gerarchia completa (mesocycle → weeks → sessions → exercises → sets) da template |
| `WeeklyVolumeCalculator` | Calcola hard set settimanali per muscolo pesati per `contribution_pct`. Restituisce status `below_mev/in_mav/approaching_mrv/over_mrv`. Calcola e1RM (Epley). |
| `WeeklyProgressionService` | Applica progressione MEV→MRV settimana per settimana. Su deload: volume -50%, carico -10%. |
| `DeloadEvaluator` | Aggrega feedback ultime 2 settimane; trigger: joint_pain ≥ 2 per 2 settimane, MRV raggiunto, RIR drift, fine mesociclo. |
| `KpiService` | Metriche aggregate: revenue per periodo/piano/trainer, occupancy, nuovi tesserati, retention, churn. Cache Redis tag `kpi` TTL 1h. |
| `PtBookingService` | Prenotazioni PT con verifica disponibilità slot trainer. |
| `ClassBookingService` | Iscrizioni corsi collettivi con gestione waitlist. |
| `E1rmCalculator` | Formula Epley: `w * (1 + r/30)`. |
| `PlateLoadoutCalculator` | Algoritmo greedy decrescente su `PlateInventory` attivi; `delta_kg=0` se combinazione esatta, altrimenti combinazione per difetto. |
| `PersonalRecordDetector` | `check(ExerciseSet, athleteId)` — rileva PR e1RM dopo soglie configurabili (`config/pr.php`). Sincrono, pronto per migrazione a evento+listener. |

---

## Artisan commands

| Comando | Classe | Descrizione | Schedule |
|---|---|---|---|
| `pilot:init` | `PilotInitCommand` | Esegue `PilotSeeder` con conferma interattiva (piani abbonamento reali + account gestore) | manuale |
| `pwa:generate-icons` | `GeneratePwaIcons` | Genera icone PWA da `resources/images/icon.png` (192px, 512px, maskable) | manuale |
| `members:notify-inactive` | `InactiveMembersCommand` | Identifica tesserati inattivi da N giorni, mette in coda campagna automatica | schedulato |
| `kpi:summary` | `KpiSummaryCommand` | Genera e invia per email report KPI mensile | schedulato 1° del mese |

---

## Seeder

| Seeder | Eseguito in | Contenuto |
|---|---|---|
| `RoleSeeder` | sempre | Ruoli Spatie: `gestore`, `trainer`, `receptionist`, `atleta` |
| `ExerciseSeeder` | sempre | Carica `database/seeders/sql/exercises_seed.sql` via `DB::unprepared()` (83 esercizi, 26 muscoli, 14 equipment, 27 pattern) |
| `DemoSeeder` | solo `local` | Utenti di test, dati fittizi per sviluppo locale |
| `PilotSeeder` | via `pilot:init` | Piani abbonamento reali + account gestore da env |
| `PlateInventorySeeder` | `db:seed` | Dischi reali: 20/15/10/5/2.5/1.25 kg per lato |
| `PilotTemplateSeeder` | manuale | Template PPL Ipertrofia Intermediato (4 sett.) con 3 sessioni/sett. e progressione automatica |

---

## Controllers HTTP

Usati solo per operazioni non-Livewire.

| Controller | Route | Funzione |
|---|---|---|
| `ProgressPhotoController` | `GET /athlete/photos/{progressPhoto}` | Serve foto con URL firmati dal disco locale |
| `PushSubscriptionController` | `POST /athlete/push-subscribe` | Salva endpoint VAPID per Web Push |
| `SyncBatchController` | `POST /athlete/session/sync` | Riceve batch operazioni offline (quick_log, complete_set, generate_warmup, delete_warmup); idempotenza via `sync_operations.client_uuid` |

---

## Layout

| File | Usato da | Note |
|---|---|---|
| `layouts/backoffice.blade.php` | Tutti i componenti backoffice | AdminLTE 3, flash messages Alpine.js |
| `layouts/athlete.blade.php` | Tutti i componenti atleta | Dark (#121212), bottom nav a 6 voci, PWA meta tags |

---

## Feature flags (Laravel Pennant)

Definiti in `AppServiceProvider::defineFeatureFlags()`. Tabella `features` nel DB.

| Flag | Attivo per | Default |
|---|---|---|
| `periodization_engine` | gestore o email in `FEATURE_BETA_TRAINERS` | per utente |
| `push_notifications` | atleti e trainer | per utente |
| `group_classes` | `FEATURE_GROUP_CLASSES=true` | false globale |
| `financial_reports` | solo gestore | per utente |

Directive Blade: `@feature('flag') ... @endfeature`.
Gestione UI: `/backoffice/admin/feature-flags` (solo gestore).
