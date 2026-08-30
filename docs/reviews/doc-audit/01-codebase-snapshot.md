# DOC01 — Snapshot fattuale del codice

**Data:** 2026-08-23
**Fase:** 2 di 6 — READ-ONLY
**Fonte:** tutti i dati provengono da file in `.tmp/doc-audit/` o da output di comandi eseguiti in questa
sessione. Nessuna voce è derivata da CLAUDE.md o dalla memoria.

---

## Modelli Eloquent

**Conteggio:** 39 file in `app/Models/`

Tutti usano la convenzione Laravel (nessun `$table` esplicito); il nome tabella si deriva pluralizzando
il nome del modello in snake_case.

| Modello | Tabella derivata per convenzione |
|---|---|
| AccessLog | access_logs |
| AthleteVolumeLandmark | athlete_volume_landmarks |
| BodyMeasurement | body_measurements |
| ClassBooking | class_bookings |
| CommunicationLog | communication_logs |
| CommunicationTemplate | communication_templates |
| DumbbellInventory | dumbbell_inventory |
| Equipment | equipment |
| Exercise | exercises |
| ExerciseMuscle | exercise_muscle |
| ExerciseSet | exercise_sets |
| FeedbackSubmission | feedback_submissions |
| GroupClass | group_classes |
| Member | members |
| Mesocycle | mesocycles |
| Message | messages |
| MicrocycleWeek | microcycle_weeks |
| MovementPattern | movement_patterns |
| Muscle | muscles |
| OpeningHour | opening_hours |
| PersonalRecord | personal_records |
| PlateInventory | plate_inventory |
| ProgressPhoto | progress_photos |
| PtBooking | pt_bookings |
| PushSubscription | push_subscriptions |
| SessionExercise | session_exercises |
| SessionExerciseFeedback | session_exercise_feedbacks |
| SessionExerciseGroup | session_exercise_groups |
| SessionFeedback | session_feedbacks |
| SessionReadinessCheck | session_readiness_checks |
| Subscription | subscriptions |
| SubscriptionPlan | subscription_plans |
| SyncOperation | sync_operations |
| TemplateSession | template_sessions |
| TemplateSessionExercise | template_session_exercises |
| TrainerAvailability | trainer_availability |
| TrainingSession | training_sessions |
| User | users |
| WorkoutTemplate | workout_templates |

**Nota:** `DumbbellInventory` esiste come modello ma non ha seeder attivo e non è citato in CLAUDE.md
nella sezione entità principali. Da chiarire in fase 3.

---

## Tabelle create nelle migration

**Conteggio:** 54 tabelle (da `Schema::create` in `database/migrations/`)

```
access_logs
athlete_volume_landmarks
body_measurements
cache
cache_locks
class_bookings
communication_logs
communication_templates
dumbbell_inventory
equipment
exercise_equipment
exercise_muscle
exercise_sets
exercises
failed_jobs
features
feedback_submissions
group_classes
job_batches
jobs
members
mesocycles
messages
microcycle_weeks
model_has_permissions
model_has_roles
movement_patterns
muscles
notifications
opening_hours
password_reset_tokens
permissions
personal_records
plate_inventory
progress_photos
pt_bookings
push_subscriptions
role_has_permissions
roles
session_exercise_feedbacks
session_exercise_groups
session_exercises
session_feedbacks
session_readiness_checks
sessions
subscription_plans
subscriptions
sync_operations
template_session_exercises
template_sessions
trainer_availability
training_sessions
users
workout_templates
```

**Nota:** la tabella `sessions` esiste (creata da migrazione Laravel standard per session driver DB) ed è
distinta da `training_sessions`. Rilevante per il finding in `glossary.md` (.claude/ versione).

---

## Route

**Totale:** 119 route (include route di framework/pacchetti)

| Gruppo | Conteggio | Note |
|---|---|---|
| `athlete/*` | 18 | Route applicative PWA atleta |
| `backoffice/*` | 36 | Route applicative backoffice |
| `telescope/*` | 44 | Route Laravel Telescope (dev tool) |
| `livewire/*` + `adminlte/*` | 6 | Route interne Livewire e AdminLTE |
| Auth (login, register, ecc.) | 9 | Route Breeze |
| App generiche (`/`, `dashboard`, `health`, `storage/*`, `up`) | 6 | Route applicative top-level |

**Route applicative totali (escludendo telescope/livewire-internal):** 69

### Route athlete (18)
```
GET  athlete
GET  athlete/bookings
GET  athlete/exercises
GET  athlete/exercises/{exercise:slug}
GET  athlete/history
GET  athlete/measurements
GET  athlete/messages
GET  athlete/messages-unread-count
GET  athlete/photos/upload
GET  athlete/photos/{progressPhoto}       (DELETE su stesso path)
GET  athlete/profile
GET  athlete/progress
POST athlete/push-subscribe
GET  athlete/records
POST athlete/session/sync
GET  athlete/session/{session}
GET  athlete/session/{session}/recap
GET  athlete/volume
```

### Route backoffice (36) — elenco completo in `.tmp/doc-audit/routes.json`

---

## Componenti Livewire

**Totale file in `app/Livewire/`:** 52

| Gruppo | Conteggio |
|---|---|
| `Athlete/` | 14 componenti |
| `Backoffice/` | 35 componenti |
| `Actions/` | 1 (Logout — da Breeze, non componente UI) |
| `Forms/` | 1 (LoginForm — da Breeze) |
| `Shared/` | 1 (InAppFeedback) |

### Athlete (14)
```
BodyMeasurementForm, Booking, Dashboard, ExerciseCatalog, ExerciseDetail,
Messages, PersonalRecords, Profile, ProgressPhotoUpload, SessionFeedbackForm,
SessionRecap, TrainingHub, WeeklyVolume, WorkoutSession
```

### Backoffice (35) — per area
```
Access:       AccessLogList
Admin:        FeatureFlagManager, FeedbackList, PlateInventoryManager
Athletes:     AthleteAnalytics, AthleteProfile, AthleteSessionHistory, BodyMeasurementForm
Calendar:     AvailabilityManager, BookingList, GroupClassManager, TrainerCalendar
Communications: CommunicationCampaign
Dashboard:    Dashboard
Exercises:    ExerciseDetail, ExerciseForm, ExerciseList
Members:      MemberForm, MemberList
Mesocycles:   MesocycleAssign, MesocycleDetail, MesocycleList, VolumeLandmarkManager
Messages:     MessageThread
Reports:      FinancialReport, ManagerDashboard, TrainingReport
Search:       GlobalSearch
Settings:     OpeningHoursManager
Shared:       NotificationBell
Subscriptions: SubscriptionForm, SubscriptionList
Templates:    TemplateBuilder, TemplateForm, TemplateList
```

---

## Servizi (`app/Services/`)

**Conteggio:** 13

```
ClassBookingService
DeloadEvaluator
E1rmCalculator
ExerciseSubstitutionFinder
KpiService
MesocycleInstantiationService
PersonalRecordDetector
PlateLoadoutCalculator
PtBookingService
ReadinessEvaluator
SessionRecapBuilder
WeeklyProgressionService
WeeklyVolumeCalculator
```

---

## Observer (`app/Observers/`)

**Conteggio:** 5

```
ExerciseObserver
PtBookingObserver
SubscriptionObserver
TrainerAvailabilityObserver
TrainingSessionObserver
```

---

## Policy

**Conteggio:** 0 — la cartella `app/Policies/` non esiste. Autorizzazioni gestite via `abort_unless`
inline nei componenti Livewire e gate definiti in `AppServiceProvider`.

---

## Comandi Artisan (`app/Console/Commands/`)

**Conteggio:** 4

```
GeneratePwaIcons
InactiveMembersCommand
KpiSummaryCommand
PilotInitCommand
```

---

## Job (`app/Jobs/`)

**Conteggio:** 8

```
ExportFinancialReportCsv
ExportMembersListCsv
HealthCheckJob
NotifyWaitlistPromotion
SendCampaignMessages
SendMedicalCertExpiryReminders
SendSessionReminders
SendSubscriptionExpiryReminders
```

---

## Middleware custom

**Conteggio:** 0 — la cartella `app/Http/Middleware/` non esiste. Middleware personalizzati non
presenti; il progetto usa i middleware di framework/pacchetti (auth, role, can) via `bootstrap/app.php`.

---

## Form Request (`app/Http/Requests/`)

**Conteggio:** 1

```
SyncBatchRequest
```

**Nota:** la validazione negli altri componenti Livewire avviene tramite `$rules` inline nei componenti,
non tramite Form Request dedicate.

---

## Controller (`app/Http/Controllers/`)

**Conteggio:** 6

```
Auth/VerifyEmailController
Controller (base)
HealthCheckController
ProgressPhotoController
PushSubscriptionController
SyncBatchController
```

---

## Seeder (`database/seeders/`)

**Conteggio:** 16

```
ActiveMesocycleSeeder
BookingDemoSeeder
CommunicationTemplateSeeder
DatabaseSeeder
DemoSeeder
DemoTemplatesSeeder
DumbbellInventorySeeder
ExerciseDescriptionSeeder
ExerciseSeeder
OpeningHoursSeeder
PilotSeeder
PilotTemplateSeeder
PlateInventorySeeder
ProgressDemoSeeder
RoleSeeder
TrainingHistorySeeder
```

---

## Configurazioni non-standard Laravel 11

Standard Laravel 11: `app`, `auth`, `cache`, `database`, `filesystems`, `logging`, `mail`, `queue`,
`services`, `session`, `broadcasting`, `cors`, `hashing`, `view`.

**Configurazioni custom/pacchetti (12):**

| File | Origine |
|---|---|
| `adminlte.php` | jeroennoten/laravel-adminlte |
| `backup.php` | spatie/laravel-backup |
| `features.php` | applicazione (feature flags iron-gym) |
| `flare.php` | spatie/flare-client-php |
| `livewire.php` | livewire/livewire |
| `pennant.php` | laravel/pennant |
| `permission.php` | spatie/laravel-permission |
| `pilot.php` | applicazione (credenziali pilot seeder) |
| `pr.php` | applicazione (soglie PersonalRecord) |
| `readiness.php` | applicazione (soglie ReadinessEvaluator) |
| `telescope.php` | laravel/telescope (dev) |
| `volume_landmarks.php` | applicazione (soglie volume) |

---

## Feature flag (Pennant)

Nomi di feature flag effettivamente usati nel codice (da `Feature::` occorrenze in `app/` e `routes/`):

```
financial_reports
group_classes
periodization_engine
push_notifications
view-group-classes
```

**Nota:** le stringhe `atleta`, `trainer`, `gestore`, `features.beta_trainers` compaiono in contesti
`Feature::` ma sono probabilmente nomi di gate/ruolo, non flag Pennant.
Da verificare in fase 3.

---

## Test (`tests/`)

**Totale file:** 45 (inclusi `Pest.php` e `TestCase.php`)
**Test eseguibili:** 43 file (41 Feature + 4 Unit, esclusi Pest.php e TestCase.php)

### Unit (4)
```
CommunicationTemplateTest
E1rmCalculationTest
ExampleTest
KpiServiceTest
```

### Feature (41)
```
AthleteHistoryTest
Auth/AuthenticationTest, EmailVerificationTest, PasswordConfirmationTest,
  PasswordResetTest, PasswordUpdateTest, RegistrationTest
BodyMeasurementTest
BookingTest
DeloadEvaluatorTest
ExampleTest
ExerciseDetailPageTest
ExerciseSeedTest
ExerciseSubstitutionFinderTest
ExportTest
FlareTest
MemberFormTest
MemberTest
MesocycleInstantiationTest
NotificationTest
PersonalRecordDetectorTest
PlateLoadoutCalculatorTest
ProfileTest
QueryCountTest
ReadinessEvaluatorTest
ReceptionistCheckinTest
SessionRecapBuilderTest
SmokeTest
SyncBatchTest
ThemeToggleTest
TrainingFlowTest
WeeklyProgressionServiceTest
WeeklyVolumeCalculatorTest
WeeklyVolumeComponentTest
WorkoutBuilderTest
WorkoutSessionNavigationTest
WorkoutSessionSubstitutionTest
WorkoutSessionTest
WorkoutSessionUxTest
```

---

## Suite Pest

```json
{"tool":"pest","result":"passed","tests":226,"passed":220,"assertions":552,"duration_ms":67433,"skipped":6}
```

**220 pass + 6 skip + 0 fail — suite verde.**

---

*Generato da DOC01 fase 2 — 2026-08-23*
