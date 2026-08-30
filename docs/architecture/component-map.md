# iron-gym — Mappa componenti, route, observers, comandi

Riferimento rapido per navigare il codice senza dover leggere tutti i file.
Aggiornare dopo ogni aggiunta significativa.

---

## Route backoffice

Prefisso `/backoffice`, middleware `auth + role:gestore|trainer|receptionist`.

| Route name | URL | Componente Livewire | Ruoli aggiuntivi |
|---|---|---|---|
| `backoffice.dashboard` | `/backoffice/dashboard` | `Backoffice\Dashboard` | |
| `backoffice.search` | `/backoffice/search` | `Backoffice\Search\GlobalSearch` | |
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
| `backoffice.mesocycles.show` | `/backoffice/mesocycles/{mesocycleId}` | `Backoffice\Mesocycles\MesocycleDetail` | |
| `backoffice.athletes.volume-landmarks` | `/backoffice/athletes/{athleteId}/volume-landmarks` | `Backoffice\Mesocycles\VolumeLandmarkManager` | |
| `backoffice.athletes.measurements` | `/backoffice/athletes/{athleteId}/measurements` | `Backoffice\Athletes\BodyMeasurementForm` | |
| `backoffice.athletes.analytics` | `/backoffice/athletes/{athleteId}/analytics` | `Backoffice\Athletes\AthleteAnalytics` | |
| `backoffice.athletes.profile` | `/backoffice/athletes/{athleteId}/profile` | `Backoffice\Athletes\AthleteProfile` | `gestore\|trainer` |
| `backoffice.calendar.index` | `/backoffice/calendar` | `Backoffice\Calendar\TrainerCalendar` | |
| `backoffice.calendar.availability` | `/backoffice/calendar/availability` | `Backoffice\Calendar\AvailabilityManager` | |
| `backoffice.bookings.index` | `/backoffice/bookings` | `Backoffice\Calendar\BookingList` | |
| `backoffice.group-classes.index` | `/backoffice/group-classes` | `Backoffice\Calendar\GroupClassManager` | |
| `backoffice.group-classes.schedules` | `/backoffice/group-classes/schedules` | `Backoffice\Calendar\ClassScheduleManager` | |
| `backoffice.group-classes.catalog` | `/backoffice/group-classes/catalog` | `Backoffice\Calendar\GroupClassCatalog` | solo gestore per CRUD |
| `backoffice.athletes.messages` | `/backoffice/athletes/{athleteId}/messages` | `Backoffice\Messages\MessageThread` | |
| `backoffice.communications.campaign` | `/backoffice/communications/campaign` | `Backoffice\Communications\CommunicationCampaign` | |
| `backoffice.reports.manager` | `/backoffice/reports/manager` | `Backoffice\Reports\ManagerDashboard` | `gestore` |
| `backoffice.reports.financial` | `/backoffice/reports/financial` | `Backoffice\Reports\FinancialReport` | `gestore` |
| `backoffice.reports.training` | `/backoffice/reports/training` | `Backoffice\Reports\TrainingReport` | `gestore\|trainer` |
| `backoffice.settings.index` | `/backoffice/settings` | `Backoffice\Settings\SettingsHub` | `gestore` (can:access-admin-section) |
| `backoffice.settings.feature-flags` | `/backoffice/settings/feature-flags` | `Backoffice\Settings\FeatureFlagManager` | `gestore` (can:access-admin-section) |
| `backoffice.admin.feedback` | `/backoffice/admin/feedback` | `Backoffice\Admin\FeedbackList` | `gestore` |
| `backoffice.admin.plate-inventory` | `/backoffice/admin/plate-inventory` | `Backoffice\Admin\PlateInventoryManager` | `gestore` |
| `backoffice.reports.download` | `/backoffice/reports/download/{file}` | closure | `gestore` |
| `backoffice.members.expiry` | `/backoffice/members/expiry` | `Backoffice\Members\ExpiryDashboard` | `gestore\|receptionist` |
| `backoffice.checkin` | `/backoffice/checkin` | `Backoffice\Access\QuickCheckin` | |
| `backoffice.settings.opening-hours` | `/backoffice/settings/opening-hours` | `Backoffice\Settings\OpeningHoursManager` | |
| `backoffice.subscriptions.export` | `/backoffice/subscriptions/export` | closure CSV | `gestore` |
| `backoffice.members.export` | `/backoffice/members/export` | closure CSV | `gestore` |

Note: `/backoffice/admin/feature-flags` redirige con 301 a `/backoffice/settings/feature-flags`.

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
| `athlete.bookings` | `/athlete/bookings` | `Athlete\Booking` | `can:view-athlete-bookings` (pt_bookings OR group_classes) |
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
| `Access` | `QuickCheckin` | Check-in rapido: ricerca tesserato live, validazione cert+abbonamento, cronologia giornaliera |
| `Admin` | `FeedbackList` | Gestione feedback in-app (solo gestore) |
| `Athletes` | `AthleteProfile` | Contenitore profilo atleta con tab Alpine (storico, analytics, misurazioni, landmarks, messaggi) |
| `Athletes` | `AthleteSessionHistory` | Storico sessioni atleta lato backoffice, dettaglio inline con e1RM |
| `Athletes` | `AthleteAnalytics` | Grafici e1RM, volume settimanale, IMC/BF% |
| `Athletes` | `BodyMeasurementForm` | Misurazioni corporee lato backoffice |
| `Calendar` | `TrainerCalendar` | Vista settimanale FullCalendar.js, drag-and-drop slot |
| `Calendar` | `AvailabilityManager` | CRUD ricorrenze settimanali trainer |
| `Calendar` | `BookingList` | Lista prenotazioni PT con filtri e azioni |
| `Calendar` | `GroupClassManager` | Lista occorrenze corsi collettivi, gestione iscritti, check-in presenze, annullamento |
| `Calendar` | `ClassScheduleManager` | CRUD palinsesti ricorrenti (ClassSchedule): giorno, orario, trainer, date validita' |
| `Calendar` | `GroupClassCatalog` | CRUD definizioni corsi (GroupClass): nome, slug, durata, capienza, sala. Solo gestore. |
| `Communications` | `CommunicationCampaign` | Campagne comunicazione con segmentazione e invio batch |
| `Exercises` | `ExerciseList` | Lista esercizi con filtri e paginazione; cache Redis tag `exercises` |
| `Exercises` | `ExerciseDetail` | Scheda tecnica esercizio (breadcrumb, muscoli con progress bar, video). Binding su slug. |
| `Exercises` | `ExerciseForm` | CRUD esercizio con pivot exercise_muscle e exercise_equipment |
| `Members` | `MemberList` | Lista tesserati con link a profilo allenamento |
| `Members` | `ExpiryDashboard` | Pannello scadenze: certificati medici e abbonamenti in scadenza con filtri temporali |
| `Members` | `MemberForm` | CRUD anagrafica tesserato; sezione opzionale "Crea account accesso app" (crea User + ruolo atleta in un unico submit) |
| `Mesocycles` | `MesocycleList` | Lista mesocicli con link a profilo atleta e dettaglio |
| `Mesocycles` | `MesocycleDetail` | Tabella volume per muscolo, progressione, forza deload; gated su `periodization_engine` |
| `Mesocycles` | `MesocycleAssign` | Assegnazione template a atleta con data inizio e numero settimane |
| `Mesocycles` | `VolumeLandmarkManager` | CRUD MEV/MAV/MRV per atleta-muscolo |
| `Admin` | `PlateInventoryManager` | CRUD inline dischi (`PlateInventory`) e manubri (`DumbbellInventory`). Solo gestore. |
| `Search` | `GlobalSearch` | Ricerca globale: atleti, PT, template schede, mesocicli. Risultati per sezione. |
| `Messages` | `MessageThread` | Chat real-time trainer↔atleta (polling ogni 3s) |
| `Reports` | `ManagerDashboard` | KPI gestore: info-box, grafici Chart.js fatturato/piano/occupancy, churn. Solo gestore. |
| `Reports` | `FinancialReport` | Report mensile/trimestrale/annuale, export CSV e PDF. Solo gestore. |
| `Reports` | `TrainingReport` | Sessioni completate, volume medio, aderenza schede |
| `Settings` | `SettingsHub` | Hub impostazioni con tab "Funzioni" (feature flags) e "Manuale". Solo gestore. |
| `Settings` | `FeatureFlagManager` | Toggle 14 flag raggruppati per gruppo (Moduli/Sessione atleta/Sistema). Solo gestore. |
| `Settings` | `ManualViewer` | Renderer manuale Markdown embedded nel tab "Manuale" di SettingsHub |
| `Settings` | `OpeningHoursManager` | CRUD orari apertura settimanali + eccezioni per data. Accessibile a gestore e receptionist. |
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
| `Dashboard` | Hero card sessione prossima/in-corso, striscia settimana mesociclo, recap ultimo allenamento, empty state contestuali |
| `WorkoutSession` | Logging live un-esercizio-alla-volta (nav prev/next/jump-drawer); readiness check pre-sessione; modulazione carichi; quick-log; previous performance inline; sostituzione esercizio guidata; rest timer Alpine; warmup generator; export sessione completata |
| `SessionFeedbackForm` | Feedback post-sessione (pump, soreness, effort, joint pain, performance) scala 0-3 |
| `TrainingHub` | Hub storico: tab Storico (sessioni completate e saltate) + Progressi + Misurazioni |
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
| `PtBookingObserver` | `PtBooking` | `saved/deleted` → invalida cache slot trainer e tag KPI. Nessuna notifica inviata. |
| `SubscriptionObserver` | `Subscription` | `create/update` → invalida cache KPI tag `kpi` |
| `TrainerAvailabilityObserver` | `TrainerAvailability` | `saved/deleted` → ricalcola slot disponibili |
| `TrainingSessionObserver` | `TrainingSession` | `update` → aggiorna `status`, `started_at`, `completed_at` |

---

## Servizi di dominio

Tutti in `app/Services/`.

| Servizio | Funzione |
|---|---|
| `MesocycleInstantiationService` | Crea gerarchia completa (mesocycle → weeks → sessions → exercises → sets) da template |
| `WeeklyVolumeCalculator` | Calcola hard set settimanali per muscolo pesati per `contribution_pct`. Restituisce status `below_mev/in_mav/approaching_mrv/over_mrv`. |
| `WeeklyProgressionService` | Applica progressione MEV→MRV settimana per settimana. Su deload: volume -50%, carico -10%. |
| `DeloadEvaluator` | Aggrega feedback ultime 2 settimane; trigger: joint_pain ≥ 2 per 2 settimane, MRV raggiunto, RIR drift, fine mesociclo. |
| `KpiService` | Metriche aggregate: revenue per periodo/piano/trainer, occupancy, nuovi tesserati, retention, churn. Cache Redis tag `kpi` TTL 1h. |
| `PtBookingService` | Prenotazioni PT con verifica disponibilità slot trainer. |
| `ClassBookingService` | Iscrizioni corsi collettivi con gestione waitlist. |
| `E1rmCalculator` | Formula Epley: `w * (1 + r/30)`. |
| `PlateLoadoutCalculator` | Algoritmo greedy decrescente su `PlateInventory` attivi; `delta_kg=0` se combinazione esatta, altrimenti combinazione per difetto. |
| `PersonalRecordDetector` | `check(ExerciseSet, athleteId)` — rileva PR e1RM dopo soglie configurabili (`config/pr.php`). Sincrono, pronto per migrazione a evento+listener. |
| `ExerciseSubstitutionFinder` | `find(Exercise)` → Collection max 5 candidati. Match su stesso `joint_action_id` o `compound_pattern_id` + stesso `measurement_type`. Overlap = somma min(pct_orig, pct_cand) su muscoli comuni; tie-break: stesso mechanic poi skill_level. |
| `ReadinessEvaluator` | `evaluate(SessionReadinessCheck)` → `ReadinessProposal`. Score 0-12 (4 campi TINYINT 0-3). Soglie in `config/readiness.php` (≥9=none, 5-8=reduce_5pct, <5=reduce_10pct). `applyReduction(float, int): float` arrotonda a 2.5 kg. |
| `SessionRecapBuilder` | `build(TrainingSession, athleteId)` → array con `duration_minutes`, `tonnage_kg`, `sets_completed/sets_prescribed`, `prs`, `top_muscles` (top 3 per SUM contribution_pct). 5 query, nessun N+1. |

---

## Artisan commands

| Comando | Classe | Descrizione | Schedule |
|---|---|---|---|
| `pilot:init` | `PilotInitCommand` | Esegue `PilotSeeder` con conferma interattiva (piani abbonamento reali + account gestore) | manuale |
| `pwa:generate-icons` | `GeneratePwaIcons` | Genera icone PWA da `resources/images/icon.png` (192px, 512px, maskable) | manuale |
| `members:notify-inactive` | `InactiveMembersCommand` | Identifica tesserati inattivi da N giorni, mette in coda campagna automatica | schedulato |
| `kpi:summary` | `KpiSummaryCommand` | Genera e invia per email report KPI mensile | schedulato 1° del mese |
| `classes:generate-occurrences` | `GenerateClassOccurrences` | Genera `ClassOccurrence` dal palinsesto attivo per N giorni futuri; idempotente (unique su schedule+date) | schedulato giornalmente |
| `classes:send-reminders` | `SendClassReminders` | Invia `ClassReminderNotification` agli iscritti con occorrenze il giorno successivo | schedulato `dailyAt('08:00')` |

---

## Seeder

| Seeder | Eseguito in | Contenuto |
|---|---|---|
| `RoleSeeder` | sempre | Ruoli Spatie: `gestore`, `trainer`, `receptionist`, `atleta` |
| `ExerciseSeeder` | sempre | Carica `database/seeders/sql/exercises_seed.sql` via `DB::unprepared()` (83 esercizi, 26 muscoli, 14 equipment, 27 pattern) |
| `CoreDemoSeeder` | solo `local` | Account staff (gestore/trainer/receptionist/atleta), tesserati, abbonamenti, accessi demo |
| `PilotSeeder` | via `pilot:init` | Piani abbonamento reali + account gestore da env |
| `PlateInventorySeeder` | `db:seed` | Dischi reali: 20/15/10/5/2.5/1.25 kg per lato |
| `DumbbellInventorySeeder` | `db:seed` | Manubri standard (2–60 kg) |
| `TemplateSeeder` | manuale | Template PPL Ipertrofia Intermediato (4 sett.) + 4 template dimostrativi; idempotente |
| `ExerciseDescriptionSeeder` | `db:seed` | Popola `execution_description` su tutti e 83 esercizi |
| `CommunicationTemplateSeeder` | `db:seed` | Template messaggi automatici (scadenza abbonamento, certificato medico, promemoria sessione) |
| `ClassDemoSeeder` | `db:seed` | Corsi collettivi demo (yoga, spinning, zumba), palinsesti, occorrenze future, prenotazioni PT demo |
| `SettingsFlagSeeder` | `db:seed` | Inizializza tutte le chiavi `settings` per i 14 flag gestibili; idempotente (`firstOrCreate`) |
| `FeedbackDemoSeeder` | `db:seed` | 8 feedback in-app demo (4 da atleta@atleta.atleta) |
| `TrainingDemoSeeder` | `db:seed` | Storico sessioni di allenamento con set e feedback per gli atleti demo |
| `VolumeDemoSeeder` | `db:seed` | 50 atleti con 12 settimane di storico (PR, misurazioni, iscrizioni corsi) per report e analytics |
| `VolumeLandmarkDemoSeeder` | `db:seed` | Landmark MEV/MAV/MRV personalizzati per atleta ID=5 (visibilita' pulsante "Ripristina default") |
| `OpeningHoursSeeder` | `db:seed` | Orari apertura default lun–ven 06:30–22:30, sab 08:00–18:00, dom 10:00–14:00; idempotente (`firstOrCreate`) |
| `ScenarioDemoSeeder` | solo `local/test` | 4 scenari per piano test funzionale (yoga waitlist, accessi esauriti, overlap trainer, occorrenza passata) |

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
| `layouts/athlete.blade.php` | Tutti i componenti atleta | Dark (#121212), bottom nav 4 tab (Home/Allenamento/Progressi/Profilo), PWA meta tags, viewport-fit=cover safe-area |

---

## Feature flags (Laravel Pennant)

Definiti in `AppServiceProvider::boot()` via `Setting::bool()`. Fonte di verita': tabella `settings`.
Metadati (label, platea, chiave settings, default, gruppo): `config/features.php → managed_flags`.
Gestione UI: `/backoffice/settings/feature-flags` (solo gestore).
Nota: `/backoffice/admin/feature-flags` fa 301 redirect alla route sopra.

Directive Blade: `@feature('flag') ... @endfeature`.

### Moduli

| Flag | Platea | Default pilota |
|---|---|---|
| `group_classes` | Tutta la palestra (globale) | ON |
| `messaging` | Tutta la palestra (globale) | ON |
| `pt_bookings` | Atleti (globale) | ON |

### Sessione atleta

| Flag | Platea | Default pilota |
|---|---|---|
| `readiness_check` | Atleti (globale) | ON |
| `exercise_substitution` | Atleti (globale) | ON |
| `session_recap` | Atleti (globale) | ON |
| `personal_records` | Atleti (globale) | ON |
| `weekly_volume` | Atleti (globale) | ON |
| `plate_calculator` | Atleti (globale, nessun gating point UI) | ON |

### Sistema

| Flag | Platea | Default pilota |
|---|---|---|
| `financial_reports` | Solo gestore | ON |
| `periodization_engine` | Gestore + trainer in `FEATURE_BETA_TRAINERS` | ON |
| `push_notifications` | Atleti e trainer | OFF |
| `outbound_notifications` | Tutta la palestra (kill switch globale email/push) | ON |
| `in_app_feedback` | Tutti gli utenti (globale) | OFF |
