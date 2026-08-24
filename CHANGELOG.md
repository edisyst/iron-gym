# Changelog — iron-gym

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
