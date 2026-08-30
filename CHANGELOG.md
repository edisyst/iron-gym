# Changelog — iron-gym

---

## SET01 Fase 3 — Verifica finale e chiusura (2026-08-30)

### Correzioni CLAUDE.md

- `financial_reports` e `periodization_engine`: gruppo corretto da "Moduli" a "Sistema" (allineamento a `config/features.php`).
- `outbound_notifications`: platea corretta da "Job di sistema" a "Tutta la palestra (flag globale)".

### Fix Pint

- `app/Services/ManualRenderer.php`: `concat_space`, `unary_operator_spaces`, `not_operator_with_successor_space`.
- `app/Livewire/Backoffice/Settings/ManualViewer.php`: `binary_operator_spaces`.

### component-map.md aggiornato

- Route backoffice aggiunte: `members.expiry`, `checkin`, `settings.opening-hours`, `subscriptions.export`, `members.export`.
- Componenti aggiunti: `Access\QuickCheckin`, `Members\ExpiryDashboard`, `Calendar\ClassScheduleManager`, `Calendar\GroupClassCatalog`, `Settings\SettingsHub`, `Settings\FeatureFlagManager`, `Settings\ManualViewer`, `Settings\OpeningHoursManager`.
- `PtBookingObserver`: descrizione corretta (nessuna notifica inviata).
- Comandi aggiunti: `classes:generate-occurrences`, `classes:send-reminders`.
- Seeder aggiunti: `GroupClassSeeder`, `SettingsFlagSeeder`, `FeedbackDemoSeeder`, `FunctionalTestSeeder`.
- `OpeningHoursSeeder`: riga deduplicata.

### Scostamenti documentati (set01-chiusura.md)

- S-01: sezione 08 senza voce sidebar diretta.
- S-02: sezione 14 (report finanziari) senza voce sidebar in adminlte.php.
- S-03: "Feedback utenti" in menu senza sezione manuale.
- S-04: sezione 12 cita `/backoffice/messages` che non esiste; la route e' per-atleta.
- S-05: `Admin/FeatureFlagManager.php` dead code (file non rimosso dopo migrazione a Settings).

**Suite:** 506 test (500 pass / 6 skipped). PHPStan: 0 errori. Pint: conforme.

---

## SET01 Step 4 — Manuale sezioni 7-16 + SECTION_FLAGS (2026-08-30)

### Sezioni Markdown aggiunte (resources/docs/manual/)

- `07-schede-mesocicli.md`: template e mesocicli — builder, duplica, assegnazione, stati, filtri.
- `08-progressione-volume.md`: volume landmarks (MEV/MAV/MRV), progressione automatica, deload; flag `periodization_engine`.
- `09-calendario-disponibilita.md`: slot ricorrenti trainer, override puntuali, flusso prenotazioni PT.
- `10-prenotazioni-pt.md`: stati prenotazione, conferma/annulla/ripristina, filtri, report completate.
- `11-corsi-collettivi.md`: struttura GroupClass/ClassSchedule/ClassOccurrence, flusso creazione, lista d'attesa, finestre prenotazione; gated `group_classes`.
- `12-comunicazione-campagne.md`: campagne massa email/SMS, messaggistica one-to-one trainer-atleta, coda asincrona.
- `13-report-allenamento.md`: panoramica atleti, drilldown sessioni, filtri periodo e stato mesociclo.
- `14-report-finanziari.md`: KPI ricavi, occupazione PT, sessioni completate, cache Redis; gated `financial_reports`.
- `15-inventario-dischi.md`: dischi e manubri, edit inline, PlateLoadoutCalculator.
- `16-impostazioni-sistema.md`: tabella flag per gruppo (Moduli/Sessione atleta/Sistema), procedura toggle, avvertenze.

### SECTION_FLAGS

`ManualViewer::SECTION_FLAGS` popolato con due associazioni slug → flag:
- `11-corsi-collettivi` → `group_classes`
- `14-report-finanziari` → `financial_reports`

Badge ON/OFF appaiono nella sidebar del manuale accanto ai titoli delle sezioni gated.

**Suite:** 506 test (500 pass / 6 skipped). PHPStan: 0 errori. Pint: conforme. SET01 chiuso.

---

## SET01 Step 3 — Manualistica backoffice (2026-08-29)

### Infrastruttura

- `app/Services/ManualRenderer.php`: service che scopre file `.md` in `resources/docs/manual/`, ordina per nome, estrae titoli H1, renderizza con `Str::markdown()`, cache per mtime (`manual.{slug}.{mtime}`, TTL 1h). Slug validato come chiave di array — mai concatenato a path (path-traversal safe).
- `app/Livewire/Backoffice/Settings/ManualViewer.php`: componente Livewire embedded in tab "Manuale" di SettingsHub. `selectSection(slug)` con validazione slug. `SECTION_FLAGS` vuoto (popolato in Step 4).
- `resources/views/livewire/backoffice/settings/manual-viewer.blade.php`: sidebar sezioni con ricerca Alpine (`x-show` su `title.includes`), badge flag ON/OFF condizionale, contenuto renderizzato con stili `.manual-content` scoped.
- `resources/views/livewire/backoffice/settings/settings-hub.blade.php`: tab "Manuale" aggiornato con `@livewire('backoffice.settings.manual-viewer')`.

### Sezioni Markdown (resources/docs/manual/)

- `01-dashboard.md`: contatori, widget scadenze, flusso operativo, errori comuni.
- `02-tesserati.md`: registrazione, modifica, note interne, filtro certificati, export CSV.
- `03-abbonamenti.md`: creazione, rinnovo rapido, filtri, sospensione/riattivazione (solo gestore), export CSV.
- `04-accessi-checkin.md`: flusso check-in, 3 controlli (cert/abbonamento/ingressi), cronologia odierna, storico completo.
- `05-scadenze.md`: due tabelle (cert + abbonamenti), finestra temporale, ricerca, widget dashboard, flusso operativo.
- `06-esercizi.md`: filtri (nome/muscolo/meccanica/livello/attrezzatura), vincolo XOR pattern, soft-delete, cache tag.

### Documentazione

- `docs/manual-howto.md`: istruzioni per aggiungere, rinominare o rimuovere sezioni; sezione su SECTION_FLAGS badge.

### Fix

- `app/Livewire/Backoffice/Access/QuickCheckin.php` non toccato.
- `app/Livewire/Backoffice/Settings/OpeningHoursManager.php`: `orderByRaw('MONTH(specific_date), DAY(specific_date)')` → `orderBy('specific_date')`. Fix SQL MySQL-specifico non portabile in SQLite (già incluso in FunctionalPlanGapTest).

### Test

- `tests/Feature/ManualViewerTest.php` (11 test): gestore → 200; receptionist/trainer/atleta → 403; slug path-traversal e slug inesistente → false da `slugExists`; tutte le sezioni renderizzano senza eccezioni; mount con prima sezione attiva; `selectSection` valido; `selectSection` con slug inesistente non modifica `currentSlug`.

**Suite:** 506 test (500 pass / 6 skipped). PHPStan: 0 errori. Pint: conforme.

---

## Piano test funzionale R09+ — esecuzione automatizzata (2026-08-29)

### Copertura automatizzata

84 casi del piano `docs/testing/r09-plus-functional-test-plan.md` mappati alla suite esistente (484 test). Gap identificati e colmati:

**Bug trovato e fixato:**
- `OpeningHoursManager::render()`: `MONTH(specific_date), DAY(specific_date)` → SQL MySQL-specifico non compatibile SQLite in test. Fix: `->orderBy('specific_date')`. Comportamento identico in produzione (MySQL), test ora portabili.

**Nuovi test (FunctionalPlanGapTest — 11 test):**
- TC-OPH-001..007: `OpeningHoursManager` — visualizzazione slot ricorrenti, aggiunta, modifica inline, eliminazione, eccezione chiuso, validazione `end_time > start_time`, permesso negato atleta.
- TC-CLS-018: atleta → `/backoffice/group-classes` → 403.
- TC-CLS-019: receptionist → `deleteClass()` → 403.
- TC-NOT-006: gestore e trainer → `/athlete/notifications` → 403.

**Suite:** 495 test (489 pass / 6 skipped). PHPStan: 0 errori. Pint: conforme.

---

## SET01 Step 2C — Gating flag sessione atleta + navigazione filtrata (2026-08-29)

### Navigazione filtrata

- `athlete.blade.php`: sidebar "Progressi" href ora condizionale — `weekly_volume` attivo → `/athlete/volume`, altrimenti `/athlete/measurements`. Risolve link morto con flag spento.
- `athlete.blade.php`: toast PR (`pr-achieved`) wrappato in `@feature('personal_records')/@endfeature`.
- `dashboard.blade.php`: link chevron "Ultimo allenamento" → recap wrappato in `@feature('session_recap')/@endfeature`.

### WorkoutSession — PersonalRecordDetector sempre attivo

`WorkoutSession::completeSet()` e `quickLog()`: `PersonalRecordDetector::check()` ora eseguito sempre (anche con `personal_records` off). Il dispatch dell'evento `pr-achieved` (toast) rimane condizionale sul flag. I PR vengono scritti in DB anche durante test pilota con flag spento.

### Flag plate_calculator

Aggiunto a `config/features.php` managed_flags (gruppo "Sessione atleta") e `AppServiceProvider` (`Feature::define`). Nessun gating point lato atleta (rimosso in UX01). Toggle esposto in FeatureFlagManager per uso futuro. `PlateInventoryManager` backoffice accessibile a prescindere.

### Test

- `SessionFlagGatingTest` (10 test): `readiness_check` off abort 403; `exercise_substitution` off abort 403; `personal_records` off → route 403, toast assente, PR salvato in DB; `weekly_volume` off → route 403, sidebar href fallback; `session_recap` off → route 403, link recap assente in dashboard.

### Fix preesistente

- `FeedbackDemoSeeder.php`: fix Pint `concat_space` / `binary_operator_spaces`.

**Suite:** 484 test (478 pass / 6 skipped). PHPStan: 0 errori. Pint: conforme.

---

## SET01 Step 2B — Gating completo messaging e pt_bookings (2026-08-29)

**`messaging` — punto mancante:**
- Alpine store `messages.init()` avvolge la `fetch('/athlete/messages-unread-count')` in `@feature('messaging')/@endfeature`: con il flag spento il browser non emette la chiamata HTTP a ogni pagina.
- Link "Apri messaggi" nel dashboard empty-state (sezione "Nessuna scheda attiva") aggiunto a `@feature('messaging')`.

**`pt_bookings` — gating completo:**
- Nuovo gate `view-athlete-bookings`: true se `pt_bookings` OR `group_classes` attivo.
- Route `/athlete/bookings` → middleware `can:view-athlete-bookings` (403 se entrambi i flag spenti).
- `Booking::mount()`: se `pt_bookings` off → `activeTab='classes'` (evita tab PT attivo ma invisibile).
- `booking.blade.php`: tab button "Sessione PT" e intero contenuto tab PT in `@feature('pt_bookings')`.
- Bottom-nav atleta: link "Prenota" in `@can('view-athlete-bookings')/@endcan`.
- `TrainerAvailabilityObserver` lasciato attivo: ricalcola slot (consistenza dati, non invio verso atleti).
- Backoffice `BookingList` non toccato: trainer continua a gestire l'agenda PT esistente.

**Matrice pt_bookings × group_classes:**
| pt_bookings | group_classes | Pagina Prenota |
|---|---|---|
| ON | ON | Entrambi i tab visibili |
| ON | OFF | Solo tab PT |
| OFF | ON | Solo tab Corsi; mount forza activeTab='classes' |
| OFF | OFF | 403; link Prenota assente |

**Test:** 8 nuovi test in `ModuleFlagGatingTest` (fetch assente, link assenti, matrice 4 casi, activeTab). Suite: 474 test (468 pass / 6 skipped). PHPStan 0 errori. Pint conforme.

---

## SET01 Step 2 — Fix post-release (2026-08-29)

**Log::warning nel kill switch:** ogni job `outbound_notifications` ora emette `Log::warning('[outbound_notifications] invio soppresso da interruttore', ['job' => ...])` prima di ritornare. Facilita il debug in produzione. Test aggiunto (`OutboundNotificationsKillSwitchTest` +1 → 8 test). Suite: 466 test (460 pass / 6 skipped).

**Fix punto 4 SET01 Step 1 — SettingsHub embed:** il tab "Funzioni" ora embeds `@livewire('backoffice.settings.feature-flag-manager')` direttamente; rimosso il link "Gestisci funzioni" che richiedeva un click extra.

**Fix punto 6 SET01 Step 1 — raggruppamento flag:** `financial_reports` e `periodization_engine` spostati dal gruppo "Moduli" a "Sistema" in `config/features.php` e `FeatureFlagManager`. Sidebar: "Feedback utenti" spostato da IMPOSTAZIONI a COMUNICAZIONE (posizione corretta accanto a Campagne).

**Fix PHPStan — factory mancanti:** create `AthleteVolumeLandmarkFactory` e `SessionExerciseFeedbackFactory`; `database/factories` aggiunto ai path in `phpstan.neon`. PHPStan livello 6: 0 errori.

**Docs:** corretto `PtBookingObserver` in CLAUDE.md — l'observer invalida cache slot trainer e tag KPI (non invia notifiche come documentato erroneamente).

---

## SET01 Step 2 — Gating completo 9 nuovi flag (2026-08-29)

**Obiettivo:** chiudere GAP-03 e aggiungere kill switch a tutti i livelli per 9 nuovi flag: `messaging`, `pt_bookings`, `outbound_notifications`, `in_app_feedback`, `readiness_check`, `exercise_substitution`, `session_recap`, `personal_records`, `weekly_volume`.

**GAP-03 chiuso:** route `/reports/manager` e `/reports/financial` protette da middleware `can:view-financial-reports` (defense-in-depth oltre al gate nei componenti Livewire).

**Nuovi flag — livelli di applicazione:**
- **Route middleware:** `can:view-messaging` su `/messages`, `can:view-session-recap` su `/session/recap`, `can:view-personal-records` su `/records`, `can:view-weekly-volume` su `/volume`.
- **Livewire (PHP):** `WorkoutSession` — readiness_check gate su mount/skip/submit; exercise_substitution su openSubstitutionModal/confirmSubstitution; personal_records su rilevamento PR. `SessionFeedbackForm` — redirect condizionale session_recap. `Profile`, `Dashboard`, `Booking` — pt_bookings e personal_records gated sulle query.
- **View Blade:** `@feature` su link sidenav messaggi, link sidenav record, tab profilo PT/record/messaggi, bottone sostituzione esercizio, feedback in-app in entrambi i layout.
- **Job kill switch:** `outbound_notifications_enabled=false` → i 7 job di notifica (SendSubscriptionExpiryReminders, SendMedicalCertExpiryReminders, SendSessionReminders, SendClassReminders, NotifyClassCancellation, NotifyWaitlistPromotion, SendCampaignMessages) ritornano subito senza inviare.

**FeatureFlagManager:** vista ristrutturata con una card per gruppo (Moduli / Sessione atleta / Sistema). Fix bug chiavi stringa perse da `->groupBy()` (sostituito con loop PHP nativo).

**config/features.php:** 13 flag con campo `group`. `SettingsFlagSeeder` aggiornato.

**Test:** 17 nuovi test — `FeatureFlagGatingTest` (10: flag-off/on per messaging, pt_bookings, session_recap, personal_records, weekly_volume, readiness_check), `OutboundNotificationsKillSwitchTest` (7: kill switch per ogni job). Aggiornati 5 test esistenti con `Feature::activate()` in beforeEach. Suite: 465 test (459 pass / 6 skipped). PHPStan 0 errori. Pint conforme.

---

## SET01 Step 1 — Sezione Impostazioni e unificazione feature flag (2026-08-29)

**Obiettivo:** creare la sezione Impostazioni riservata al gestore, portarci la gestione dei flag, e chiudere DIFETTO-A e DIFETTO-B rilevati nell'assessment.

**DIFETTO-A — Pattern uniforme per tutti i flag:**
Tutti e quattro i flag gestibili da UI (`periodization_engine`, `push_notifications`, `group_classes`, `financial_reports`) usano ora il pattern `Setting::bool(key, default) && <condizione_scope>`. Il toggle scrive sempre su `settings` + `Feature::purge()`, mai `activateForEveryone/deactivateForEveryone`. Utenti che non avevano mai risolto un flag ora lo rileggono correttamente al prossimo accesso.

**DIFETTO-B — UI mostra stato interruttore gym-wide:**
`FeatureFlagManager::render()` non chiama più `Feature::active($flag)` (risolto sull'utente corrente). Legge direttamente `Setting::bool()`, indipendente dal ruolo del gestore. Corregge la visualizzazione di `push_notifications` (definer scope: atleta/trainer) che il gestore vedeva sempre spento.

**Nuovi componenti:**
- `Backoffice\Settings\SettingsHub` (`/backoffice/settings`) — hub con tab Funzioni e Manuale (segnaposto)
- `Backoffice\Settings\FeatureFlagManager` (`/backoffice/settings/feature-flags`) — spostato da `Admin`, aggiornato

**Route e redirect:**
- Nuovo gruppo `can:access-admin-section` su `/backoffice/settings/*`
- Redirect 301 da `/backoffice/admin/feature-flags` → `/backoffice/settings/feature-flags`

**Sidebar:** header ADMIN soppresso, voci accorpate in IMPOSTAZIONI (Funzioni, Inventario Dischi, Feedback utenti).

**SettingsFlagSeeder:** scrive le 4 chiavi `settings` con valori pilota; idempotente (`Setting::write`); escluso da prod; registrato in `DatabaseSeeder`. `PilotSeeder::seedFeatureFlags` delega a questo seeder.

**config/features.php:** aggiunta chiave `managed_flags` con label, description, platea, settings_key e default per ogni flag. Fonte unica usata da `FeatureFlagManager` e dalla manualistica.

**Test:** 11 nuovi test in `SettingsFeatureFlagsTest` (accesso 4 ruoli, redirect 301, DIFETTO-A per 4 flag, DIFETTO-B push_notifications). Import aggiornato in `GlobalFeatureFlagTest`. Suite: 448 test (442 pass / 6 skipped). PHPStan 0 errori. Pint conforme.

---

## FIX02 — Idempotenza seeder e overlap PT+corso atleta (2026-08-27)

**F-01/F-04 — `OpeningHoursSeeder` idempotente** (`9b906a8`):
- Sostituito `truncate()+create()` con `firstOrCreate` su chiave naturale:
  slot settimanali su `(day_of_week, specific_date=null)`, festività/vigilie su
  `(specific_date, is_annual=true)`. Rieseguibile N volte senza perdita dati.
- F-04 risolto di conseguenza: il seeder e' ora sicuro in tutti gli ambienti,
  niente da spostare in `DatabaseSeeder`.

**F-02 — `ClassBookingService::enroll` controlla overlap PT+corso** (`4ecc742`):
- Aggiunto check `PtBooking confirmed` stesso giorno/orario prima di iscrivere
  il membro al corso collettivo. Eccezione: `"Hai già una sessione PT confermata
  in questo orario."` Previene doppia prenotazione PT+corso sullo stesso slot.

---

## DOC02 — Piano test funzionali R09+ e seeder demo (2026-08-27)

Attivita' di qualita' read-only: nessuna modifica al codice applicativo.

**Assessment:** `docs/reviews/r09-plus-test-assessment.md` — perimetro reale R09→FIX01
(158 commit, 182 file, 226 nuovi test automatici). 6 findings documentati, non corretti.

**Piano di test manuale:** `docs/testing/r09-plus-functional-test-plan.md` — 109 casi
in 14 aree funzionali (CLS, SCH, CAT, GEN, NOT, PRF, EXP, CHK, SUB, MBR, DBC, FLG, OPH, REG).
Ogni caso: persona, account, precondizioni, step, risultato atteso verificabile in UI.

**FunctionalTestSeeder** — 4 scenari demo idempotenti:
- Yoga Full (occorrenza al completo + waitlist per TC-CLS-009/012)
- Carlo Accessi — `carlo.accessi@functional-test.demo` / `demo1234` (TC-CHK-004)
- Overlap trainer PT+corso (REG-003)
- Occorrenza passata non completata (TC-CLS-015/016)

**Divergenze codice/doc corrette in CLAUDE.md:**
- Aggiunto `OpeningHoursManager` (`/backoffice/settings/opening-hours`) alla sezione componenti
- Aggiunto warning F-01/F-04 su `OpeningHoursSeeder::truncate` e `DatabaseSeeder`

**Findings aperti (non corretti — da pianificare):**
- F-01: `OpeningHoursSeeder:13` usa `truncate()` — non idempotente
- F-02: `ClassBookingService::enroll` non controlla overlap atleta PT+corso
- F-04: `DatabaseSeeder` chiama `OpeningHoursSeeder` fuori da `isLocal()`

---

## FIX01 — Feature flag globali e autorizzazioni mesocicli (2026-08-26)

Nato dal test funzionale R09-R14: quasi tutti i sintomi riportati (corsi
invisibili all'atleta, catalogo senza link, promemoria non testabili) avevano
un'unica causa.

**Feature flag globale `group_classes`:**
- Causa: `Feature::activateForEveryone()` aggiorna solo le righe gia' presenti in
  `features`. Un utente che non aveva mai risolto il flag ricadeva sul definer
  → `config` → `env` → `false`. Il toggle da backoffice era quindi inefficace,
  e le righe memorizzate a `false` vincevano comunque sul definer.
- Nuova tabella `settings` (key/value JSON) come sorgente di verita' per i flag
  validi per l'intera palestra; `config/features.php` resta il default iniziale.
- `Setting::bool()` / `Setting::write()` con cache invalidata in scrittura.
- Definer `group_classes` legge da `settings`; `FeatureFlagManager` scrive in
  `settings` ed esegue `Feature::purge()` cosi' il nuovo valore vale per tutti.
- `FeatureFlagManager::confirmToggle()`: aggiunti guard `role:gestore` e
  whitelist dei flag gestiti (prima nessun controllo di ruolo).
- Migration cancella le righe stale `group_classes` da `features`.
- Gli altri tre flag (`periodization_engine`, `push_notifications`,
  `financial_reports`) restano per-utente, risolti per ruolo.

**Fix query `Athlete\Dashboard`:** la card "prossimi corsi" faceva join fra
`class_bookings` e `class_occurrences` senza qualificare `status` e `member_id`
→ MySQL 1052 "Column 'status' in where clause is ambiguous", pagina `/athlete`
in errore 500 con il flag attivo. Colonne ora qualificate, `whereHas` ridondante
rimosso. Stessa classe di bug gia' corretta in R21.

**Autorizzazioni mesocicli:**
- `MesocycleList`: il trainer vede solo i propri mesocicli (prima vedeva quelli
  di tutti, con pulsanti che portavano a un 403).
- `MesocycleDetail`: `applyProgression()` e `forceDeload()` verificavano solo il
  ruolo, non la proprieta'. Aggiunto `authorizeOwnership()` — `mount()` da solo
  non basta, le action Livewire arrivano come richieste indipendenti.

**Dati e comandi:**
- `GroupClassSeeder` registrato in `DatabaseSeeder` prima di `BookingDemoSeeder`:
  creava le uniche `ClassSchedule` del progetto ma non veniva mai eseguito
  (`class_schedules` era vuota, pagina palinsesto senza dati).
- Nuovo comando `classes:send-reminders [--sync]` che dispatcha
  `SendClassReminders` (esisteva solo come job schedulato, non lanciabile a mano).
- Alias route `/athlete/dashboard` → redirect a `athlete.dashboard`.
- Link "Volume landmarks" nell'header di `AthleteProfile` backoffice: la route
  esisteva senza alcun link nell'interfaccia.

**Nota su R12:** `applyProgression` funziona correttamente. La progressione
MEV→MRV agisce sul numero di **set** (`planned_sets_count`), non sui carichi:
verificato su dati reali, `exercise_sets` 23 → 37 con `action=progressed`. I
`planned_weight_kg` dei set esistenti restano invariati per design.

**Dati demo atleti (`R09R31DemoSeeder`):**
- `atleta@atleta.atleta` riceve una sessione PT **pending** futura: prima non ne
  aveva, quindi l'annullamento dalla dashboard (R14) non era provabile.
- `alessia.colombo@example.com` diventa il caso "abbonamento scaduto"
  deterministico — `status=active` con `expires_at` nel passato, piu' certificato
  medico scaduto. Copre il badge "Scaduto" (R13) e il blocco dei prerequisiti di
  iscrizione ai corsi (R09 Step 2).
- Nota: i due casi non possono coincidere sullo stesso atleta. Il profilo
  distingue un abbonamento lapsed da uno assente filtrando su `status=active` e
  calcolando il badge da `expires_at`; un atleta in quello stato fallisce
  `Member::activeSubscription()` e non puo' iscriversi ai corsi.
- Badge "In attesa" sulle sessioni PT pending nella dashboard atleta: la card
  mostrava data e trainer ma non lo stato.

**Test (16):** `GlobalFeatureFlagTest` (6), `MesocycleOwnershipTest` (5),
`AthleteDashboardClassCardTest` (3), `AthleteDashboardPendingPtBadgeTest` (2).

Suite: 429 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R31 — Statistiche PT in ManagerDashboard (2026-08-24)

**`ManagerDashboard` — sezione "Sessioni PT completate per trainer":**
- Query `pt_bookings JOIN users` filtrata per `booked_date` nel periodo selezionato e `status = completed`
- Raggruppamento per `trainer_id`, ordinamento decrescente per conteggio
- Tabella con colonne: Trainer, Sessioni completate
- Messaggio "Nessuna sessione PT nel periodo" se risultato vuoto

**Test (3):** tabella mostra conteggio corretto per trainer; sessioni non-completed escluse; sessioni fuori range di date escluse.

Suite: 413 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R30 — Export CSV tesserati (2026-08-24)

**Route `GET /backoffice/members/export` (solo gestore):**
- Rispetta i filtri correnti tramite query params `?search=X&certFilter=Y` (stessi valori di `MemberList`)
- Output: CSV UTF-8 con BOM, separatore `;`
- Colonne: Cognome, Nome, Email, Telefono, Abbonamento, Scadenza abb., Cert. medico, Attivo
- Nome file: `tesserati-YYYY-MM-DD.csv`

**`MemberList` view — bottone "Esporta CSV":**
- Bottone `btn-outline-secondary` con icona `fa-file-csv` visibile solo a gestore (`@role('gestore')`)
- Link `<a href>` che passa i filtri attivi correnti (`$search`, `$certFilter`)

**Test (4):** gestore scarica CSV (200 + Content-Type); CSV contiene cognome e nome; receptionist ottiene 403; filtro `certFilter=missing` esclude tesserati con cert valido.

Suite: 410 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R29 — Export CSV abbonamenti (2026-08-24)

**Route `GET /backoffice/subscriptions/export` (solo gestore):**
- Rispetta il filtro corrente tramite query param `?filter=all|active|expired|expiring|suspended`
- Output: CSV UTF-8 con BOM (compatibile Excel), separatore `;`
- Colonne: Cognome, Nome, Email, Piano, Inizio, Scadenza, Stato
- Nome file: `abbonamenti-YYYY-MM-DD.csv`

**`SubscriptionList` view — bottone "Esporta CSV":**
- Bottone `btn-outline-secondary` con icona `fa-file-csv` visibile solo a gestore (`@role('gestore')`)
- Link `<a href>` che passa il filtro attivo corrente; nessuna azione Livewire necessaria

**Test (4):** gestore scarica CSV (200 + Content-Type text/csv); CSV contiene nome tesserato e piano; receptionist ottiene 403; filtro `active` esclude abbonamenti scaduti dal CSV.

Suite: 406 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R28 — Note interne sul tesserato (2026-08-24)

**`MemberForm` (già completo):** campo `notes` textarea presente in property, `mount()`, `rules()` e view — nessuna modifica necessaria.

**`MemberList` — indicatore visivo note:**
- Icona `fa-sticky-note text-warning` accanto al nome del tesserato quando `notes` non è null/vuoto
- Tooltip nativo (`title`) con anteprima fino a 100 caratteri della nota
- `aria-label="Note interne presenti"` per accessibilità
- Nessuna icona mostrata per tesserati senza note

**Test (4):** form salva note interne; icona mostrata in lista con note presenti; icona assente senza note; form pre-carica note esistenti in modifica.

Suite: 402 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R27 — Sospensione abbonamento (2026-08-24)

**`Backoffice\Subscriptions\SubscriptionList` — azioni sospendi/riattiva:**
- Metodo `suspend(int $id)`: solo gestore; richiede `status = active`; imposta `suspended`
- Metodo `reactivate(int $id)`: solo gestore; richiede `status = suspended`; imposta `active`
- Bottone `btn-outline-warning` con icona `fa-pause` e `wire:confirm` per abbonamenti attivi
- Bottone `btn-outline-primary` con icona `fa-play` e `wire:confirm` per abbonamenti sospesi
- Guard: `abort_unless(gestore, 403)` + `abort_if(status errato, 422)`
- Filtro "Sospesi" aggiunto al select (`filter = 'suspended'`)
- Query `render()` estesa con `->when('suspended', where status = suspended)`

**Test (5):** gestore sospende attivo; gestore riattiva sospeso; receptionist non può sospendere (status invariato); filtro sospesi mostra badge Sospeso; doppia sospensione non cambia status.

Suite: 398 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R26 — Tab "Accessi" nel profilo atleta (2026-08-24)

**`Athlete\Profile` — tab "Accessi":**
- Nuovo tab tra "Corsi" (o "Sessioni" se flag off) e "Messaggi"
- Mostra gli ultimi 5 ingressi ordinati per `checked_in_at DESC`
- Ogni riga: data, ora, nome piano abbonamento (se presente), badge "Entrata" verde
- Stato vuoto "Nessun accesso registrato"
- Isolamento: query filtrata su `member_id` del tesserato collegato all'atleta loggato

**`Profile::render()`:** aggiunto `$recentAccessLogs` con `AccessLog::with('subscription.plan')->where('member_id', $member->id)->orderByDesc('checked_in_at')->limit(5)`.

**Test (5):** tab visibile; ingresso con badge Entrata; piano abbonamento mostrato; stato vuoto; accessi di altri tesserati esclusi.

Suite: 393 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R25 — Rinnovo abbonamento rapido (2026-08-24)

**`Backoffice\Subscriptions\SubscriptionList` — bottone "Rinnova":**
- Nuova colonna azioni in tabella; bottone `btn-outline-success` con icona `fa-redo` e `aria-label`
- Visibile solo con permesso `manage-subscriptions` (gestore e receptionist); nascosto a trainer
- Link a `backoffice.subscriptions.create` con query params `?member_id=X&plan_id=Y`

**`Backoffice\Subscriptions\SubscriptionForm` — pre-popolamento da query string:**
- `mount()` legge `request()->query('member_id')` e `request()->query('plan_id')`
- Se `plan_id` presente, chiama `updatedPlanId()` → calcola `expires_at` automaticamente a partire da oggi
- Comportamento invariato se i params non sono presenti (form vuoto normale)

**Test (5):** bottone Rinnova visibile a gestore; nascosto a trainer; `member_id` pre-popolato; `plan_id` + `expires_at` calcolata; nuovo abbonamento creato con save().

Suite: 388 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R24 — Check-in Rapido backoffice (2026-08-24)

**Nuovo componente `Backoffice\Access\QuickCheckin` (`/backoffice/checkin`):**
- Ricerca live tesserato per nome/cognome/email (min 2 caratteri, max 8 risultati)
- Card risultato: nome, email, badge piano abbonamento, badge "Cert. scaduto" se necessario
- `selectMember()` → popola campo ricerca con nome selezionato, abilita bottone
- `registerAccess()` → verifica cert valido + abbonamento attivo + accessi disponibili; incrementa `accesses_used`, decrementa `accesses_remaining` se limitato; crea `AccessLog`
- Messaggi di errore distinti: cert scaduto/mancante, nessun abbonamento attivo, accessi esauriti
- Messaggio di successo con nome tesserato; reset automatico del form
- Cronologia ultimi 10 accessi odierni in tabella affiancata (tesserato, piano, ora, operatore)
- Route `backoffice.checkin` accessibile a gestore e receptionist; trainer bloccato (403)
- Voce "Check-in" (icona `fa-sign-in-alt`) aggiunta in sidebar sopra "Accessi"

**Test (7):** gestore accede; receptionist accede; trainer bloccato (403); accesso registrato con cert+abb validi; rifiuto cert scaduto; rifiuto nessun abbonamento; cronologia odierna visibile.

Suite: 383 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R23 — Widget scadenze nella Dashboard backoffice (2026-08-24)

**`Backoffice\Dashboard` — widget scadenze imminenti:**
- Card `card-outline card-warning` "Scadenze imminenti" mostrata condizionalmente quando `certExpiring30Count > 0 OR subExpiring7Count > 0`
- Due badge contatori: certificati medici in scadenza entro 30 giorni (giallo) e abbonamenti in scadenza entro 7 giorni (rosso)
- Bottone "Vai al pannello →" link diretto a `backoffice.members.expiry`
- Link dei two small-box esistenti (Abbonamenti in scadenza, Certificati scaduti) aggiornati per puntare a `members.expiry` invece di pagine generiche
- Nuove proprietà pubbliche: `$certExpiring30Count` (cert futuri nei prossimi 30gg, esclusi già scaduti) e `$subExpiring7Count` (abbonamenti attivi che scadono entro 7gg via `Subscription::expiringSoon(7)`)

**Test (4):** widget visibile con cert in scadenza; widget assente senza scadenze; `certExpiring30Count` conta solo futuri entro finestra (esclude scaduti e oltre finestra); `subExpiring7Count` esclude abbonamenti con scadenza oltre 7 giorni.

Suite: 376 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R22 — Pannello Scadenze backoffice (2026-08-24)

**Nuovo componente `Backoffice\Members\ExpiryDashboard` (`/backoffice/members/expiry`):**
- Tabella **certificati medici in scadenza** entro N giorni (default 30): nome, email, data scadenza con badge rosso/giallo, giorni rimanenti, piano abbonamento attivo, link modifica tesserato
- Tabella **abbonamenti in scadenza** entro N giorni (default 7): nome, email, piano, data scadenza con badge, giorni rimanenti, link rinnovo
- Filtri live: campo ricerca (nome/email), select finestra temporale certificati (7/14/30/60 gg), select finestra abbonamenti (3/7/14/30 gg)
- Badge contatore rosso/verde su intestazione di ogni sezione
- Stato vuoto per entrambe le sezioni quando non ci sono scadenze
- Route `backoffice.members.expiry` accessibile a gestore e receptionist (middleware `role:gestore|trainer|receptionist` ereditato + atleta bloccato via HTTP 403)
- Voce "Scadenze" (icona `fa-exclamation-triangle`) aggiunta alla sidebar sotto "Abbonamenti"

**Test (7):** gestore accede; receptionist accede; atleta bloccato (403); cert in scadenza visibile; cert oltre finestra esclusa; abbonamento in scadenza visibile; filtro certDays esclude oltre finestra ridotta.

Suite: 372 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R21 — Corsi collettivi nel profilo atleta (2026-08-24)

**`Athlete\Profile` — tab "Corsi":**
- Tab visibile solo se feature flag `group_classes` attivo (Pennant)
- Sezione "Prossimi corsi": prenotazioni `confirmed`/`waitlisted` su occorrenze future `planned`; badge Confermato (verde) / Lista d'attesa (giallo); data+ora+nome corso
- Sezione "Storico corsi": prenotazioni passate (tutti gli status tranne waitlisted); badge Confermato/Annullato/Assente
- JOIN su `class_occurrences` per ordinamento per data; colonne qualificate `class_bookings.status` / `class_bookings.member_id` per evitare ambiguità SQL
- Limite 5 record per sezione; stato vuoto per entrambe le sezioni
- Link "Prenota un corso →" → `/athlete/bookings`
- Isolamento: query filtrata su `member_id` dell'atleta loggato

**`Profile::render()`:** aggiunto `$groupClassesEnabled`, `$upcomingClassBookings`, `$pastClassBookings`; query con JOIN condizionale al flag.

**Test (5):** tab visibile con flag ON; tab assente con flag OFF; prenotazione confermata futura; prenotazione waitlisted; isolamento da altri atleti.

Suite: 365 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R20 — Messaggi nel profilo atleta (2026-08-24)

**`Athlete\Profile` — tab "Messaggi":**
- Nuovo tab tra "Sessioni" e "Password"
- Badge contatore non letti sul tab (numero rosso inline)
- Mostra gli ultimi 5 messaggi (inviati o ricevuti) ordinati per `created_at DESC`
- Ogni riga: nome contatto (prefisso "Tu →" se inviato), anteprima corpo (60 char), data/ora, pallino rosso se non letto
- Sezione titolo con conteggio "N non letti" se presenti
- Link "Apri messaggi" → `/athlete/messages`; link "Vai ai messaggi →" in fondo se presenti
- Stato vuoto con CTA "Scrivi al tuo trainer →"
- Isolamento: query `sender_id = id OR receiver_id = id`

**`Profile::render()`:** aggiunto `$recentMessages` e `$unreadMessagesCount` (via `Message::unread()`).

**Test (5):** messaggio ricevuto visibile con mittente; messaggio inviato con prefisso Tu; badge non letti; stato vuoto; messaggi tra altri utenti non inclusi.

Suite: 360 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R19 — Storico sessioni nel profilo atleta (2026-08-24)

**`Athlete\Profile` — tab "Sessioni":**
- Nuovo tab tra "Record" e "Password"
- Mostra le ultime 5 sessioni `completed`/`skipped` ordinate per `completed_at DESC`
- Ogni riga: nome sessione, data, durata in minuti (se `started_at` e `completed_at` presenti), badge Completata/Saltata
- Badge colorato: Completata=verde, Saltata=grigio; nome sessione desaturato se saltata
- Link "Vedi storico" → `/athlete/history` (`TrainingHub`)
- Stato vuoto "Nessuna sessione completata"
- Isolamento: query via `week.mesocycle.athlete_id`

**`Profile::render()`:** aggiunto `$recentSessions` con `whereHas('week.mesocycle', athlete_id)->whereIn('status', ['completed','skipped'])->orderByDesc('completed_at')->limit(5)`.

**Test (5):** sessione completata visibile; sessione saltata con badge; sessioni planned escluse; durata calcolata (75 min); sessioni di altri atleti non incluse.

Suite: 355 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R18 — Personal Record nel profilo atleta (2026-08-24)

**`Athlete\Profile` — tab "Record":**
- Nuovo tab tra "Misurazioni" e "Password"
- Mostra gli ultimi 5 PR di tipo `e1rm` ordinati per data decrescente
- Ogni riga: nome esercizio (`name_it`), e1RM in kg (formattato con 1 decimale), data
- Link "Vedi tutti" → `/athlete/records` (pagina `PersonalRecords` esistente)
- Stato vuoto con spiegazione "I PR vengono rilevati automaticamente durante le sessioni"
- "Vedi tutti i record →" in fondo se ci sono PR
- Isolamento: query filtra su `athlete_id = auth()->id()`

**`Profile::render()`:** aggiunto `$recentPrs` via `PersonalRecord::with('exercise')->where(...)->orderByDesc('achieved_at')->limit(5)->get()`.

**Fix view:** `$pr->exercise?->name` → `$pr->exercise?->name_it` (Exercise usa `name_it`).

**Test (5):** tab mostra PR con esercizio e valore; stato vuoto; limit 5 (6° non renderizzato); PR di altro atleta non incluso; link "Vedi tutti" presente.

Suite: 350 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R17 — Misurazioni corporee nel profilo atleta (2026-08-24)

**`Athlete\Profile` — tab "Misurazioni":**
- Nuovo tab tra "Sessioni PT" e "Password"
- Mostra le ultime 5 misurazioni ordinate per data decrescente
- Campi visibili per riga: data, peso (kg), BF%, vita (cm), petto (cm) — solo se valorizzati
- Note della misurazione visibili se presenti
- Link "+ Aggiungi" → `/athlete/measurements` (pagina `BodyMeasurementForm` esistente)
- Stato vuoto con link diretto alla prima registrazione
- "Vedi tutte e aggiungi →" in fondo se ci sono misurazioni
- Isolamento: query filtra su `athlete_id = auth()->id()`

**`Profile::render()`:** aggiunto `$recentMeasurements` via `BodyMeasurement::where('athlete_id', Auth::id())->orderByDesc('measured_at')->limit(5)->get()`.

**Test (5):** tab mostra misurazione con peso/BF%/vita; stato vuoto; limit 5 (6° e 7° non renderizzati); isolamento da altri atleti; note visibili.

Suite: 345 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R16 — Sessioni PT nel profilo atleta (2026-08-24)

**`Athlete\Profile` — tab "Sessioni PT":**
- Nuovo tab tra "Abbonamento" e "Password"
- **Prossime sessioni:** PT `pending`/`confirmed` da oggi in poi, ordinate per data/ora; badge status colorato (Confermata=verde, In attesa=giallo)
- **Storico PT:** ultimi 10 PT `completed`/`no_show`/`cancelled` con data decrescente; sezione visibile solo se presenti
- Stato vuoto "Nessuna sessione PT in programma" se nessuna prossima
- Isolamento: ogni query filtra su `member_id` dell'atleta autenticato

**`Profile::render()`:** carica `$upcomingPtBookings` e `$pastPtBookings` con eager load `trainer`.

**Test (5):** tab PT mostra sessione confermata; tab PT mostra sessione pending; storico PT con completed; nessuna PT di altri atleti inclusa; stato vuoto senza sessioni.

Suite: 340 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R15 — Test BookingList e CommunicationCampaign (2026-08-24)

**`BookingListTest` (7 test):**
- Gestore conferma qualsiasi prenotazione PT
- Trainer conferma solo le proprie (filtro `trainer_id` nella query)
- Altro trainer — silent no-op (query non trova record, nessun 403)
- Gestore annulla con motivo → status `cancelled`
- Altro trainer annulla → `abort_unless` → 403
- Validazione motivo annullamento: minimo 5 caratteri
- Gestore ripristina prenotazione annullata → `pending`

**`CommunicationCampaignTest` (4 test):**
- Gestore visualizza form campagna
- `send()` dispatcha `SendCampaignMessages` con `memberIds` corretti (`Bus::fake`)
- Validazione: `body` obbligatorio
- Filtro `active` esclude tesserati senza abbonamento valido

**PilotSeeder:** `group_classes` attivato per tutti con `Feature::activateForEveryone`.

Suite: 335 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R14 — Sessioni PT in dashboard atleta e test analytics (2026-08-24)

**`Athlete\Dashboard`:**
- Proprietà `upcomingPtBookings` (Collection): PT pending/confirmed da oggi, limit 3, eager load `trainer`
- View: sezione "Prossime sessioni PT" con nome trainer, data, orario, link a `/athlete/bookings`

**Fix `ManagerDashboard`:** `CONCAT→||` cross-db in query `atRiskMembers` (SQLite test compat).

**Test (10):**
- `AthleteAnalyticsTest` (4): gestore vede dati atleta; trainer con mesociclo; atleta → 403; `findOrFail` su ID inesistente lancia `ModelNotFoundException`
- `ManagerDashboardTest` (2): gestore OK; date `dateFrom`/`dateTo` inizializzate al mese corrente
- `AthleteDashboardPtBookingTest` (4): PT futura confermata visibile; PT passata non mostrata; PT cancellata non mostrata; isolamento — PT di altro atleta non incluse

Suite: 324 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R13 — Stato abbonamento nel profilo atleta e fix CONCAT (2026-08-24)

**`Athlete\Profile` — tab "Abbonamento":**
- Carica abbonamento attivo con `status=active`, `orderByDesc('expires_at')`, eager load `plan`
- Badge stato dinamico: Attivo / In scadenza (≤30gg) / Scaduto
- Mostra: nome piano, date `started_at`/`expires_at`, giorni rimanenti, `accesses_remaining` se valorizzato
- Stato vuoto se nessun abbonamento attivo

**Fix cross-db `CONCAT→||`:**
- `TrainingReport` — 2 occorrenze in `whereRaw` (nome atleta)
- `GlobalSearch` — 1 occorrenza in `whereRaw` (ricerca tesserati)
- Pattern: `DB::connection()->getDriverName() === 'sqlite' ? "a || ' ' || b" : "CONCAT(a, ' ', b)"`

**Test (14):**
- `AthleteProfileSubscriptionTest` (4): abbonamento attivo visibile; badge scadenza; nessun abbonamento → stato vuoto; abbonamento di altro atleta non mostrato
- `TrainingReportTest` (6): gestore vede tutti; trainer vede solo propri; trainer non vede altrui; drilldown gestore; drilldown trainer con mesociclo; drilldown trainer senza mesociclo → 403
- `GlobalSearchTest` (4): query < 2 caratteri → nessun risultato; trova atleta; trova trainer; trova template

Suite: 314 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R12 — Attivazione periodization engine e test Livewire (2026-08-24)

**PilotSeeder:** `Feature::activateForEveryone('financial_reports')` e `Feature::activateForEveryone('periodization_engine')` — flag attivi per default su nuove installazioni pilota.

**Test (12):**
- `MesocycleDetailTest` (6): trainer accede OK; gestore accede OK; atleta → 403; `forceDeload` marca week2 come deload; `forceDeload` sull'ultima settimana → no-op; `applyProgression` aggiorna `lastProgressionResultData`
- `VolumeLandmarkManagerTest` (6): gestore OK; trainer con mesociclo OK; trainer senza mesociclo → 403; `save()` persiste su DB; `resetToDefaults()` elimina righe custom; default caricati correttamente

Suite: 300 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R11 — Class reminder notification (2026-08-24)

**`ClassReminderNotification`** (`database` + `webpush`):
- `toArray()`: `type=class_reminder`, `occurrence_id`, messaggio "Domani hai {nome} alle {HH:mm}."
- `toWebPush()`: titolo "Corso domani", body uguale

**`SendClassReminders` job** (schedulato `dailyAt('08:00')` in `routes/console.php`):
- Query `ClassOccurrence` con `date = tomorrow` e `status = planned`
- Per ogni `confirmedBookings`, notifica `$booking->member?->user`

**Centro notifiche atleta:** aggiunta icona calendario e colore accent per `type = class_reminder`.

**Test (6):** job notifica utenti con prenotazione confermata domani; non notifica se occorrenza oggi/dopodomani; non notifica se cancellata; non notifica se prenotazione waitlist/cancelled; nessuna notifica se nessuna occorrenza domani; idempotente su run multipli stessa data.

Suite: 288 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R10 — Centro notifiche atleta (2026-08-24)

**`Athlete\Notifications`** Livewire, route `/athlete/notifications`:
- Lista tutte le notifiche DB (session_reminder, waitlist_promoted, class_cancelled) in ordine cronologico inverso
- Icona e colore per tipo notifica; badge "non lette" in evidenza
- `markRead(id)`: segna singola notifica come letta (owner check su `notifiable_id`)
- `markAllRead()`: segna tutte come lette tramite `unreadNotifications()->update()`
- `deleteNotification(id)`: elimina (owner check; altri utenti al sicuro)
- Paginazione 20 elementi

**Sidebar atleta:**
- Nuova voce "Notifiche" con icona campanella dopo "Messaggi"
- Badge rosso con conteggio non lette via Alpine store `notifications`
- Endpoint `GET /athlete/notifications-unread-count` → `{count: N}`

**Fix:** `route('athlete.booking')` → `route('athlete.bookings')` nella dashboard atleta

**Test (7):** vede proprie notifiche; non vede altrui; markRead; markAllRead; deleteNotification; delete non tocca altrui; endpoint unread-count

Suite: 282 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R09 Step 6 — Finestre prenotazione e cancellazione (2026-08-24)

**Finestra prenotazione (`Athlete\Booking::enrollClass`):**
- Verifica `booking_opens_days` (default 7): blocca iscrizione se occorrenza è troppo lontana (finestra non ancora aperta)
- Verifica `booking_closes_minutes` (default 30): blocca iscrizione se l'inizio è entro 30 min
- Configurazione centralizzata in `config/classes.php`; la logica non è nel service per permettere bypass backoffice

**Finestra cancellazione (`Athlete\Booking::cancelClassBooking`):**
- Verifica `free_cancel_hours` (default 3): blocca cancellazione se inizio è entro 3 ore
- Flash `session('error')` all'utente senza propagare eccezione

**`ClassBookingService::cancel(bool $byGym = false)`:**
- Parametro `$byGym`: se `true`, imposta `cancelled_by_gym` invece di `cancelled_by_athlete`; nessuna restrizione di finestra (già gestita dal chiamante)
- `GroupClassManager::removeParticipant()` ora passa `byGym: true` (staff bypass) e include controllo ruolo receptionist

**Test (5 `BookingWindowTest`):** enroll in finestra OK; enroll troppo presto bloccato; enroll troppo tardi bloccato; cancellazione entro finestra OK; cancellazione oltre free_cancel_hours bloccata

Suite: 275 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R09 Step 5 — GroupClassCatalog, sidebar submenu, dashboard atleta (2026-08-24)

**`GroupClassCatalog`** Livewire, route `/backoffice/group-classes/catalog`:
- CRUD definizioni corso (GroupClass): nome, descrizione, durata, capienza default, sala, colore, is_active
- Slug auto-generato con suffisso incrementale se già esistente
- `toggleActive(id)`: attiva/disattiva corso senza toccare le occorrenze
- `deleteClass(id)`: blocca se esistono occorrenze future pianificate (`whereDate`)
- Accesso riservato a gestore (`hasRole('gestore')`); trainer visualizza ma non può modificare
- Colonna "Prossimi" mostra conteggio occorrenze future tramite `withCount`

**Sidebar submenu Corsi collettivi:**
- Voce singola rimpiazzata da submenu a 3 voci: Occorrenze → Palinsesto → Catalogo corsi
- Tutti e tre rispettano il gate `can: view-group-classes`

**Dashboard atleta — card prossimi corsi:**
- `Dashboard::mount()`: carica max 3 `ClassBooking` confirmed future (JOIN su `class_occurrences`) se `Feature::active('group_classes')`
- View: sezione "Prossimi corsi" con dot colorato (colore corso), nome, data+orario; link a `athlete.booking`

**Test (8):** visualizza catalogo; crea corso; modifica corso; toggle active×2; blocca delete con occorrenze future; delete senza occorrenze; trainer non può creare; slug con suffisso se già esistente

Suite: 270 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R09 Step 4 — Notifica cancellazione, check-in receptionist, feature flag gate (2026-08-24)

**Notifica cancellazione occorrenza:**
- `ClassOccurrenceCancelledNotification` (mail + database + webpush): pattern identico a `WaitlistPromotionNotification`
- `NotifyClassCancellation` job: itera `confirmedBookings()->with('member.user')`, salta senza account; dispatched `afterResponse` da `GroupClassManager::deleteClass()` quando `$hasConfirmed`
- Flash message aggiornato: "Corso cancellato — partecipanti notificati."

**Check-in receptionist:**
- `GroupClassManager::markAttended` e `markNoShow`: aggiunto `'receptionist'` ai ruoli ammessi
- `completeOccurrence` rimane riservato a gestore e trainer

**Feature flag gate (`Athlete\Booking`):**
- `render()`: `futureClasses` e `myClassBookings` caricati solo se `Feature::active('group_classes')`; in precedenza le query giravano sempre indipendentemente dal flag

**Test (7):** dispatch job su deleteClass con confermati; no dispatch senza confermati; job notifica confermati con account; salta senza account; salta waitlist; receptionist markAttended; receptionist markNoShow

Suite: 262 pass / 6 skipped. PHPStan 0 errori. Pint OK.

---

## R09 Step 3 — ClassScheduleManager e attendance tracking (2026-08-24)

**`ClassScheduleManager`** Livewire, route `/backoffice/group-classes/schedules`:
- CRUD per ClassSchedule (palinsesto ricorrente): group_class, weekday (select Lun–Dom), start_time, trainer, valid_from, valid_until, is_active
- `toggleActive(id)`: switch on/off senza perdere le occorrenze già generate
- `deleteSchedule(id)`: blocca se esistono occorrenze future pianificate (`whereDate('date', '>=', today())`)

**Attendance tracking in `GroupClassManager`:**
- `completeOccurrence(id)`: solo da `planned`; transitions → `completed`; `confirmedBookings()->update(['attended_at' => now()])` bulk (esclude no_show già segnati)
- `markNoShow(bookingId)`: `status → no_show`, `attended_at → null`
- `markAttended(bookingId)`: `status → confirmed`, `attended_at → now()` (ripristino no_show)
- View: pulsante "Completa" in tabella e nel pannello dettaglio (visibile solo su planned); sezione no-show con ripristino; badge Presente su iscritti con attended_at; edit/completa nascosti su occorrenze completed/cancelled

**Test (13 nuovi):**
- `ClassScheduleManagerTest` (7 casi): lista, create, validazione, edit, toggle, delete con/senza occorrenze future
- `AttendanceTest` (6 casi): complete bulk, idempotenza su già-completed, markNoShow, markAttended, ordine no-show→complete, ruolo trainer

Suite: 255 pass / 6 skipped. PHPStan 0 errori. Pint OK.

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
