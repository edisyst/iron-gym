# Storico release — iron-gym

Dettaglio release per release. Vedi anche `CHANGELOG.md` per il log completo.

## Milestone principali

- **Step 1-10** + **Release 01-08** + **UX01-07**: core gestionale, WorkoutSession, brand layer atleta/backoffice. Tag **v0.9.0** (2026-07-05).
- **v1.2.3** (2026-08-24): fix Pint `binary_operator_spaces` in 4 file di test. CI ripristinata.
- **Audit** sicurezza v2, receptionist, funzionale PWA atleta, HK01, DOC01 completati.

## R09 — Corsi collettivi (2026-08-24/25)

- **Step 1**: schema GroupClass→ClassSchedule→ClassOccurrence, consumer adattati.
- **Step 2**: command `classes:generate-occurrences`, prerequisiti enroll (abbonamento+cert), overlap check atleta/trainer, 29 test.
- **Step 3**: ClassScheduleManager CRUD palinsesto, attendance tracking (completeOccurrence/markAttended/markNoShow), 13 test.
- **Step 4**: ClassOccurrenceCancelledNotification + NotifyClassCancellation job, check-in receptionist, feature flag gate Athlete\Booking, 7 test.
- **Step 5**: GroupClassCatalog CRUD (solo gestore), sidebar submenu 3 voci, dashboard atleta card prossimi corsi, 8 test.
- **Step 6**: finestra prenotazione (booking_opens_days/booking_closes_minutes), finestra cancellazione gratuita (free_cancel_hours), removeParticipant → cancelled_by_gym, 5 test. R09 chiuso.

## R10-R31 — Profilo atleta e gestione (2026-08-25/26)

- **R10**: centro notifiche atleta (`/athlete/notifications`), badge, endpoint `unread-count`, 7 test.
- **R11**: ClassReminderNotification (database+webpush) + SendClassReminders job `dailyAt('08:00')`, 6 test.
- **R12**: `periodization_engine` attivato in PilotSeeder; test MesocycleDetail (6) e VolumeLandmarkManager (6).
- **R13**: tab "Abbonamento" profilo atleta, fix `CONCAT→||` cross-db, 14 test.
- **R14**: sessioni PT future dashboard atleta, fix `CONCAT→||` ManagerDashboard, 10 test.
- **R15**: test BookingList (7) e CommunicationCampaign (4), `group_classes` attivato PilotSeeder. R15 chiuso.
- **R16**: tab "Sessioni PT" profilo atleta, 5 test.
- **R17**: tab "Misurazioni" profilo atleta, 5 test.
- **R18**: tab "Record" profilo atleta (e1RM), 5 test.
- **R19**: tab "Sessioni" profilo atleta, 5 test.
- **R20**: tab "Messaggi" profilo atleta, 5 test.
- **R21**: tab "Corsi" profilo atleta (gated `group_classes`), fix SQL ambiguous column, 5 test.
- **R22**: Pannello Scadenze backoffice (`/backoffice/members/expiry`), 7 test.
- **R23**: widget "Scadenze imminenti" dashboard backoffice, 4 test.
- **R24**: Check-in Rapido backoffice (`/backoffice/checkin`), 7 test.
- **R25**: Rinnovo abbonamento rapido, SubscriptionForm pre-popola da query string, 5 test.
- **R26**: tab "Accessi" profilo atleta, 5 test.
- **R27**: sospensione/riattivazione abbonamento (solo gestore), 5 test.
- **R28**: icona note interne in MemberList, 4 test.
- **R29**: Export CSV abbonamenti, solo gestore, 4 test.
- **R30**: Export CSV tesserati, solo gestore, 4 test.
- **R31**: tabella "Sessioni PT completate per trainer" in ManagerDashboard, 3 test.

## FIX01 (2026-08-26)

Flag `group_classes` spostato su `settings`; guard `role:gestore` + whitelist in FeatureFlagManager; fix colonna `status` ambigua in Athlete\Dashboard; MesocycleList filtrata per trainer; GroupClassSeeder in DatabaseSeeder; comando `classes:send-reminders`; alias `/athlete/dashboard`; dati demo scaduti/PT pending. 16 test.

## DOC02 + FIX02 (2026-08-27)

Assessment funzionale R09+, piano test manuale 109 casi, FunctionalTestSeeder (5 scenari demo). F-01/F-02/F-04 risolti: OpeningHoursSeeder idempotente, ClassBookingService controlla overlap PT+corso.

## SET01 — Impostazioni e feature flags (2026-08-29/30)

- **Step 1**: SettingsHub + FeatureFlagManager unificato, `Setting::bool + Feature::purge` pattern, 448 test.
- **Step 2**: kill switch completo 9 nuovi flag su tutti i livelli, `config/features.php` con campo `group`, 17 test.
- **Step 2B**: gating `messaging` e `pt_bookings` (Alpine store, route, nav, tab), 8 test.
- **Step 2C**: gating 6 flag "Sessione atleta", PR sempre scritto in DB, nav condizionale, 10 test.
- **Step 3**: ManualRenderer + ManualViewer, 6 sezioni Markdown, 11 test.
- **Step 4**: sezioni manuale 7-16, SECTION_FLAGS per badge ON/OFF. SET01 chiuso.

## v1.2.4 (2026-08-30)

ArtisanRunner (`/backoffice/settings/artisan`). develop allineato a master.  
**v1.2.4+** (2026-08-31): fix Pint `ordered_imports` + `fully_qualified_strict_types` in `routes/backoffice.php`.

## DOC02 chiusura (2026-08-30)

Allineamento documentazione post-SET01: fix route prenotazioni manuale, consolidamento `docs/reviews/`.

## API01-04 (2026-09-01)

- **API01**: foundation Sanctum v4, `is_service_account`, flag `public_api`, rate limiter Redis, EnsureApiEnabled, formato errori `code` stabile, `/ping` + `/me`, 3 comandi artisan. 17 test. Suite: 523.
- **API02**: 7 endpoint lettura (members, subscriptions, exercises, access-logs), 7 JsonResource, 4 FormRequest. `docs/api/03-endpoints.md`. Test kill switch/401/403/filtri/N+1.
- **API03**: POST /api/v1/access-logs (check-in totem, idempotenza 5min), GET group-classes/class-occurrences (gated), AccessService estratto con lockForUpdate, CheckinResult+CheckinFailure. 25 test. Suite: 593.
- **API04**: GET/POST/DELETE /api/v1/class-bookings (enroll idempotente, waitlist, cancel). Fix `whereDate()` SQLite. 31 test. Suite: 624.

## DOC03 (2026-09-01)

Swagger UI + openapi.yaml (15 endpoint, tutti i component riutilizzabili). `/backoffice/settings/api-docs` (gestore). Download YAML. Try-it-out disabilitato.
