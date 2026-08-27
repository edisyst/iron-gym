# Assessment — Test Funzionali R09+ (Iron Gym)

> Prodotto da analisi read-only del codice. Fonte di verità: codice sorgente.
> Commit base: `2cf59a8` (chiusura UX07, pre-R09). HEAD: `4d4e1a8`.

---

## 1. Feature implementate per release

### R09 — Corsi Collettivi (5 step, chiuso)

**Schema e modelli** (`database/migrations/2026_08_24_*`)
- `GroupClass` → `ClassSchedule` → `ClassOccurrence` (schema a 3 livelli)
- Tabella pivot `class_trainer` (abilitazione trainer su corso)
- `ClassBooking` trasformata: aggiunto `position`, `attended_at`, `booked_by`; ENUM status esteso
- `ClassOccurrence`: accessor `confirmed_count`, `available_spots`, `is_full`
- `ClassSchedule`: palinsesto ricorrente con `weekday` (0=lun..6=dom), `valid_from/valid_until`

**Servizi**
- `ClassBookingService::enroll()`: prerequisiti abbonamento attivo + cert valido, overlap atleta, lockForUpdate
- `ClassBookingService::cancel()`: `cancelled_by_athlete` o `cancelled_by_gym`, promozione waitlist
- `PtBookingService`: aggiunto check overlap con `ClassOccurrence` per il trainer

**Comandi**
- `classes:generate-occurrences` — materializza `ClassOccurrence` da `ClassSchedule` attivi (idempotente, horizon=28gg)
- Schedulato `dailyAt('03:00')` in `routes/console.php`

**Livewire backoffice**
- `GroupClassManager` — occorrenze: lista, dettaglio, form, completeOccurrence, markAttended, markNoShow, removeParticipant, deleteClass
- `ClassScheduleManager` — CRUD palinsesto (gestore + trainer)
- `GroupClassCatalog` — CRUD definizioni corso (gestore)

**Livewire atleta**
- `Athlete\Booking` — enrollClass, cancelClassBooking con finestre `config/classes.php`

**Config** `config/classes.php`:
```
booking_opens_days: 7
booking_closes_minutes: 30
free_cancel_hours: 3
generation_horizon_days: 28
```

**Notifiche e job**
- `ClassOccurrenceCancelledNotification` (database + mail-like; inviata solo a `confirmed`)
- `NotifyClassCancellation` job (dispatchato da `GroupClassManager::deleteClass`)
- `WaitlistPromotionNotification` (già pre-R09, ora integrata in `cancel`)

---

### R10 — Centro Notifiche Atleta (chiuso)

- `Athlete\Notifications` Livewire: lista, markRead, markAllRead, deleteNotification
- Route `/athlete/notifications` + endpoint `/athlete/notifications-unread-count` (JSON)
- Badge non lette nella sidebar atleta (via `NotificationBell` o polling endpoint)
- Fix `route('athlete.booking')` → `athlete.bookings` (inesistente, corretto in CLAUDE.md come `athlete.bookings`)

---

### R11 — Promemoria Corsi (chiuso)

- `ClassReminderNotification` (database + webpush)
- `SendClassReminders` job: invia a prenotazioni `confirmed` per occorrenze del giorno seguente
- `SendClassRemindersCommand` (`classes:send-reminders --sync`)
- Schedulato `dailyAt('08:00')`

---

### R12 — Periodization Engine (chiuso)

- `PilotSeeder` attiva `periodization_engine` e `financial_reports` via `Feature::activateForEveryone`
- Test per `MesocycleDetail` (applyProgression, forceDeload, role guards)
- Test per `VolumeLandmarkManager` (save, resetToDefaults, auth trainer)

---

### R13 — Abbonamento nel Profilo Atleta (chiuso)

- `Athlete\Profile` tab `abbonamento`: piano, date, badge stato (Attivo/Scaduto/In scadenza)
- Fix `CONCAT→||` in `TrainingReport` e `GlobalSearch` per compatibilità SQLite nei test

---

### R14 — Sessioni PT nella Dashboard Atleta (chiuso)

- `Athlete\Dashboard::upcomingPtBookings()`: PT futuri `confirmed/pending`
- Fix `CONCAT→||` in `ManagerDashboard`

---

### R15 — BookingList + CommunicationCampaign (chiuso)

- `BookingList`: confirm, cancel (con motivo + validazione), restore; guard ownership trainer
- `CommunicationCampaign`: send job, validazione body, filtro `active`
- `group_classes` attivato in `PilotSeeder` (via `Setting::write`)

---

### R16–R21 — Tab Profilo Atleta (chiuse)

| Release | Tab | Dati mostrati |
|---|---|---|
| R16 | `pt` | Prossime PT (confirmed/pending) + storico ultimi 10 |
| R17 | `misurazioni` | Ultimi 5 BodyMeasurement + link pagina completa |
| R18 | `record` | Ultimi 5 PersonalRecord e1RM + link pagina completa |
| R19 | `sessioni` | Ultime 5 completed/skipped + link storico |
| R20 | `messaggi` | Ultimi 5 Message + badge non letti + link /athlete/messages |
| R21 | `corsi` | Gated `group_classes`; prossimi confirmed/waitlisted + storico |
| R26 | `accessi` | Ultimi 5 AccessLog + piano |

---

### R22–R24 — Backoffice Operativo (chiuse)

- **R22** `ExpiryDashboard` (`/backoffice/members/expiry`): certificati + abbonamenti in scadenza, filtri live; gestore + receptionist
- **R23** Widget "Scadenze imminenti" in `Dashboard` backoffice: counter cert 30gg + abb 7gg
- **R24** `QuickCheckin` (`/backoffice/checkin`): ricerca live, validazione cert+abb+accessi, cronologia odierna; gestore + receptionist

---

### R25–R31 — Operazioni Abbonamenti + Export + Stats (chiuse)

- **R25** Rinnovo rapido: bottone "Rinnova" in `SubscriptionList`; `SubscriptionForm::mount()` prepopola da querystring
- **R27** Sospensione/riattivazione abbonamento (solo gestore); filtro "Sospesi"
- **R28** Note interne tesserato: icona `fa-sticky-note` in `MemberList`
- **R29** Export CSV abbonamenti (`/backoffice/subscriptions/export`), solo gestore; BOM UTF-8, `;`
- **R30** Export CSV tesserati (`/backoffice/members/export`), solo gestore
- **R31** Tabella PT completate per trainer in `ManagerDashboard` con filtro periodo

---

### FIX01 — Fixes post-R31 (chiuso, commit `4d4e1a8`)

- `group_classes` spostato su `settings.group_classes_enabled` (era `Feature::activateForEveryone`)
- `FeatureFlagManager`: guard `role:gestore` + whitelist flag gestibili
- Fix colonna `status` ambigua in `Athlete\Dashboard`
- `MesocycleList` filtrata per trainer, ownership check in `MesocycleDetail`
- `GroupClassSeeder` registrato in `DatabaseSeeder`
- Alias `/athlete/dashboard`
- Dati demo: PT pending per `atleta@atleta.atleta`, abb+cert scaduti per `alessia.colombo`

---

### Componenti attivi ma non in CLAUDE.md (trovati nel codice, non documentati)

- `OpeningHoursManager` (`/backoffice/settings/opening-hours`): CRUD orari apertura settimanali + override per data specifica; visibile a tutti i ruoli backoffice, modificabile da gestore + receptionist
- `DumbbellInventory` / `DumbbellInventorySeeder`: inventario manubri integrato in `PlateInventoryManager`
- `GlobalSearch` (`/backoffice/search`): ricerca atleti/trainer/template, min 2 caratteri

---

## 2. Stato feature flag `group_classes`

**Definizione** (`app/Providers/AppServiceProvider.php:62`):
```php
Feature::define('group_classes', function (): bool {
    return Setting::bool('group_classes_enabled', (bool) config('features.group_classes_enabled', false));
});
```

**Meccanismo**: globale (non per-utente). Legge `settings.group_classes_enabled` (tabella introdotta in `2026_08_26_000001_create_settings_table.php`). `activateForEveryone()` di Pennant NON copre utenti mai risolti — per questo il flag è su `settings`.

**Attivazione via PilotSeeder**:
```php
Setting::write('group_classes_enabled', true);
Feature::purge('group_classes');  // svuota cache Pennant
```

**Toggle backoffice**: `FeatureFlagManager` (solo gestore), whitelist hardcoded, scrive su `settings` e fa `Feature::purge`.

**Dove viene letto**:
- `Athlete\Booking:166,211,281,291`
- `Athlete\Dashboard:62`
- `Athlete\Profile:157`
- Gate `view-group-classes` (sidebar backoffice per voce Corsi)

---

## 3. Copertura test automatici

### Coperto da test automatici

| Area | File test | N. casi |
|---|---|---|
| R09 schema + prenotazione + overlap | `BookingTest.php` | 22 |
| R09 finestre prenotazione/cancellazione | `BookingWindowTest.php` | 5 |
| R09 cancellazione corso + notifica | `ClassCancellationTest.php` | 8 |
| R09 palinsesto CRUD | `ClassScheduleManagerTest.php` | 9 |
| R09 attendance (completeOccurrence, markAttended, markNoShow) | `AttendanceTest.php` | 7 |
| R09 generazione occorrenze | `GenerateClassOccurrencesTest.php` | 7 |
| R09 catalogo corsi CRUD | `GroupClassCatalogTest.php` | 9 |
| R10 notifiche atleta | `AthleteNotificationsTest.php` | 7 |
| R11 promemoria corsi | `SendClassRemindersTest.php` | 6 |
| R12 MesocycleDetail | `MesocycleDetailTest.php` + `MesocycleOwnershipTest.php` | 11 |
| R12 VolumeLandmarkManager | `VolumeLandmarkManagerTest.php` | 6 |
| R13 profilo atleta abbonamento | `AthleteProfileSubscriptionTest.php` | 4 |
| R13 TrainingReport | `TrainingReportTest.php` | 6 |
| R13 GlobalSearch | `GlobalSearchTest.php` | 4 |
| R14 Dashboard PT bookings | `AthleteDashboardPtBookingTest.php` | 4 |
| R14 AthleteAnalytics | `AthleteAnalyticsTest.php` | 3 |
| R14 ManagerDashboard | `ManagerDashboardTest.php` | 2 |
| R15 BookingList | `BookingListTest.php` | 7 |
| R15 CommunicationCampaign | `CommunicationCampaignTest.php` | 4 |
| R16 tab PT nel profilo | `AthleteProfilePtBookingTest.php` | 5 |
| R17 tab misurazioni | `AthleteProfileMeasurementsTest.php` | 5 |
| R18 tab record | `AthleteProfilePersonalRecordsTest.php` | 5 |
| R19 tab sessioni | `AthleteProfileSessionsTest.php` | 5 |
| R20 tab messaggi | `AthleteProfileMessagesTest.php` | 5 |
| R21 tab corsi | `AthleteProfileClassBookingsTest.php` | 5 |
| R22 pannello scadenze | `ExpiryDashboardTest.php` | 7 |
| R23 widget scadenze dashboard | `BackofficeDashboardExpiryWidgetTest.php` | 4 |
| R24 QuickCheckin | `QuickCheckinTest.php` | 7 |
| R24 Receptionist check-in + permessi | `ReceptionistCheckinTest.php` | 23 |
| R25 rinnovo abbonamento | `SubscriptionRenewalTest.php` | 5 |
| R26 tab accessi | `AthleteProfileAccessLogsTest.php` | 5 |
| R27 sospensione abbonamento | `SubscriptionSuspensionTest.php` | 5 |
| R28 note tesserato | `MemberNotesTest.php` | 4 |
| R29 export CSV abbonamenti | `SubscriptionExportCsvTest.php` | 4 |
| R30 export CSV tesserati | `MemberExportCsvTest.php` | 4 |
| R31 PT stats ManagerDashboard | `ManagerDashboardPtStatsTest.php` | 3 |
| FIX01 feature flag globale | `GlobalFeatureFlagTest.php` | 5 |
| R09 Dashboard atleta card corsi | `AthleteDashboardClassCardTest.php` | 3 |
| R14 Dashboard badge PT pending | `AthleteDashboardPendingPtBadgeTest.php` | 2 |

**Totale test automatici R09+: ~226 nuovi casi** (su 429 totali della suite).

### Gap — NON coperto da test automatici (priorità test manuali)

| Area | Perché serve test manuale |
|---|---|
| **OpeningHoursManager** | Zero test automatici. CRUD completo da verificare in UI. |
| **DumbbellInventory** in PlateInventoryManager | Zero test UI. Il seeder popola i dati ma nessun test interazione. |
| **FinancialReport** (export PDF/CSV) | Solo `ExportTest` sul job; nessun test del componente UI. |
| **Flussi multi-attore** (receptionist segna no-show → atleta vede notifica) | I test automatici testano singole azioni, non la catena completa. |
| **Promozione waitlist in UI** | Il servizio è testato; la UI (messaggio flash, aggiornamento lista) no. |
| **Finestra prenotazione** — corso tra >7 giorni (non ancora aperto) | `BookingWindowTest` testa il servizio; la pagina `/athlete/booking` con UI no. |
| **Export CSV** — download effettivo in browser | Test HTTP verifica header, non apertura file in Excel. |
| **Toggle feature flag** in UI | Test automatico usa `->call()`; il toggle via UI (gestore → atleta vede effetto) no. |
| **Sidebar backoffice** — sottomenu corsi (3 voci) | Non testato automaticamente. |
| **Badge notifiche sidebar** atleta | Il contatore badge non è testato con rendering view. |
| **Recap sessione + export PNG** | `SessionRecapBuilderTest` è unit; il flusso UI completo (completeSession → SessionFeedbackForm → SessionRecap) no. |
| **Pulsante Rinnova** → form pre-popolato, calcolo `expires_at` in UI | `SubscriptionRenewalTest` testa il componente; la navigazione da lista → form no. |
| **classes:send-reminders** esecuzione da terminale | Non testato con `php artisan` reale; solo `Job::handle()` in test. |
| **classes:generate-occurrences** in produzione (schedule) | Testato unitariamente; lo scheduling `dailyAt('03:00')` non è verificato. |

---

## 4. Findings

> Non corretti — annotati per riferimento.

| ID | File | Riga | Tipo | Descrizione |
|---|---|---|---|---|
| F-01 | `database/seeders/OpeningHoursSeeder.php` | 13 | Non-idempotente | `OpeningHour::truncate()` — se eseguito su DB non vuoto cancella dati reali. Non può essere rieseguito sicuramente su ambiente pilota. |
| F-02 | `app/Services/ClassBookingService.php` | 45–55 | Gap funzionale | `enroll()` controlla overlap corso-corso per atleta ma NON controlla se l'atleta ha una sessione PT confermata nello stesso slot. Possibile doppia prenotazione PT+corso. |
| F-03 | `database/seeders/BookingDemoSeeder.php` | 180 | Dati demo insufficienti | Circuit Training: capacity=8, participants=6 — nessun corso è al completo nel demo. Impossibile testare manualmente il flusso "corso pieno → waitlist → promozione" senza dati aggiuntivi. |
| F-04 | `database/seeders/DatabaseSeeder.php` | 24 | Non-idempotente a cascata | `OpeningHoursSeeder` è chiamato da `DatabaseSeeder` (non in blocco `isLocal()`) e usa `truncate()`. Eseguire `db:seed` (senza `--class`) su ambiente pilota cancella gli orari di apertura. |
| F-05 | `CLAUDE.md` | sezione componenti | Doc-code divergenza | `OpeningHoursManager` (`/backoffice/settings/opening-hours`) non è menzionato nella sezione "Mappa componenti". `DumbbellInventory` e `GlobalSearch` citati in CLAUDE.md ma senza route o componente dettagliato. |
| F-06 | `CLAUDE.md` | sezione stato sviluppo | Doc obsoleta | CLAUDE.md elenca `FIX01` come contenente "fix colonna status ambigua in Athlete\Dashboard" ma non menziona `OpeningHoursManager` come componente esistente introdotto prima di R09. |

---

## 5. Dati demo mancanti per test manuali completi

I seeder esistenti (`DemoSeeder`, `BookingDemoSeeder`, `R09R31DemoSeeder`, `GroupClassSeeder`) coprono molti scenari ma lasciano i seguenti gap:

| Scenario necessario | Stato | Seeder attuale | Da creare |
|---|---|---|---|
| Corso al completo con almeno 1 atleta in waitlist | **MANCANTE** | Nessuno | Serve occorrenza con `capacity=N`, N iscritti `confirmed` + almeno 1 `waitlisted` |
| Atleta senza alcun abbonamento (non scaduto, proprio assente) | Parzialmente coperto | `davide.martini` ha abb. attivo; `alessia.colombo` ha abb. scaduto | Aggiungere scenario "nessun abbonamento del tutto" o riusare `davide.martini` che ha solo l'abb. |
| Abbonamento con `accesses_remaining=0` (esauriti) | **MANCANTE** | Tutti i demo hanno `null` (illimitati) | Serve tesserato con abbonamento a ingressi contati, esauriti |
| Trainer con PT confermato nello stesso slot di un corso collettivo | **MANCANTE** | Booking e corsi hanno slot diversi | Serve overlap intenzionale per testare `PtBookingService` |
| Occorrenza futura oltre 7 giorni (finestra non aperta) | Coperto | `BookingDemoSeeder` crea `addDays(8..11)` → fuori finestra | OK |
| Occorrenza tra <30 min dall'inizio (finestra chiusa) | **MANCANTE** | Nessuno | Serve occorrenza con `start_time` = now+20min (data oggi) |
| Orari di apertura popolati | Coperto | `OpeningHoursSeeder` (ma non idempotente) | Già esiste; dipende dall'ambiente |
| Atleta iscritto a un corso che viene poi cancellato (per testare notifica) | Parzialmente coperto | `R09R31DemoSeeder` inserisce una notifica `class_cancelled` simulata | Il flusso reale (deleteClass da UI → notifica) non ha dati dedicati |

### Account demo disponibili

| Ruolo | Email | Password | Note demo |
|---|---|---|---|
| Gestore | `admin@admin.admin` | `admin` | Accesso completo |
| Trainer 1 | `trainer@trainer.trainer` | `trainer` | Luca Bianchi |
| Trainer 2 | `trainer2@trainer.trainer` | `trainer` | Elena Russo |
| Receptionist | `receptionist@receptionist.receptionist` | `receptionist` | Sara Verdi |
| Atleta demo | `atleta@atleta.atleta` | `atleta` | Abb. attivo, cert valido, PR/messaggi/notifiche |
| Giovanni Ferrari | `giovanni.ferrari@example.com` | `atleta` | Abb. attivo, cert valido; ha note interne |
| Alessia Colombo | `alessia.colombo@example.com` | `atleta` | Abb. scaduto (status=active, expires nel passato), cert scaduto |
| Marco Ricci | `marco.ricci@example.com` | `atleta` | Abb. attivo, cert in scadenza a 20gg; ha note interne |
| Federica Esposito | `federica.esposito@example.com` | `atleta` | Abb. attivo, cert valido |
| Davide Martini | `davide.martini@example.com` | `atleta` | Abb. attivo, cert mancante (null) |
| Gestore pilota | `gestore@iron-gym.test` | `changeme` | PilotSeeder; solo se eseguito |

### Seeder da eseguire (nell'ordine corretto, su DB già inizializzato)

```bash
# Ambiente dev — ordine di esecuzione per il set demo completo:
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=ExerciseSeeder
php artisan db:seed --class=ExerciseDescriptionSeeder
php artisan db:seed --class=PlateInventorySeeder
php artisan db:seed --class=DumbbellInventorySeeder
# OpeningHoursSeeder NON è idempotente (usa truncate) — ok solo su DB vuoto
php artisan db:seed --class=CommunicationTemplateSeeder
php artisan db:seed --class=DemoSeeder
php artisan db:seed --class=DemoTemplatesSeeder
php artisan db:seed --class=TrainingHistorySeeder
php artisan db:seed --class=ActiveMesocycleSeeder
php artisan db:seed --class=ProgressDemoSeeder
php artisan db:seed --class=GroupClassSeeder
php artisan db:seed --class=BookingDemoSeeder
php artisan db:seed --class=R09R31DemoSeeder
php artisan db:seed --class=PilotSeeder    # attiva feature flags
```

### Dati da aggiungere per coprire tutti gli scenari del piano di test

1. **Corso al completo con waitlist** — `capacity=3`, 3 iscritti `confirmed` + 1 `waitlisted` (atleta demo o altri)
2. **Abbonamento a ingressi esauriti** — 1 tesserato con `max_accesses=10`, `accesses_used=10`, `accesses_remaining=0`
3. **Occorrenza tra <30 min** — non realizzabile con seeder (serve data dinamica relativa a `now()`; descrivere come crearla manualmente nel piano di test)
4. **Overlap PT+corso per trainer** — almeno 1 PT `confirmed` per Trainer 1 nello stesso slot di un corso collettivo esistente

---

## 6. Note per la Fase 1 (piano di test)

- Esiste già `docs/test-funzionali.md` che copre R09–R31 a livello di checklist. Il piano dettagliato (Fase 1) dovrà:
  - Strutturare quei test come casi formali con ID, precondizioni esatte (nome record demo), risultato atteso verificabile
  - Aggiungere i gap non coperti: OpeningHoursManager, DumbbellInventory, finestre temporali precise, flussi multi-attore
  - Coprire i permessi negativi sistematicamente (tabella persona × azione)
  - Descrivere come forzare le condizioni temporali senza modificare il codice (es. usare occorrenze con date calcolate dal seeder)
- Il piano deve indicare per ogni caso quale record demo usare per nome (es. "usa l'occorrenza Circuit Training del+1 da BookingDemoSeeder")
