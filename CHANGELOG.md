# Changelog — iron-gym

---

## R09 Step 2 — Command, prerequisiti e overlap corsi collettivi (2026-08-24)

**Command `classes:generate-occurrences`:**
- Signature: `classes:generate-occurrences {--horizon=}` (default: `config/classes.php generation_horizon_days`, 28)
- Legge ClassSchedule attivi con groupClass, itera CarbonPeriod max(today,valid_from)..min(until,valid_until), filtra per weekday (0=lun, dayOfWeekIso-1)
- `ClassOccurrence::firstOrCreate([class_schedule_id, date], [...])` — idempotente; `wasRecentlyCreated` per contatore
- `end_time` calcolato da `start_time + groupClass->duration_minutes`
- Schedulato in `routes/console.php` daily 03:00

**`ClassBookingService::enroll()` — prerequisiti:**
- Abbonamento attivo: `$member->activeSubscription()->exists()` → `BookingException('Nessun abbonamento attivo.')`
- Certificato medico: `$member->has_medical_cert_valid` → `BookingException('Certificato medico scaduto o assente.')`
- Overlap atleta: `ClassBooking::whereHas('occurrence', fn → whereDate('date', ...) + time overlap)` → `BookingException('Hai già un corso confermato in questo orario.')`

**`PtBookingService::book()` — overlap trainer:**
- `ClassOccurrence::whereDate('date', ...)->where('status','planned')->where time overlap` → `BookingException('Il trainer ha un corso collettivo nello slot ...')`

**Nota tecnica:** usato `whereDate()` (anziché `where('date', ...)`) su colonna cast 'date' per compatibilità SQLite (che serializza come 'Y-m-d H:i:s') e MySQL (tipo date).

**Model:** `ClassSchedule` — `@property Carbon|null $valid_from/valid_until` (PHPStan L6); `GroupClass::scopeActive()` — return type `Builder<GroupClass>`

**Test (29 nuovi/aggiornati):**
- `BookingTest` (22 casi): enroll senza abbonamento, con abbonamento scaduto, senza cert, con cert scaduto, successo, overlap atleta (overlap e non-overlap), PT overlap trainer (overlap e non-overlap)
- `GenerateClassOccurrencesTest` (7 casi): genera occorrenze, idempotenza, valid_from, valid_until, is_active=false, orizzonte custom, end_time da duration_minutes

Suite: 241 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R09 Step 1 — Schema corsi collettivi (2026-08-24)

Riscrittura semantica da modello monolitico a tre livelli: GroupClass (definizione) → ClassSchedule (palinsesto) → ClassOccurrence (istanza datata).

**Migration (5 file):**
- `create_class_trainer_table`: pivot abilitazione trainer-corso (PK composta, cascade/restrict)
- `create_class_schedules_table`: palinsesto ricorrente; weekday 0=lun..6=dom (stessa convenzione TrainerAvailability)
- `create_class_occurrences_table`: istanza datata; unique (class_schedule_id, date) per idempotenza command; NULL non coperto (corretto)
- `transform_group_classes_table`: aggiunge slug/default_capacity/room/color/is_active; data migration con deduplica per name; crea class_trainer e class_occurrences da righe esistenti; rimuove trainer_id/scheduled_at/max_participants/status/cancellation_reason; down() best-effort
- `transform_class_bookings_table`: aggiunge class_occurrence_id/attended_at/booked_by; popola via old_class_id helper; enum status → confirmed/waitlisted/cancelled_by_athlete/cancelled_by_gym/no_show; rimuove class_id; unique → (class_occurrence_id, member_id)

**Model nuovi:** `ClassSchedule`, `ClassOccurrence` (con accessor confirmed_count/available_spots/is_full)

**Model aggiornati:** `GroupClass` (relazioni trainers BelongsToMany, schedules/occurrences HasMany, scope active()), `ClassBooking` (relazione occurrence(), promuove su class_occurrence_id)

**Service/Job/Notification aggiornati:** `ClassBookingService` (opera su ClassOccurrence; cancel → cancelled_by_athlete), `NotifyWaitlistPromotion` (usa booking->occurrence), `WaitlistPromotionNotification` (accetta ClassOccurrence)

**Livewire:** `GroupClassManager` (lista/CRUD occorrenze, firstOrCreate GroupClass per slug), `TrainerCalendar` (occorrenze nel calendario), `Athlete\Booking` (enrollClass/cancelClass su occurrenceId)

**Factory:** `ClassScheduleFactory`, `ClassOccurrenceFactory` (nuovi); `GroupClassFactory`, `ClassBookingFactory` (aggiornati al nuovo schema)

**Seeder:** `GroupClassSeeder` (idempotente, 4 corsi con palinsesto + occorrenze 2 settimane, solo dev)

**Config:** `config/classes.php` (booking_opens_days, booking_closes_minutes, free_cancel_hours, generation_horizon_days)

**Test:** `BookingTest` adattato (ClassOccurrence, nuovi test relazioni/vincoli/null-schedule); `ReceptionistCheckinTest` adattato (ClassOccurrence factory, status cancelled_by_athlete)

---

## DOC01 — Audit documentazione (2026-08-23)

Audit completo dei 40 file `.md` in 6 fasi. Findings risolti: 2 CRITICO, 4 MEDIO, 7 BASSO. 6 file archiviati (dated reports).

Modifiche principali: `component-map.md` (route fix, namespace, seeder), `glossary.md` (`sessions`→`training_sessions`), `docs/test/01-04` riscritti (URL corretti, sezioni mancanti aggiunte, 403 attesi documentati), `docs/review/test-per-ruolo.md` (177→226 test).

Suite: 220/226 (6 skip pre-esistenti). PHPStan 0 errori. Pint OK.

---

## HK01 — Housekeeping (2026-08-22)

Audit codice morto, dipendenze orfane, documentazione. Report: `docs/audit/hk01-report.md`.

- PHP: rimossi `sessionStatusClass/Label()` da `Athlete\Dashboard`, eliminato `config/barbell.php`
- View orfane: `Athlete\Progress` + view, `dashboard.blade.php` (stub Breeze)
- Composer: `ext-gd/mbstring` aggiunti a `require`, `laravel/tinker` spostato in `require-dev`, guard `class_exists` su `TelescopeServiceProvider`
- Docs: fix `e1RM` attributito a `E1rmCalculator` (non `WeeklyVolumeCalculator`), URL history corretti

Suite: 220/226 (6 skip pre-esistenti). PHPStan 0 errori. Pint OK.

---

## Audit receptionist (2026-08-19)

Fix permessi e test E2E per il ruolo receptionist.

**Fix sicurezza (P0-P1):** `CommunicationCampaign` e `AvailabilityManager` spostate in gruppo `role:gestore|trainer`; `GroupClassManager.save/delete`, `TrainerCalendar.cancelBooking`, `BookingList.confirm/cancel` protetti con `abort_unless`; check-in blocca su cert. medico scaduta/assente; dashboard atleta mostra banner danger/warning per scadenza cert.

**Fix UI/Perf:** link Modifica/Profilo nascosti per receptionist in `MemberList`; badge abbonamento tradotti; wire:loading su modale check-in; indice composito `last_name/first_name` su `members`.

24 test in `ReceptionistCheckinTest`. Suite: 212/218 → 220/226. PHPStan 0. Pint OK.

---

## Audit funzionale PWA atleta (2026-08-18)

Fix puntuali post-audit interfaccia atleta.

Chiusi: sessioni `skipped` visibili in storico, set time-based filtrati per `completed_at`, null-guard su `cancelPtBooking/cancelClassBooking`, tab Corsi gated su `group_classes`, N+1 in `WeeklyVolume::mount()`, push subscribe idempotente, `translate-y-*` in Tailwind safelist. Componente `History.php` orfano rimosso.

Suite: 189/195 (6 skip). PHPStan 0. Pint OK.

---

## UX07 — Scala UI maggiorata (2026-07-18)

Alzata scala interfaccia atleta per uso in palestra con una mano sola.

Token aggiornati: `--ig-text-md` 22px, `--ig-text-lg` 26px, `--ig-text-xl` 34px, `--ig-text-display` 48px, `--ig-touch-target` 56px. Nuovi: `--ig-touch-target-sm` 40px, `--ig-touch-target-xl` 64px, `--ig-bottom-nav-h` 72px, `--ig-nav-icon` 26px. Tutti i valori hardcoded tokenizzati. Bottone FATTO: nuova classe `ws-action-done-btn` (64px). Overflow protection su `ig-stat__value`.

Suite: 189/195. PHPStan 0. Pint OK.

---

## UX06 — Toggle tema dark/light e viewport mobile/desktop (2026-07-17)

- Toggle tema: `aria-pressed` dinamico, label testuale "Chiaro"/"Scuro", `aria-label` aggiornato
- Toggle viewport (solo `local`): script inline nel `<head>` forza `width=1280`, badge "Vista desktop" Alpine, bottone in `/athlete/profile`, limiti noti documentati

Suite: 189/195. PHPStan 0. Pint OK.

---

## UX05-B — Ergonomia PWA atleta (2026-07-06)

Fix contrasto, touch target, safe-area, input mobile, accessibilità.

Safe-area topbar corretta; `--ig-accent` light portato a 4.78:1, `--ig-text-3` dark a 4.56:1; 14 elementi portati a `var(--ig-touch-target)` 48px; font-size input bilanciere 16px (evita zoom iOS); `aria-label` su tutti i campi zona azione. Report: `docs/reviews/ui-atleta-ergonomia-2026-07-06.md`.

Suite: 190/190. PHPStan 0. Pint OK.

---

## UX05 — Coerenza visiva e de-inlining CSS (2026-07-05)

`--ig-intensity-0..5` come unica fonte per body-map e legenda. Classi `.ig-tab-group/tab/tab--active` estratte da 5 view. `.ig-form-input/label/is-invalid` uniformi. Active state radio via CSS puro. `profile.blade.php` migrata a `ig-*`.

Suite: 190/190. PHPStan 0. Pint OK.

---

## UX04 — Stati, feedback e micro-interazioni (2026-07-05)

`x-athlete.toast` (coda Alpine, auto-dismiss), `x-athlete.skeleton` (shimmer + `prefers-reduced-motion`), `x-athlete.empty-state` (7 punti). `wire:loading.attr="disabled"` su tutti i bottoni. `livewire:request-failed` → toast. `--ig-transition: 180ms` globale.

Suite: 190/190. PHPStan 0. Pint OK.

---

## UX03 — Navigazione 4-tab e home action-oriented (2026-07-05)

Bottom nav consolidata a 4 tab (Home/Allenamento/Progressi/Profilo). Dashboard riscritta: hero card sessione, striscia settimana, recap ultimo allenamento, empty state contestuali. `Alpine.store('messages')` nel layout. Safe-area iOS e `manifest.json` corretti.

Suite: 190/190. PHPStan 0. Pint OK.

---

## UX02 — Redesign schermata sessione (2026-07-05)

Layout un-esercizio-alla-volta con prev/next e jump-drawer. `setData` pre-compilato con `planned_*`. Zona azione fissa: stepper `x-athlete.input-number` + bottone FATTO 56px + rest timer. Nuovo partial `session-exercise.blade.php`. 7 test navigazione (`WorkoutSessionNavigationTest`).

Suite: 190/190. PHPStan 0. Pint OK.

---

## UX01 — Design foundation PWA atleta (2026-07-05)

Design system: ~40 CSS custom properties su `:root`, dark/light theme con anti-FOUC, migrazione classi legacy ai token, safe-area iOS. Componenti `x-athlete.*`: `button`, `card`, `stat`, `badge`, `input-number`. Documentazione: `docs/architecture/ui-atleta.md`.

Suite: 183/183. PHPStan 0. Pint OK.

---

## Tag v0.9.0 (2026-07-05)

UX01–UX05 completate. Git flow inizializzato (`develop` da `master`). Suite: 190/190. PHPStan 0. Pint OK.

---

## Release 08 — Recap di fine sessione (2026-07-03)

`SessionRecapBuilder`: durata, tonnellaggio (warmup esclusi), ratio set, PR nel range, top 3 muscoli. `SessionRecap` Livewire su `/athlete/session/{id}/recap`. Card HTML 375px brand + CSS standalone. Export PNG via `html-to-image` + Web Share API con fallback download. Bottone "Riepilogo" nello storico. 6 test `SessionRecapBuilderTest`.

Suite: 183/183. PHPStan 0. Pint OK.

---

## Release 07 — Readiness check pre-sessione (2026-07-03)

4 campi 0-3 (sleep/stress/soreness/joint), score 0-12, proposta modulazione (none/-5%/-10%+rimozione set). `SessionReadinessCheck` model + migration (UNIQUE su `training_session_id`). `ReadinessEvaluator` + `ReadinessProposal` value object. Soglie in `config/readiness.php`. Backoffice: sezione readiness in `AthleteSessionHistory`. 14 test `ReadinessEvaluatorTest`.

Suite: 177/177. PHPStan 0. Pint OK.

---

## Release 06 — Sostituzione esercizio guidata (2026-07-03)

`ExerciseSubstitutionFinder::find()`: max 5 candidati per overlap muscolare (joint_action o compound_pattern + measurement_type). `substituted_from_exercise_id` su `session_exercises`. UI: bottom sheet con 5 card, blocco su set working completati. Backoffice: badge "sost. da [originale]". 14 test in `ExerciseSubstitutionFinderTest` + `WorkoutSessionSubstitutionTest`.

Suite: 163/163. PHPStan 0. Pint OK.

---

## Release 05 — PR detection in tempo reale (2026-07-03)

`PersonalRecordDetector::check()`: e1RM Epley, soglie in `config/pr.php`, integrato in `quickLog/completeSet` (online) e `SyncBatchController` (offline). Toast PR in sessione. Componente `PersonalRecords` su `/athlete/records`. 6 test `PersonalRecordDetectorTest`.

Suite: 143/149. PHPStan 0. Pint OK.

---

## Release 04 — Volume visuale: body map SVG (2026-07-03)

Body map SVG fronte+retro, 25 muscoli con `data-muscle`, classi `intensity-0..5` da `WeeklyVolumeCalculator`. `WeeklyVolume` Livewire su `/athlete/volume`: selettore settimana, barre con marker MEV/MAV/MRV. Voce "Volume" in sidebar e bottom nav. 8 test `WeeklyVolumeComponentTest`. Docs: `docs/architecture/body-map-svg.md`.

Suite: 129/143. PHPStan 0. Pint OK.

---

## Release 03 — Offline-first sync (2026-07-03)

Alpine store `syncQueue` con IDB e retry backoff esponenziale. Intercettori offline in `exercise-card.blade.php`. Endpoint `POST /athlete/session/sync` con idempotenza via `sync_operations.client_uuid UNIQUE` e last-write-wins. Service worker v2 (network-first su `/athlete/session/*`). 4 test `SyncBatchTest`.

Suite: 125/135. PHPStan 0. Pint OK.

---

## Release 02 — Plate calculator (2026-07-03)

`PlateInventory` model + `PlateLoadoutCalculator` (greedy decrescente, `delta_kg` se non esatto). `PlateInventoryManager` backoffice. Modale atleta con stack grafico dischi colorati. 4 test `PlateLoadoutCalculatorTest`.

Suite: 125/135. PHPStan 0. Pint OK.

---

## Release 01 — UX sessione (2026-07-03)

Quick-log one-tap (copia `planned_*`→`actual_*` per tipo), previous performance inline (singola query, `$previousPerformance[exercise_id][set_index]`), rest timer globale (`Alpine.store('restTimer')`, vibrazione+Notification API), warm-up generator (50/70/85%, idempotente, soglia 40 kg). 15 test `WorkoutSessionUxTest`.

Suite: 121/129. PHPStan 0. Pint OK.

---

## 2026-06-27 — Revisione sicurezza e qualità

**Security (CRITICAL/HIGH):** IDOR fix su `SessionFeedbackForm`, `TemplateBuilder` (8 metodi), `MessageThread`; middleware backoffice riorganizzato; `abort_unless` defense-in-depth su `MesocycleDetail`; FK `athlete_id/trainer_id` su `mesocycles`.

**Performance:** lookup ExerciseForm cachati forever; `DeloadEvaluator::evaluate()` fuori da `render()`; `checkRirDrift()` filtro `ROW_NUMBER` in MySQL; indice su `exercise_sets.completed_at`.

**Test:** fix IDOR `DeloadEvaluatorTest` (2 casi mancanti), 6 factory aggiunte.

Suite: 96/102. PHPStan 0. Pint OK.

---

## 2026-06-22 — Storico atleta e navigazione backoffice

`AthleteProfile` (tab Alpine: storico, analytics, misurazioni, volume, messaggi). `AthleteSessionHistory` backoffice con dettaglio inline e sicurezza ownership. Fix `@extends`→wrapper `<div>` su view embedded. Link "Profilo allenamento" in `MemberList` e `MesocycleList`. Fix `TrainingReport` (nomi colonne feedback) e `MesocycleDetail::forceDeload()`. 4 test `AthleteHistoryTest`.

Suite: 90/96. PHPStan 0. Pint OK.

---

## 2026-06-25 — Allineamento catalogo esercizi

`execution_description` integrata in `exercises_seed.sql` (fonte unica). `iron_gym_esercizi_descrizioni.xlsx` rimosso. `build_exercises_sqlite.py` riscritto senza dipendenze extra (legge solo SQL). `database.sqlite` rigenerato con tutti 83 esercizi.

---

## Step 10 — Pilota in palestra reale

**Feature flags (`laravel/pennant`):** 4 flag (`periodization_engine`, `push_notifications`, `group_classes`, `financial_reports`). `FeatureFlagManager` backoffice (solo gestore).

**Error tracking (`spatie/laravel-flare`):** context utente, IP anonimizzati, eccezioni non segnalabili configurate.

**Feedback in-app:** migration `feedback_submissions`, widget flottante `InAppFeedback` su tutti i layout, `FeedbackList` backoffice con note inline.

**Onboarding pilota:** `PilotSeeder` + `PilotInitCommand` idempotenti, `docs/devops/go-live-checklist.md`.

Suite: test `FlareTest` + `SmokeTest` (6 smoke, skip su SQLite in-memory). PHPStan 0. Pint OK.

---

## Step 9 — Hardening e DevOps

`spatie/laravel-backup` (giornaliero, retention 7G/4W/3M, alert email). Health check `/up`. `GeneratePwaIcons` artisan. Laravel Telescope (dev). CI GitHub Actions: cache config/route/view, `migrate --force`, `queue:restart`. Rate limiting auth, CSP base.

---

## Step 8 — Reportistica gestore

`KpiService`: revenue, occupancy trainer, churn rate (cache Redis tag `kpi` TTL 1h). `ManagerDashboard`: grafici Chart.js, tesserati a rischio churn. `FinancialReport`: export CSV + PDF. `TrainingReport`: gestore+trainer. `KpiSummaryCommand` schedulato.

---

## Step 7 — CRM e notifiche

Messaggistica `Message` (thread atleta↔trainer, polling 3s). `CommunicationCampaign` con segmenti e job coda. `NotificationBell` backoffice. Push VAPID (`PushSubscription`). `InactiveMembersCommand` schedulato.

---

## Step 6 — Prenotazioni e calendario

`TrainerAvailability`, `PtBooking`, `GroupClass`, `ClassBooking`. `TrainerCalendar` (FullCalendar.js), `AvailabilityManager`, `BookingList`, `GroupClassManager`. Observer `TrainerAvailabilityObserver` e `PtBookingObserver`.

---

## Step 5 — Tracking corporeo

`BodyMeasurement`, `ProgressPhoto`. Form misurazioni backoffice+atleta. `Progress` atleta con grafici Chart.js. `AthleteAnalytics` backoffice.

---

## Step 4 — Periodizzazione e autoregolazione

`WeeklyVolumeCalculator` (hard sets pesati per `contribution_pct`). `WeeklyProgressionService` (MEV→MRV, deload -50%/-10%). `DeloadEvaluator` (4 trigger). `VolumeLandmarkManager` backoffice. `MesocycleAssign`. Value objects `ProgressionResult`, `DeloadSignal`.

---

## Step 3 — App atleta v1 e workout logging

Layout `athlete.blade.php` dark, PWA (manifest + service worker + icone). `WorkoutSession` (logging live), `SessionFeedbackForm`, `History`. Instanziamento mesociclo da template. `TrainingSessionObserver`.

---

## Step 2 — Libreria esercizi e workout builder

`ExerciseList/Detail/Form` (CRUD con pivot muscoli/equipment, cache tag `exercises`). `TemplateList/Form/Builder` (drag-and-drop sessioni ed esercizi). `ExerciseObserver`.

---

## Step 1 — Skeleton + gestionale base

Laravel 11 + Docker Compose (app/db/redis/node) + GitHub Actions CI. Schema training-core migrato. `ExerciseSeeder` (83 esercizi via SQL), `RoleSeeder` (4 ruoli Spatie), `DemoSeeder`. Auth Breeze Livewire. Modelli `Member`, `SubscriptionPlan`, `Subscription`, `AccessLog`. Backoffice: `MemberList/Form`, `SubscriptionList/Form`, `AccessLogList`.

---

## Step 0 — Discovery e modello di dominio

Glossario bodybuilding, 4 personas, tassonomia esercizi 7 assi. Tabella `movement_patterns` con `category` (compound_pattern/joint_action), 27 pattern. CHECK XOR `compound_pattern_id/joint_action_id` su `exercises`. Schema SQL completo. Catalogo seed 83 esercizi, 26 muscoli, 14 equipment. Modello set `planned_*`/`actual_*`. Regole progressione MEV→MRV. Documenti: `step-0-discovery.md`, `exercises-catalog.md`, `glossary.md`.
