# iron-gym

Gestionale palestra bodybuilding/fitness. Copre: anagrafica tesserati, abbonamenti, accessi, libreria esercizi, schede allenamento (template e mesocicli), logging sessioni atleta, periodizzazione con volume landmarks, tracking corporeo, prenotazioni PT/corsi, messaggistica trainer-atleta, notifiche automatiche, reportistica gestore, feature flags.

## Stack tecnico

- **Backend:** PHP 8.3, Laravel 11.x
- **Frontend backoffice:** Livewire 3 + Alpine.js, tema AdminLTE 3.x + brand layer Iron Gym
- **App atleta:** stesse tecnologie, layout dedicato su prefisso /athlete
- **Database:** MySQL 8.0 (database: `iron_gym`)
- **Cache / code:** Redis 7
- **Auth:** Laravel Breeze (stack Livewire); API: Sanctum personal access token
- **Permissions:** spatie/laravel-permission
- **Static analysis:** Larastan livello 6
- **Code style:** Laravel Pint
- **Test:** Pest
- **Container:** Docker Compose (app, db, redis, node)
- **CI/CD:** GitHub Actions

## Convenzioni

- Lingua: italiano nel codice (commenti, messaggi), termini tecnici in inglese.
- Modelli singolari PascalCase, tabelle plurali snake_case.
- Form Request per validazione, mai inline nei controller.
- Livewire per CRUD e form complessi, Blade puro per pagine statiche.
- Migration sempre con down() implementato.
- Naming Livewire: app/Livewire/Backoffice/<Area>/<Nome> e app/Livewire/Athlete/<Nome>.

## Dominio — entità principali

**Gestionale:**
- Member: tesserato, anagrafica, certificato medico con scadenza
- SubscriptionPlan: tipologia abbonamento (durata, prezzo, ingressi)
- Subscription: abbonamento attivo di un Member
- AccessLog: registro accessi in struttura
- DumbbellInventory: inventario manubri (peso_kg, quantity_pairs, is_active); usato da PlateInventoryManager (`/backoffice/admin/plate-inventory`)

**Training core:**
- MovementPattern: lookup pattern motori (compound_pattern / joint_action)
- Muscle, Equipment: lookup tassonomia esercizi
- Exercise: catalogo esercizi, relazioni N-M su Muscle (pivot ExerciseMuscle con role e contribution_pct) e Equipment
- WorkoutTemplate: template scheda riutilizzabile (gym-wide)
- TemplateSession, TemplateSessionExercise: struttura template
- Mesocycle: istanza concreta assegnata ad atleta, generata da WorkoutTemplate
- MicrocycleWeek: settimana mesociclo (is_deload, start_date, end_date)
- TrainingSession: sessione giornaliera (planned/in_progress/completed/skipped)
- SessionExerciseGroup: raggruppamento superset/giant_set
- SessionExercise: esercizio in sessione con technique_type
- ExerciseSet: set atomico con campi planned_* e actual_* separati
- SessionFeedback, SessionExerciseFeedback: feedback post-sessione scala 0-3
- AthleteVolumeLandmark: MEV/MAV/MRV personalizzati per atleta-muscolo

**Prenotazioni:**
- TrainerAvailability: disponibilità settimanale trainer
- PtBooking: prenotazione sessione PT
- GroupClass: **definizione** del corso (slug, name, description, duration_minutes, default_capacity, room, color, is_active). Non contiene data/ora.
- ClassSchedule: palinsesto ricorrente per un corso (group_class_id, weekday 0=lun..6=dom, start_time, trainer_id nullable, valid_from, valid_until, is_active). Stesso weekday convention di TrainerAvailability.
- ClassOccurrence: **istanza datata** del corso (group_class_id, class_schedule_id nullable, date, start_time, end_time, trainer_id, capacity, status planned/cancelled/completed). Unique (class_schedule_id, date) per idempotenza command. Porta gli accessor confirmed_count, available_spots, is_full.
- class_trainer: pivot abilitazione trainer su corso (group_class_id, trainer_id).
- ClassBooking: iscrizione a ClassOccurrence con waitlist. status enum: confirmed, waitlisted, cancelled_by_athlete, cancelled_by_gym, no_show. Ha attended_at e booked_by. Unique (class_occurrence_id, member_id).

**Comunicazione:**
- Message: messaggistica interna trainer-atleta
- CommunicationTemplate, CommunicationLog: campagne e log invii
- PushSubscription: endpoint Web Push per notifiche PWA

**Tracking e analytics:**
- BodyMeasurement: misurazioni corporee periodiche
- ProgressPhoto: foto progressi per pose
- PlateInventory: inventario dischi per lato (weight_kg, quantity_pairs, color, is_active)
- PersonalRecord: PR per atleta+esercizio; record_type ENUM(e1rm, max_weight, max_reps_at_weight); questa release implementa solo e1rm
- SessionReadinessCheck: check pre-sessione (training_session_id UNIQUE); campi sleep_quality, stress_level, soreness_level, joint_status (TINYINT 0-3, 0=pessimo 3=ottimo); accessor score (0-12)

**Sistema:**
- FeedbackSubmission: feedback in-app utenti
- Feature (Pennant): feature flags per roll-out graduale
- Setting: impostazioni globali key/value (PK stringa, value JSON). Sorgente di verità per i feature flag validi per l'intera palestra — `group_classes` — perché `Feature::activateForEveryone()` aggiorna solo le righe già esistenti in `features`. Helper `Setting::bool($key, $default)` e `Setting::write($key, $value)`
- SyncOperation: tabella idempotenza sync offline (client_uuid UNIQUE, operation, processed_at)

## Servizi disponibili

- PlateLoadoutCalculator: calcola dischi per lato del bilanciere; greedy decrescente su PlateInventory attivi; delta_kg=0 se combinazione esatta, altrimenti combinazione per difetto
- MesocycleInstantiationService: crea gerarchia completa da template
- WeeklyVolumeCalculator: calcola hard set settimanali pesati per contribution_pct
- WeeklyProgressionService: applica progressione MEV→MRV con lettura feedback
- DeloadEvaluator: valuta i quattro trigger di deload
- KpiService: metriche aggregate per la dashboard gestore; cache Redis tag `kpi` TTL 1h
- PtBookingService: prenotazioni PT con verifica disponibilità
- ClassBookingService: enroll(ClassOccurrence, Member) → ClassBooking; cancel(ClassBooking) imposta cancelled_by_athlete e promuove waitlist; lockForUpdate su ClassOccurrence
- E1rmCalculator: formula Epley per stima 1RM
- PersonalRecordDetector: check(ExerciseSet, athleteId) → PersonalRecord|null; sincrono, pronto per migrazione a evento+listener; soglie in config/pr.php (max_reps_epley, min_sessions_before_pr)
- ExerciseSubstitutionFinder: find(Exercise) → Collection max 5 candidati; matching per stesso joint_action_id o compound_pattern_id + stesso measurement_type + non soft-deleted; overlap = somma min(pct_orig, pct_cand) su muscoli comuni; tie-break: stesso mechanic poi skill_level
- ReadinessEvaluator: evaluate(SessionReadinessCheck) → ReadinessProposal; score 0-12 (somma 4 campi 0-3); soglie da config/readiness.php (high=9 none, 5-8 reduce_5pct, <5 reduce_10pct); applyReduction(float, int): float arrotondato a 2.5 kg
- SessionRecapBuilder: build(TrainingSession, athleteId) → array con duration_minutes, tonnage_kg (set working completati, warmup esclusi), sets_completed/sets_prescribed, prs (PersonalRecord nel range started_at..completed_at), top_muscles (top 3 per SUM contribution_pct su set completati non-warmup). Cinque query, nessun N+1.

## Observers

Registrati in `AppServiceProvider`. Tutti in `app/Observers/`.

- ExerciseObserver (Exercise): flush cache tag `exercises` su create/update/delete
- PtBookingObserver (PtBooking): invalida cache slot trainer e tag KPI su saved/deleted. **Nota:** documentazione precedente ("notifica atleta+trainer") era errata — nessuna notifica viene inviata.
- SubscriptionObserver (Subscription): invalida cache KPI tag `kpi` su create/update
- TrainerAvailabilityObserver (TrainerAvailability): ricalcola slot disponibili su saved/deleted
- TrainingSessionObserver (TrainingSession): aggiorna status, started_at, completed_at su update

## Decisioni architetturali fisse

- Single-tenant: niente gym_id.
- movement_patterns è tabella lookup con category (compound_pattern / joint_action).
- CHECK XOR su exercises: esattamente una tra compound_pattern_id e joint_action_id valorizzata.
- Mesociclo snapshottato all'istanziamento: modifiche al template non si propagano.
- Set unilaterali: un ExerciseSet per coppia di lati, niente granularità DX/SX nell'MVP.
- Feedback post-sessione scala 0-3.
- Ruoli spatie: atleta, trainer, gestore, receptionist.

## Mappa componenti e route

La mappa completa di tutti i componenti Livewire (~50), le route backoffice e atleta,
gli observers, i seeder e gli artisan commands è in:

**`docs/architecture/component-map.md`**

Leggila prima di aggiungere nuovi componenti o route per evitare conflitti e
seguire i pattern esistenti.

**Componenti backoffice non nella component-map (trovati in codice):**
- `OpeningHoursManager` (`/backoffice/settings/opening-hours`): CRUD orari apertura settimanali + eccezioni per data specifica. Accessibile a tutti i ruoli backoffice; modificabile da gestore e receptionist.
- `GlobalSearch` (`/backoffice/search`): ricerca live atleti/trainer/template, min 2 caratteri.

**Nota architetturale:** le view Livewire usano wrapper `<div>` (non `@extends`).
Il layout è gestito con `->layout('layouts.backoffice')` nel `render()`. Questo
pattern è necessario per embeddare componenti via `@livewire` (es. in `AthleteProfile`).
Exercise model usa `getRouteKeyName() = 'slug'` (route binding su slug).

## WorkoutSession — interazioni chiave (Release 01 + 06)

**Componente:** `app/Livewire/Athlete/WorkoutSession.php`  
**View:** `resources/views/livewire/athlete/workout-session.blade.php` + partial `partials/exercise-card.blade.php`

| Metodo | Descrizione |
|---|---|
| `quickLog($setId)` | Copia planned→actual rispettando `measurement_type`; non resetta `completed_at` se già valorizzato |
| `completeSet($setId)` | Salva valori digitati manualmente; non resetta `completed_at` se già valorizzato |
| `generateWarmup($seId)` | Crea set is_warmup=1: 50/70/85% arrotondati a 2.5kg; sotto 40kg solo 50%; idempotente |
| `deleteWarmupSet($setId)` | Rimuove singolo set warmup; rifiuta working set con 404 |
| `loadPreviousPerformance()` | Singola query aggregata, riempie `$previousPerformance[exercise_id][set_index]` |
| `openSubstitutionModal($seId)` | Blocca se set working completati; chiama ExerciseSubstitutionFinder; popola `$substitutionCandidates` come array scalare |
| `confirmSubstitution($slug)` | Aggiorna `exercise_id`, setta `substituted_from_exercise_id`; mantiene set e prescrizione invariati; blocco doppio su set completati |
| `closeSubstitutionModal()` | Azzera `$substitutingSeId` e `$substitutionCandidates` |
| `submitReadiness($sleep, $stress, $soreness, $joint, $note)` | Salva SessionReadinessCheck; calcola ReadinessProposal; traccia in `trainer_notes`; se outcome != none mostra `$modulationProposal` |
| `skipReadiness()` | Salta check, chiama `startSession()` direttamente |
| `acceptModulation()` | Aggiorna `planned_weight_kg` set non completati + elimina set extra (fascia low); poi `startSession()` |
| `rejectModulation()` | Avvia sessione senza modificare i carichi |
| `startSession()` (private) | Transiziona `planned → in_progress` con `started_at` |
| `completeSession()` | Transiziona `in_progress → completed` con `completed_at`; mostra `SessionFeedbackForm` embedded |

**Flusso post-completamento (Release 08):**
`completeSession()` → `$showFeedback=true` → `SessionFeedbackForm` (embedded) → `save()` o `skip()` → redirect `/athlete/session/{id}/recap` → `SessionRecap` mostra card + export PNG.

**Alpine store `restTimer`** (definito in workout-session.blade.php): `start(sec)`, `skip()`, `fmt(s)`. Avvia vibrazione + Notification API allo scadere. Barra fissa bottom. Per cluster usa `intra_cluster_rest_sec`.

**`$previousPerformance`**: proprietà pubblica array, serializzata Livewire, usata dal partial per mostrare "prec: Xkg × Y @ RIR Z" sotto ogni working set.

## Stato sviluppo

Step 1-10 implementati. Release 01-08 completate. UX01-UX07 completate. Tag v0.9.0 (2026-07-05).
**v1.2.3** (2026-08-24): fix Pint `binary_operator_spaces` in 4 file di test (AthleteProfileClassBookingsTest, AthleteProfileMessagesTest, AthleteProfilePersonalRecordsTest, AthleteProfileSessionsTest). Pipeline CI ripristinata.
Audit sicurezza v2, audit receptionist, audit funzionale PWA atleta, HK01, DOC01 completati.
**R09 Step 1** completato: schema GroupClass→ClassSchedule→ClassOccurrence, consumer adattati, test aggiornati.
**R09 Step 2** completato: command `classes:generate-occurrences`, prerequisiti enroll (abbonamento+cert), overlap check atleta e trainer, 29 nuovi test.
**R09 Step 3** completato: ClassScheduleManager (CRUD palinsesto), attendance tracking (completeOccurrence/markAttended/markNoShow), 13 nuovi test.
**R09 Step 4** completato: ClassOccurrenceCancelledNotification + NotifyClassCancellation job, check-in receptionist, feature flag gate in Athlete\Booking, 7 nuovi test.
**R09 Step 5** completato: GroupClassCatalog CRUD definizioni corsi (solo gestore), sidebar submenu a 3 voci (Occorrenze/Palinsesto/Catalogo), dashboard atleta card prossimi corsi collettivi, 8 nuovi test.
**R09 Step 6** completato: finestra prenotazione (booking_opens_days/booking_closes_minutes in Athlete\Booking), finestra cancellazione gratuita (free_cancel_hours), removeParticipant → cancelled_by_gym, 5 nuovi test. R09 chiuso.

**R10** completato: centro notifiche atleta (`/athlete/notifications`), badge non lette in sidebar, endpoint `unread-count`, fix `route('athlete.booking')` → `athlete.bookings`, 7 nuovi test.

**R11** completato: `ClassReminderNotification` (database + webpush) + `SendClassReminders` job schedulato `dailyAt('08:00')`, icona `class_reminder` in centro notifiche, 6 nuovi test.

**R12** completato: `periodization_engine` attivato in PilotSeeder (`financial_reports` + `periodization_engine` on per default); test Livewire `MesocycleDetail` (applyProgression, forceDeload, role guards, 6 test); test Livewire `VolumeLandmarkManager` (save, resetToDefaults, auth trainer, 6 test).

**R13** completato: sezione "Abbonamento" nel profilo atleta (piano, scadenza, badge stato), fix cross-db `CONCAT→||` in `TrainingReport` e `GlobalSearch`, 14 nuovi test (`AthleteProfileSubscriptionTest` 4, `TrainingReportTest` 6, `GlobalSearchTest` 4).

**R14** completato: sessioni PT future nella dashboard atleta (`upcomingPtBookings`), fix `CONCAT→||` in `ManagerDashboard`, test `AthleteAnalytics` (auth isolation, 4 test), test `ManagerDashboard` (2 test), test `AthleteDashboardPtBooking` (4 test).

**R15** completato: test `BookingList` (7 test: confirm/cancel/restore con isolamento ruoli), test `CommunicationCampaign` (4 test: send job, validazione body, filtro active), `group_classes` attivato in `PilotSeeder`. R15 chiuso.

**R16** completato: tab "Sessioni PT" nel profilo atleta (prossime + storico ultimi 10), 5 nuovi test. R16 chiuso.

**R17** completato: tab "Misurazioni" nel profilo atleta (ultime 5 con peso/BF%/vita/petto + link a pagina completa), 5 nuovi test. R17 chiuso.

**R18** completato: tab "Record" nel profilo atleta (ultimi 5 e1RM con esercizio, valore, data + link a pagina completa), 5 nuovi test. R18 chiuso.

**R19** completato: tab "Sessioni" nel profilo atleta (ultime 5 completed/skipped con nome, data, durata, badge + link a storico completo), 5 nuovi test. R19 chiuso.

**R20** completato: tab "Messaggi" nel profilo atleta (ultimi 5 messaggi con contatto, anteprima, data, badge non letti + link a /athlete/messages), 5 nuovi test. R20 chiuso.

**R21** completato: tab "Corsi" nel profilo atleta (gated `group_classes`, prossimi corsi prenotati + storico, badge Confermato/Lista d'attesa), 5 nuovi test. Fix SQL ambiguous column con qualifica `class_bookings.status`. R21 chiuso.

**R22** completato: Pannello Scadenze backoffice (`/backoffice/members/expiry`), accessibile a gestore e receptionist. Due tabelle: certificati medici in scadenza (default 30gg) e abbonamenti in scadenza (default 7gg). Filtri live per ricerca e finestra temporale. Voce sidebar "Scadenze". 7 nuovi test. R22 chiuso.

**R23** completato: widget "Scadenze imminenti" nella Dashboard backoffice. Card condizionale con contatori `certExpiring30Count` e `subExpiring7Count`; link diretto al pannello scadenze. Link small-box esistenti aggiornati a `members.expiry`. 4 nuovi test. R23 chiuso.

**R24** completato: Check-in Rapido backoffice (`/backoffice/checkin`). Ricerca live tesserato, validazione cert+abbonamento+accessi, cronologia accessi odierni. Voce "Check-in" in sidebar. 7 nuovi test. R24 chiuso.

**R25** completato: Rinnovo abbonamento rapido — bottone "Rinnova" in `SubscriptionList` (gestore/receptionist); `SubscriptionForm::mount()` pre-popola `member_id` e `plan_id` da query string e calcola `expires_at` automaticamente. 5 nuovi test. R25 chiuso.

**R26** completato: tab "Accessi" nel profilo atleta (ultimi 5 ingressi con data, ora, piano, badge Entrata), 5 nuovi test. R26 chiuso.

**R27** completato: sospensione/riattivazione abbonamento in `SubscriptionList` (solo gestore), guard 403/422, filtro "Sospesi", bottoni `fa-pause`/`fa-play` con `wire:confirm`. 5 nuovi test. R27 chiuso.

**R28** completato: note interne sul tesserato — `MemberForm` già completo; aggiunta icona `fa-sticky-note` con tooltip in `MemberList` quando note presenti. 4 nuovi test. R28 chiuso.

**R29** completato: Export CSV abbonamenti (`/backoffice/subscriptions/export?filter=X`), solo gestore. CSV UTF-8 con BOM, separatore `;`, rispetta filtro corrente. Bottone in `SubscriptionList`. 4 nuovi test. R29 chiuso.

**R30** completato: Export CSV tesserati (`/backoffice/members/export?search=X&certFilter=Y`), solo gestore. Colonne: Cognome, Nome, Email, Telefono, Abbonamento, Scadenza abb., Cert. medico, Attivo. Bottone in `MemberList`. 4 nuovi test. R30 chiuso.

**R31** completato: tabella "Sessioni PT completate per trainer" in `ManagerDashboard`; query `pt_bookings JOIN users` filtrata per periodo e `status=completed`, raggruppata per trainer. 3 nuovi test. R31 chiuso.

**FIX01** (2026-08-26): flag globale `group_classes` spostato su tabella `settings` (`activateForEveryone` non copriva gli utenti mai risolti, il toggle da backoffice era inefficace); guard `role:gestore` + whitelist in `FeatureFlagManager`; fix colonna `status` ambigua in `Athlete\Dashboard`; `MesocycleList` filtrata per trainer e ownership check in `MesocycleDetail::applyProgression/forceDeload`; `GroupClassSeeder` registrato in `DatabaseSeeder`; comando `classes:send-reminders`; alias `/athlete/dashboard`; link "Volume landmarks" in AthleteProfile; dati demo: PT pending per `atleta@atleta.atleta`, abbonamento+certificato scaduti per `alessia.colombo@example.com`, badge "In attesa" in dashboard atleta. 16 nuovi test.

**Suite corrente:** 593 test (587 pass / 6 skipped). **PHPStan:** livello 6, 0 errori. **Pint:** conforme.

**DOC02** (2026-08-27): assessment funzionale R09+ (`docs/reviews/r09-plus-test-assessment.md`), piano di test manuale 109 casi (`docs/testing/r09-plus-functional-test-plan.md`), `FunctionalTestSeeder` con 5 scenari demo (corsi collettivi + waitlist, notifiche, check-in ingressi esauriti, abbonamento in scadenza, orari apertura). Findings documentati: F-01/F-02/F-04 risolti in FIX02.

**FIX02** (2026-08-27): `OpeningHoursSeeder` idempotente con `firstOrCreate` (risolve F-01/F-04); `ClassBookingService::enroll` controlla overlap atleta PT+corso (risolve F-02).

**SET01 Step 1** (2026-08-29): sezione Impostazioni backoffice (`/backoffice/settings`), unificazione flag. `SettingsHub` + `FeatureFlagManager` (spostato da Admin → Settings). Pattern uniforme per tutti e 4 i flag: `Setting::bool(key, default) && <condizione_scope>`; toggle sempre `Setting::write` + `Feature::purge`. `SettingsFlagSeeder` idempotente. Redirect 301 da vecchia route. Sidebar: ADMIN soppresso, tutto in IMPOSTAZIONI. Chiude DIFETTO-A e DIFETTO-B. 448 test (442 pass / 6 skipped).

**SET01 Step 2** (2026-08-29): chiude GAP-03 (route /reports/* con middleware `can:view-financial-reports`); aggiunge 9 nuovi flag globali con kill switch completo a tutti i livelli (route, Livewire, view, job). `config/features.php` ristrutturato con campo `group` (Moduli / Sessione atleta / Sistema). `FeatureFlagManager` ora raggruppa i 13 flag per gruppo. 17 nuovi test (FeatureFlagGatingTest 10, OutboundNotificationsKillSwitchTest 7). Suite: 465 test (459 pass / 6 skipped).

**SET01 Step 2B** (2026-08-29): gating completo `messaging` e `pt_bookings`. `messaging`: Alpine store `messages.init()` non emette fetch a `unread-count` quando flag spento; link "Apri messaggi" in dashboard empty-state gated. `pt_bookings`: gate `view-athlete-bookings` (PT OR corsi collettivi); route `/athlete/bookings` gated; tab PT e contenuto tab PT in `@feature('pt_bookings')`; `Booking::mount()` forza `activeTab='classes'` se PT off; link "Prenota" in bottom-nav gated con `@can`. `TrainerAvailabilityObserver` lasciato attivo (consistenza dati, non invio). 8 nuovi test (ModuleFlagGatingTest). Suite: 474 test (468 pass / 6 skipped).

**SET01 Step 2C** (2026-08-29): gating sei flag "Sessione atleta" + fix navigazione filtrata. Nav: sidebar "Progressi" href condizionale su `weekly_volume` (fallback a `athlete.measurements`); toast PR wrappato in `@feature('personal_records')`; link recap "Ultimo allenamento" in dashboard wrappato in `@feature('session_recap')`. Flag `plate_calculator` aggiunto a `config/features.php` managed_flags e `AppServiceProvider` (nessun gating point atleta, rimosso in UX01). `WorkoutSession::completeSet/quickLog`: `PersonalRecordDetector` ora sempre eseguito; dispatch evento `pr-achieved` condizionale su flag (PR sempre scritto in DB anche con flag off). `TrainerAvailabilityObserver` lasciato attivo. 10 nuovi test (SessionFlagGatingTest). Pint fix preesistente FeedbackDemoSeeder. Suite: 484 test (478 pass / 6 skipped).

**SET01 Step 3** (2026-08-29): manualistica backoffice. `ManualRenderer` service (slug-safe, cache mtime, `Str::markdown()`); `ManualViewer` componente Livewire embedded in tab "Manuale" di SettingsHub; 6 sezioni Markdown (`resources/docs/manual/01-06`): Dashboard, Tesserati, Abbonamenti, Accessi e check-in, Scadenze, Esercizi. `docs/manual-howto.md` per aggiungere sezioni. Fix `OpeningHoursManager` SQL MySQL-specifico (`MONTH/DAY` → `orderBy`). 11 nuovi test (ManualViewerTest). Suite: 506 test (500 pass / 6 skipped).

**SET01 Step 4** (2026-08-30): sezioni manuale 7-16: Schede template e mesocicli, Progressione e volume landmarks, Calendario e disponibilita', Prenotazioni PT, Corsi collettivi, Comunicazione e campagne, Report allenamento, Report finanziari, Inventario dischi, Impostazioni di sistema. `SECTION_FLAGS` in `ManualViewer` mappato: `11-corsi-collettivi → group_classes`, `14-report-finanziari → financial_reports`. Badge ON/OFF attivi in sidebar manuale per le sezioni gated. Suite: 506 test (500 pass / 6 skipped).

**SET01 Chiusura** (2026-08-30): Fase 3 completata — tabella flag CLAUDE.md allineata a `config/features.php` (gruppi `financial_reports`/`periodization_engine` corretti da Moduli a Sistema; platea `outbound_notifications` corretta); `component-map.md` aggiornato (5 route, 8 componenti, observer, comandi, seeder mancanti); 5 scostamenti manuale/menu identificati e tutti risolti: S-01 navigazione sezione 08, S-02 voce sidebar "Report finanziario" (`can:view-financial-reports`), S-03 sottosezione feedback utenti in sezione 12, S-04 route messaggistica corretta, S-05 `Admin/FeatureFlagManager.php` eliminato. Pint fix su `ManualViewer` e `ManualRenderer`. Suite: 506 test (500 pass / 6 skipped). SET01 definitivamente chiuso.

Storico completo release e audit: **`CHANGELOG.md`**.

**DOC02** (2026-08-30): allineamento documentazione post-SET01/PERF01. Fix B-03 (route prenotazioni manuale sez. 09/10); piani test per ruolo aggiornati (sez. Impostazioni, plate calculator rimosso, note flag, notifiche R10); consolidamento `docs/review/` + `docs/audit/` → `docs/reviews/`; `11-doc02-chiusura.md`. 12 commit, solo `.md`.

**v1.2.4** (2026-08-30): tag di allineamento post-SET01. `ArtisanRunner` — pagina comandi Artisan per il gestore (`/backoffice/settings/artisan`). develop allineato a master.
**v1.2.4+** (2026-08-31): fix Pint `ordered_imports` + `fully_qualified_strict_types` in `routes/backoffice.php` (CI ripristinata).

**API01** (2026-09-01): foundation superficie API HTTP JSON. Sanctum v4, migration `personal_access_tokens` e `is_service_account` su users, flag `public_api` (Sistema, default false), rate limiter Redis (60/min auth, 10/min anon), middleware `EnsureApiEnabled` (kill switch), formato errori uniforme con chiave `code` stabile su tutti gli status, `GET /api/v1/ping` (esente da auth e flag), `GET /api/v1/me`, 3 comandi artisan (`api:create-service-account`, `api:issue-token`, `api:tokens`). Fix gate `view-training-reports` riscritto in positivo. 17 nuovi test. Suite: 523 test (517 pass / 6 skipped). PHPStan 0 errori. Pint conforme.

**API02** (2026-09-01): 7 endpoint di lettura. `GET /api/v1/subscription-plans`, `GET /api/v1/members` (search + is_active + cert_expiry_before con ability guard), `GET /api/v1/members/{id}`, `GET /api/v1/members/{id}/subscription`, `GET /api/v1/access-logs` (filtri member_id + range date con cap 31 gg), `GET /api/v1/exercises` (filtri muscle/equipment/mechanic/measurement_type), `GET /api/v1/exercises/{slug}`. `medical_cert_expiry` assente senza `members:medical-read`. Soft-deleted mai esposti. Ability whitelist in `api:issue-token`. 7 JsonResource, 4 FormRequest, 5 controller, route con middleware `abilities:*`. Test: kill switch × 4 endpoint, 401 × 4, 403 × 4, filtri, paginazione, N+1, medical conditional, soft-delete guard, whitelist command. `docs/api/03-endpoints.md` creato.

**API03** (2026-09-01): 3 endpoint write/module. `POST /api/v1/access-logs` (check-in via totem API): 201+Location su successo, 200 su duplicato entro finestra idempotenza (5 min, configurabile), 422 con `code` stabile per cert/subscription/accesses, 404 per tesserato mancante/soft-deleted. `GET /api/v1/group-classes` e `GET /api/v1/class-occurrences`: gated su flag `group_classes` → 503 `module_disabled`; no N+1 (eager load confirmedBookings). `AccessService` estratto da QuickCheckin e AccessLogList (race condition fissa con `DB::transaction + lockForUpdate`; idempotency window come parametro esplicito). `CheckinResult` readonly class + `CheckinFailure` enum. 25 nuovi test (AccessServiceTest 13, ApiCheckinTest 12 + ApiGroupClassesTest 13). Suite: 593 test (587 pass / 6 skipped). PHPStan livello 6, 0 errori. Pint conforme.

Prossima attività: API04 (prenotazioni corsi) o altra feature su richiesta.

## Architettura offline

**Strategia:** degraded mode — online usa Livewire standard, offline intercetta le azioni in Alpine e le accoda in IndexedDB.

**Alpine store `syncQueue`** (in workout-session.blade.php):
- `enqueue(operation, payload)`: salva in IDB con `crypto.randomUUID()` come `client_uuid`
- `flush(wire)`: POST batch a `/athlete/session/sync`, poi `wire.$refresh()`
- `isPending(setId)`: controlla se il set ha ops in coda
- Retry con backoff esponenziale: 2s → 4s → 8s ... max 30s
- `window._igWire`: ref globale al `$wire` Livewire, impostato in `x-init` del root div

**Endpoint sync:** `POST /athlete/session/sync` (guard `web`, middleware `auth + role:atleta`)
- Accetta `operations[]` con `client_uuid`, `operation`, `client_timestamp` (ms epoch), `payload`
- Operazioni: `quick_log`, `complete_set`, `generate_warmup`, `delete_warmup`
- Idempotenza: `sync_operations.client_uuid UNIQUE` — replay ignorato con `status: skipped`
- Conflitto: `completed_at` server (ms) > `client_timestamp` → `status: skipped_conflict` (last-write-wins server)
- Ownership: `whereHas('sessionExercise.session.week.mesocycle', athlete_id)` su ogni set

**Service worker v2:**
- Statici: stale-while-revalidate (ritorna cache, aggiorna in background)
- `/athlete/session/*`: network-first con fallback cache (pagina navigabile offline per tutta la sessione)
- Livewire e pagine dinamiche: network-only, nessuna cache

## Setup pilota — dati e procedure

### Seeder pilota (idempotenti)

```bash
php artisan db:seed --class=PilotSeeder          # piani abbonamento + account gestore
php artisan db:seed --class=PilotTemplateSeeder  # template PPL ipertrofia 4 sett.
```

### Seeder test funzionali (idempotente, solo ambienti non-production)

```bash
php artisan db:seed --class=FunctionalTestSeeder
```

Crea 4 scenari per il piano di test `docs/testing/r09-plus-functional-test-plan.md`:
- **Yoga Full**: occorrenza yoga `now+3` capacity=3, 3 confirmed + Federica in waitlist (TC-CLS-009/012)
- **Carlo Accessi** (`carlo.accessi@functional-test.demo` / `demo1234`): abb. a ingressi con `accesses_remaining=0` (TC-CHK-004)
- **Overlap trainer**: ClassOccurrence + PtBooking per Trainer 1 stesso slot `now+5` 14:00 (REG-003)
- **Occorrenza passata**: `now-2` status=planned per test attendance (TC-CLS-015/016)

`OpeningHoursSeeder` e' idempotente (usa `firstOrCreate`): sicuro da rieseguire in qualsiasi ambiente.

### Account pilota locale

- Gestore: `gestore@iron-gym.test` / `changeme` (da `.env` PILOT_MANAGER_EMAIL/PASSWORD)
- Trainer demo: `trainer@trainer.trainer`

### Feature flags pilota (impostati via SettingsFlagSeeder)

Tutti i flag usano `Setting::bool(key, default) && <condizione_scope>`.
Toggle sempre via `Setting::write` + `Feature::purge` (non `activateForEveryone`).

| Flag | Chiave `settings` | Stato pilota | Platea (quando acceso) | Gruppo |
|---|---|---|---|---|
| `group_classes` | `group_classes_enabled` | ON | Tutta la palestra | Moduli |
| `messaging` | `messaging_enabled` | ON | Tutta la palestra | Moduli |
| `pt_bookings` | `pt_bookings_enabled` | ON | Tutta la palestra | Moduli |
| `financial_reports` | `financial_reports_enabled` | ON | Solo gestore | Sistema |
| `periodization_engine` | `periodization_engine_enabled` | ON | Gestore + trainer beta | Sistema |
| `readiness_check` | `readiness_check_enabled` | ON | Atleti | Sessione atleta |
| `exercise_substitution` | `exercise_substitution_enabled` | ON | Atleti | Sessione atleta |
| `session_recap` | `session_recap_enabled` | ON | Atleti | Sessione atleta |
| `personal_records` | `personal_records_enabled` | ON | Atleti | Sessione atleta |
| `weekly_volume` | `weekly_volume_enabled` | ON | Atleti | Sessione atleta |
| `plate_calculator` | `plate_calculator_enabled` | ON | Atleti (nessun gating point, riservato a usi futuri) | Sessione atleta |
| `push_notifications` | `push_notifications_enabled` | OFF | Atleti e trainer | Sistema |
| `outbound_notifications` | `outbound_notifications_enabled` | ON | Tutta la palestra (flag globale) | Sistema |
| `in_app_feedback` | `in_app_feedback_enabled` | OFF | Tutti | Sistema |
| `public_api` | `public_api_enabled` | OFF | Account di servizio (api_client) | Sistema |

Per modificare flags: backoffice → Impostazioni → Funzioni (solo gestore).

### Procedura registrazione atleta pilota

Sequenza completa — tutto via backoffice UI:

**1. Crea tesserato + account** — Tesserati → Nuovo tesserato
   - Campi obbligatori: Cognome, Nome, Email, Scadenza cert. medico
   - Spunta **"Crea account accesso app"** → inserisci password (min. 8 caratteri)
   - Sistema crea User con ruolo `atleta` e collega `user_id` in automatico

**2. Crea abbonamento** — Abbonamenti → Nuovo abbonamento
   - Seleziona tesserato + piano + data inizio → scadenza calcolata in automatico
   - Nota: colonne DB sono `started_at` / `expires_at` (non start_date/end_date)

**3. Assegna mesociclo PPL** — Mesocicli → Assegna mesociclo
   - Seleziona atleta + template + data inizio → Avanti → Conferma

### Template PPL — struttura

`database/seeders/PilotTemplateSeeder.php` — "PPL Ipertrofia — Intermediato (4 sett.)"

- 3 sessioni/sett: Push (petto/spalle/tricipiti), Pull (schiena/bicipiti), Legs (gambe/glutei/polpacci)
- W1: 3 serie compound + 3 iso | W2: 4+3 | W3: 4+4 | W4 deload: 2+2 @RIR+1
- 12 TemplateSession, 200 ExerciseSet per mesociclo istanziato

**Flusso assegnazione:** backoffice → Mesocicli → Assegna → scegli template + atleta + data inizio.

## Catalogo esercizi — SQLite di riferimento

`database/database.sqlite` contiene catalogo completo queryabile senza MySQL:
- Tabelle: `movement_patterns` (27), `muscles` (26), `equipment` (14), `exercises` (83), `exercise_muscle` (259), `exercise_equipment` (108)
- Colonna `execution_description` su `exercises` con testo esecuzione per tutti e 83
- Script rigenerazione: `.claude/scripts/build_exercises_sqlite.py` (stdlib Python, nessuna dipendenza extra; sorgente unica: `exercises_seed.sql`)

Usare sqlite3 o DBeaver per interrogarlo. Non usato dai test (quelli usano `:memory:`).

## Documenti di dominio

Disponibili in .claude/docs/domain/ ma NON caricati automaticamente per non saturare contesto. Richiedili esplicitamente quando servono:
- .claude/docs/domain/step-0-discovery.md — ERD, schema SQL, regole progressione
- .claude/docs/domain/exercises-catalog.md — catalogo 83 esercizi (tassonomia, muscoli, note metodologiche; SQL rimosso → dati in database.sqlite)
- .claude/docs/domain/glossary.md — terminologia BB e tassonomia (documento corto, ok includerlo)

## Brand identity PWA atleta (UX01)

Layer CSS dedicato alla PWA atleta. Dark theme di default; light theme via toggle (localStorage + `data-theme` su `<html>`).

**Layout:** `resources/views/layouts/athlete.blade.php` — NON usa AdminLTE. Standalone.

**CSS entry point:** `public/css/athlete.css` (statico, caricato con `<link>` nel layout atleta).
Struttura: design tokens → base → navigazione → componenti legacy → componenti `ig-*` → volume.

**Design tokens:** CSS custom properties su `:root` (dark default) e `[data-theme="light"]`.
Token principali: `--ig-bg`, `--ig-surface`, `--ig-surface-raised`, `--ig-border`, `--ig-text-1/2/3`,
`--ig-accent`, `--ig-success/warning/danger` + varianti `-subtle`,
`--ig-touch-target` (56px), `--ig-touch-target-sm` (40px), `--ig-touch-target-xl` (64px — CTA sessione),
`--ig-bottom-nav-h` (72px), `--ig-nav-icon` (26px),
`--ig-font-sans`, `--ig-text-xs` (11px) `/sm` (13px) `/base` (16px) `/md` (22px) `/lg` (26px) `/xl` (34px) `/display` (48px),
`--ig-sp-1..10`, `--ig-radius-sm/lg/full`.

**Componenti Blade (namespace `x-athlete.*`):**
Path: `resources/views/components/athlete/`
- `x-athlete.button` — varianti primary/secondary/ghost/danger; min-height `--ig-touch-target`; spinner wire:loading integrato
- `x-athlete.card` — superficie base; props `padding`, `mb`, `tag`
- `x-athlete.stat` — label + valore numerico grande; props `label`, `unit`
- `x-athlete.badge` — status pill; prop `status` → classe `ig-badge--{status}`
- `x-athlete.input-number` — input numerico con `inputmode`; prop `stepper` per bottoni +/−

**Vite bundle atleta:** `@vite(['resources/css/app.css', 'resources/js/app.js'])` — `app.css` = solo
Tailwind base utilities; `app.js` = vuoto. Il backoffice NON usa `@vite()`. Separazione de facto già esistente.

**Documentazione design system:** `docs/architecture/ui-atleta.md`

---

## Brand identity backoffice

Layer CSS isolato e disattivabile sopra AdminLTE 3.x — nessun fork del tema.

**Palette:**
- Accent: `#E85D04` (arancio brand, shared con area atleta)
- Sidebar: `#1A1A2E` (navy scuro)
- Sidebar header: `#13132A`

**Font (Google Fonts):**
- Titoli / sidebar brand-text: `Oswald` 400/600/700
- Corpo testo: `Inter` 400/500/600

**File:**
- `public/css/iron-gym-brand.css` — override scoped su `body.iron-gym-brand` (CSS custom properties + override Bootstrap/AdminLTE)
- `public/css/backoffice.css` — utilities: `filter-w-xs/sm/md/lg`, `table-actions`, `.skip-link`
- `public/images/iron-gym-logo.svg` — dumbbell icon 32×32 arancio

**Attivazione:** `config/adminlte.php` → `'classes_body' => 'iron-gym-brand'`
**Disattivazione:** cambiare in `''` — rimuove tutto il layer in 1 riga.

**Convenzioni UI (post-audit 2026-06-28):**
- Bottoni azione tabella: `btn-sm` (non `btn-xs`)
- Errori form: `is-invalid` + `invalid-feedback` (non `text-danger small`)
- Width filtri: classi `filter-w-*` (non inline `style="width:Npx"`)
- Modali custom: `role="dialog"` + `aria-modal="true"` + `aria-labelledby`
- Bottoni icon-only: `aria-label` obbligatorio

## Cosa NON fare

- Non proporre Vue.js, Inertia, SPA.
- Non proporre Filament, Nova, Backpack.
- Non introdurre multi-tenancy.
- Non aggiungere colonne o tabelle senza discuterne prima.
- Non usare emoji nel codice o nei commenti.
- Non creare model Eloquent chiamati `Workout` o `WorkoutExercise`. `app/Livewire/Athlete/WorkoutSession.php` è un componente Livewire per il logging live della sessione, non un Model Eloquent: il nome simile non viola questo divieto.

## Superficie API HTTP JSON

Prefisso: `/api/v1`. Auth: Sanctum personal access token (`Authorization: Bearer <token>`).  
Documentazione: `docs/api/` (assessment, piano release, convenzioni).

**Kill switch:** flag `public_api` in `config/features.php`, chiave settings `public_api_enabled`, default `false`.  
Spento → tutte le route `/api/v1/*` rispondono 503 JSON tranne `/ping`.

**Account di servizio:** `is_service_account = true` su `users`, ruolo `api_client` senza permessi.  
Non possono autenticarsi via browser (blocco in `LoginForm::authenticate()`).  
Non compaiono nelle liste `User::role('atleta')`, `User::role('trainer')` ecc.

**Abilities token:** namespace separato dai gate web (es. `members:read`, `access-logs:write`).  
Mai delegare ai gate role-based dall'API.

**Formato errori:** tutte le risposte di errore hanno `message` + `code` (stabile).  
`errors` aggiunto solo per 422. Nessuno stack trace in produzione.

| Code | HTTP | Causa |
|---|---|---|
| `unauthenticated` | 401 | Token assente/revocato |
| `forbidden` | 403 | Ability mancante |
| `not_found` | 404 | Risorsa inesistente (mai "Server Error") |
| `validation_failed` | 422 | Payload non valido |
| `rate_limited` | 429 | Rate limit superato |
| `cert_invalid` | 422 | Cert. medico scaduto/mancante (check-in) |
| `subscription_inactive` | 422 | Nessun abbonamento attivo (check-in) |
| `accesses_exhausted` | 422 | Accessi residui esauriti (check-in) |
| `api_disabled` | 503 | Kill switch spento |
| `module_disabled` | 503 | Flag di modulo spento (es. `group_classes`) |

**Comandi artisan:**

```bash
# Crea account di servizio per un consumer (idempotente)
php artisan api:create-service-account <consumer-slug>

# Emette token con abilities specifiche (plain text stampato una volta)
php artisan api:issue-token <consumer-slug> --name="<desc>" --abilities="members:read"

# Elenca token attivi; revoca singolo token
php artisan api:tokens
php artisan api:tokens --consumer=<slug>
php artisan api:tokens --revoke=<token-id>
```

**Rate limiting:** Redis, 60 req/min per token (autenticato) o 10 req/min per IP (anonimo).  
Configurabile via `config/api.php` o env `API_RATE_LIMIT_AUTH` / `API_RATE_LIMIT_ANON`.

**Endpoint disponibili (API01 + API02 + API03):**

| Metodo | Path | Auth | Ability | Kill switch |
|---|---|---|---|---|
| GET | /api/v1/ping | No | — | Esente |
| GET | /api/v1/me | Bearer | — | Sì |
| GET | /api/v1/subscription-plans | Bearer | `subscription-plans:read` | Sì |
| GET | /api/v1/members | Bearer | `members:read` | Sì |
| GET | /api/v1/members/{id} | Bearer | `members:read` | Sì |
| GET | /api/v1/members/{id}/subscription | Bearer | `members:read` | Sì |
| GET | /api/v1/access-logs | Bearer | `access-logs:read` | Sì |
| POST | /api/v1/access-logs | Bearer | `access-logs:write` | Sì |
| GET | /api/v1/exercises | Bearer | `exercises:read` | Sì |
| GET | /api/v1/exercises/{slug} | Bearer | `exercises:read` | Sì |
| GET | /api/v1/group-classes | Bearer | `group-classes:read` | Sì + `group_classes` |
| GET | /api/v1/class-occurrences | Bearer | `group-classes:read` | Sì + `group_classes` |

**Abilities whitelist** (`api:issue-token`): `subscription-plans:read`, `members:read`, `members:medical-read`, `access-logs:read`, `access-logs:write`, `exercises:read`, `group-classes:read`, `*` (test/staging only).  
Riferimento completo: `docs/api/03-endpoints.md`.

## Comandi utili

```bash
# Ambiente dev
docker compose up -d
php artisan serve
npm run dev
php artisan queue:work redis --queue=default
php artisan schedule:work

# DB
php artisan migrate:fresh --seed

# Qualità
./vendor/bin/pest
./vendor/bin/phpstan analyse --memory-limit=512M
./vendor/bin/pint --test

# Go-live: inizializza piani abbonamento reali e account gestore
php artisan pilot:init

# Genera icone PWA da resources/images/icon.png
php artisan pwa:generate-icons

# Rigenera SQLite di riferimento esercizi (AI/dev tool, non prod; stdlib Python)
python .claude/scripts/build_exercises_sqlite.py
```