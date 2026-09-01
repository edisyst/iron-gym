# API Assessment — iron-gym

Data: 2026-09-01  
Scope: read-only, nessuna modifica al codice applicativo.

---

## Fase 1 — Infrastruttura HTTP esistente

### 1.1 routes/api.php

**Non esiste.** Il file non è presente nella cartella `routes/`.  
Evidenza: `ls routes/` → `athlete.php auth.php backoffice.php console.php web.php`  
`bootstrap/app.php:15` registra solo `web: routes/web.php` e `commands: routes/console.php`. Nessun `api:` key in `withRouting`.

**Implicazione:** aggiungere API richiede creare `routes/api.php` e referenziarlo in `bootstrap/app.php`.

### 1.2 Sanctum

`laravel/sanctum` **non è in `composer.json`** (né in `require` né in `require-dev`).  
Evidenza: `composer.json:8` (lista require) — assente.

`app/Models/User.php:20` usa solo `HasFactory, HasRoles, Notifiable` — nessun `HasApiTokens`.  
La migration `personal_access_tokens` **non esiste** in `database/migrations/`.

**Implicazione:** Sanctum va installato (`composer require laravel/sanctum`) prima di qualsiasi endpoint API con token auth.

### 1.3 bootstrap/app.php — configurazione rilevante

`bootstrap/app.php:22–26`: registra alias middleware spatie (`role`, `permission`, `role_or_permission`).  
`bootstrap/app.php:29–30`: `shouldRenderJsonWhen(fn => $request->is('api/*'))` — il rendering JSON per eccezioni è già predisposto per il prefisso `/api/*`. **Convenzione già stabilita e riusabile.**  
`bootstrap/app.php:34–38`: le eccezioni `AuthorizationException`, `ValidationException`, `ModelNotFoundException` sono escluse dal reporting Flare (non però gestite esplicitamente in JSON — dipenderanno dall'handler default di Laravel + `shouldRenderJsonWhen`).

Nessun middleware group `api` custom definito qui.

### 1.4 config/auth.php

Guard definita: solo `web` (session, provider `users`). **Nessun guard `api`.**  
`config/auth.php:40–45`: solo `guards.web`.  

Sanctum, una volta installato, aggiunge automaticamente il guard `sanctum` via il suo ServiceProvider. Va aggiunto esplicitamente come fallback nel guard default o usato con `auth:sanctum` nei middleware delle route API.

### 1.5 POST /athlete/session/sync — unico precedente JSON

Route: `routes/athlete.php:78` — `Route::post('/session/sync', [SyncBatchController::class, 'handle'])->name('session.sync')`  
Guard: gruppo `auth` + `role:atleta` (riga 25 del file).  
Controller: `app/Http/Controllers/SyncBatchController.php`

**Convenzioni già introdotte da questo endpoint:**

| Aspetto | Implementazione attuale |
|---|---|
| Payload chiave operazioni | `operations[]` con campi `client_uuid`, `operation`, `client_timestamp`, `payload` |
| Risposta successo | `{'results': [{'client_uuid': '...', 'status': 'ok|skipped|skipped_conflict|forbidden|error'}]}` |
| HTTP status code | sempre 200 (anche per forbidden/error — non standard) |
| Idempotenza | `sync_operations.client_uuid UNIQUE` — replay ignorato con `status: skipped` |
| Ownership check | `whereHas('sessionExercise.session.week.mesocycle', athlete_id)` |
| Conflitto last-write-wins | `completed_at` server ms > `client_timestamp` → `status: skipped_conflict` |
| Formato risposta JSON | `response()->json(['results' => $results])` — no envelope standard |

**Nota:** il controller non ha rate limiting. Gli status di errore viaggiano sempre con HTTP 200, pattern da NON replicare sulle API pubbliche.

### 1.6 Rate limiting

Nessun `RateLimiter::for` configurato in `AppServiceProvider` né in alcun provider.  
Nessun middleware `throttle:` sulle route esistenti.  
Driver cache: Redis (configurato via `predis/predis`). Il driver è pronto per supportare rate limiting by-token.

### 1.7 Export CSV — pattern autorizzazione

`routes/backoffice.php:166` — `subscriptions/export` → `middleware('role:gestore')`  
`routes/backoffice.php:208` — `members/export` → `middleware('role:gestore')`  

Pattern: singolo middleware `role:X` senza gate aggiuntivi. Non riusabile direttamente per API (richiedono `auth:sanctum` + ability check invece di role check).

---

## Fase 2 — Inventario del dominio candidabile

### 2.1 Gestionale core

| Model | Tabella | RouteKey | SoftDelete | Fillable rilevanti | Relazioni principali | Invarianti | Service scrittura |
|---|---|---|---|---|---|---|---|
| Member | members | id (default) | SÌ | first_name, last_name, email, phone, date_of_birth, medical_cert_expiry, notes, is_active | hasOne(User), hasMany(Subscription), hasMany(AccessLog) | email unique (non verificato nel model, da verificare in migration) | nessuno — scrittura in `MemberForm.php:109` |
| SubscriptionPlan | subscription_plans | id | NO | name, price_cents, duration_days, max_accesses, is_active | hasMany(Subscription) | nessuna nota | nessuno — scrittura in `SubscriptionForm` (non verificato) |
| Subscription | subscriptions | id | NO | member_id, plan_id, started_at, expires_at, accesses_used, accesses_remaining, status, created_by | belongsTo(Member), belongsTo(SubscriptionPlan) | status enum; expires_at calcolato da duration_days | nessuno — scrittura in `SubscriptionForm.php:76` |
| AccessLog | access_logs | id | NO | member_id, subscription_id, checked_in_at, checked_in_by, note | belongsTo(Member), belongsTo(Subscription), belongsTo(User, checked_in_by) | validazione cert+abb in QuickCheckin Livewire | nessuno — scrittura in `QuickCheckin.php:77` e `AccessLogList.php:96` |

**Punti critici scrittura senza service:**  
- `Member` creazione: `MemberForm.php:109` — include logica di creazione User+ruolo+password inline.  
- `Subscription` creazione: `SubscriptionForm.php:76` — calcolo expires_at inline, aggiornamento accesses_remaining.  
- `AccessLog` creazione: `QuickCheckin.php:77` — validazione cert medico scaduto e abbonamento attivo inline nel componente Livewire.

### 2.2 Tassonomia esercizi

| Model | Tabella | RouteKey | SoftDelete | Note |
|---|---|---|---|---|
| Exercise | exercises | slug (`getRouteKeyName` riga 19) | SÌ | CHECK XOR: esattamente una tra compound_pattern_id e joint_action_id; relazioni N-M Muscle+Equipment; scrittura in `ExerciseForm` Livewire |
| Muscle | muscles | id | NO | lookup, solo lettura API |
| Equipment | equipment | id | NO | lookup, solo lettura API |
| MovementPattern | movement_patterns | id | NO | lookup, category: compound_pattern/joint_action |

### 2.3 Template e periodizzazione

| Model | Tabella | SoftDelete | Invarianti | Service scrittura |
|---|---|---|---|---|
| WorkoutTemplate | workout_templates | SÌ | snapshot all'istanziamento — modifiche non si propagano | scrittura in Livewire `WorkoutTemplateForm` (non verificato) |
| Mesocycle | mesocycles | SÌ | snapshot; athlete_id + trainer_id obbligatori | `MesocycleInstantiationService::instantiate()` — service presente |
| TrainingSession | training_sessions | NO | status enum: planned/in_progress/completed/skipped; gestito da `TrainingSessionObserver` | observer + Livewire `WorkoutSession` |
| ExerciseSet | exercise_sets | NO | campi planned_* e actual_* separati | `SyncBatchController` (unico controller JSON esistente) |

### 2.4 Prenotazioni

| Model | Tabella | SoftDelete | Invarianti | Service scrittura |
|---|---|---|---|---|
| ClassOccurrence | class_occurrences | NO | UNIQUE (class_schedule_id, date); status: planned/cancelled/completed | command `classes:generate-occurrences` |
| ClassBooking | class_bookings | NO | UNIQUE (class_occurrence_id, member_id); status enum; waitlist con promozione | `ClassBookingService::enroll()` e `cancel()` — service presente |
| PtBooking | pt_bookings | NO | overlap check trainer+atleta; cancellation_deadline | `PtBookingService::book()` e `cancel()` — service presente |
| GroupClass | group_classes | id | slug unique; is_active | scrittura in `GroupClassCatalog` Livewire |
| TrainerAvailability | trainer_availabilities | NO | weekday 0-6 (0=lun); UNIQUE (trainer_id, weekday, start_time) non verificato | scrittura in Livewire; `TrainerAvailabilityObserver` ricalcola slot |

### 2.5 Tracking atleta (dati sensibili)

| Model | Note sensibilità |
|---|---|
| BodyMeasurement | peso, BF%, misure corporee — SENSIBILE |
| ProgressPhoto | foto progressi — MOLTO SENSIBILE |
| PersonalRecord | e1RM per esercizio — meno sensibile |
| SessionFeedback | scala 0-3 post-sessione |
| SessionReadinessCheck | sleep_quality, stress_level, soreness_level, joint_status |

### 2.6 Punti dove la logica di scrittura è solo in Livewire (richiederebbero estrazione)

1. **Creazione Member + User** (`MemberForm.php:109`) — include hash password, assegnazione ruolo, invio credenziali email.
2. **Creazione Subscription** (`SubscriptionForm.php:76`) — calcolo expires_at, decremento accesses_remaining.
3. **Check-in Access** (`QuickCheckin.php:77`, `AccessLogList.php:96`) — validazione cert medico + abbonamento attivo + decremento ingressi rimanenti.
4. **Creazione Exercise** (`ExerciseForm` Livewire) — gestione XOR, sync relazioni N-M con pivot.
5. **Creazione GroupClass** (`GroupClassCatalog` Livewire) — gestione slug.

---

## Fase 3 — Autorizzazione e ownership

### 3.1 Ruoli spatie usati

Ruoli effettivi: `atleta`, `trainer`, `gestore`, `receptionist` (dichiarati in CLAUDE.md, verificati nelle route e nei gate).

Gate definiti in `AppServiceProvider.php:104–134`:

| Gate | Condizione |
|---|---|
| `view-group-classes` | `Feature::active('group_classes')` — nessun check ruolo |
| `view-training-reports` | `!hasRole('receptionist')` |
| `manage-trainer-availability` | `hasAnyRole(['gestore', 'trainer'])` |
| `send-campaigns` | `hasRole('gestore')` |
| `access-training-section` | `hasAnyRole(['gestore', 'trainer'])` |
| `access-admin-section` | `hasRole('gestore')` |
| `view-access-logs` | `hasAnyRole(['gestore', 'receptionist'])` |
| `manage-members` | `hasAnyRole(['gestore', 'receptionist'])` |
| `manage-subscriptions` | `hasAnyRole(['gestore', 'receptionist'])` |
| `view-financial-reports` | `Feature::for($user)->active('financial_reports')` — internamente `hasRole('gestore')` |
| `view-messaging` | `Feature::active('messaging')` — nessun check ruolo |
| `view-athlete-bookings` | feature flag — nessun check ruolo |
| `enroll-pt-bookings` | feature flag — nessun check ruolo |
| `view-session-recap` | feature flag — nessun check ruolo |
| `view-personal-records` | feature flag — nessun check ruolo |
| `view-weekly-volume` | feature flag — nessun check ruolo |

Nessuna Policy Eloquent trovata in `app/Policies/`.

### 3.2 Pattern ownership check oggi usati

| Pattern | Dove applicato |
|---|---|
| `whereHas('sessionExercise.session.week.mesocycle', athlete_id)` | `SyncBatchController.php:130, 204` |
| `whereHas('session.week.mesocycle', athlete_id)` | `SyncBatchController.php:131` |
| `middleware('role:atleta')` | tutte le route `/athlete/*` (athlete.php:25) |
| `middleware('role:gestore|trainer|receptionist')` | tutte le route `/backoffice/*` (backoffice.php:48) |
| `abort_unless` / `authorize` | non verificato sistematicamente — non trovato nei controller esaminati |

### 3.3 Traduzione su API stateless (conseguenze)

**Endpoint gym-wide** (non richiedono scoping per atleta/trainer):  
Lettura esercizi, lookup muscoli/equipment, piani abbonamento, definizioni corsi collettivi, occorrenze corsi future.

**Endpoint con scoping per atleta:**  
Sessioni, mesocicli, prenotazioni PT/corsi, misurazioni corporee, PR, messaggi. Il token Sanctum permette `Auth::id()` come `athlete_id` — funziona se il token appartiene a un User con relazione Member.

**Endpoint con scoping per trainer:**  
Disponibilità, PT booking assegnate. `Auth::id()` come `trainer_id`.

### 3.4 Account di servizio api_client — punti critici

**Dove l'autorizzazione è basata su ruolo (e non su permesso/ability):**

1. `routes/athlete.php:25` — `middleware('role:atleta')` — un `api_client` senza ruolo `atleta` non accede alle route `/athlete/*` incluso `/athlete/session/sync`.
2. `routes/backoffice.php:48` — `middleware('role:gestore|trainer|receptionist')` — un `api_client` viene bloccato.
3. `routes/backoffice.php:166, 208` — `middleware('role:gestore')` — export CSV bloccato.
4. `AppServiceProvider.php:110` — `view-training-reports` → `!hasRole('receptionist')`: un utente senza nessun ruolo **passerebbe** questo gate (la condizione è vera se non hai il ruolo receptionist — un api_client senza ruoli soddisfa questa condizione). **Rischio di falso positivo.**
5. `AppServiceProvider.php:113–118` — `manage-trainer-availability`, `access-training-section`, `access-admin-section`, `send-campaigns` → tutti `hasRole(...)` — un api_client senza ruoli verrebbe **respinto**. Comportamento corretto ma da non dare per scontato.
6. `AppServiceProvider.php:126` — `view-financial-reports` → `Feature::for($user)->active('financial_reports')` che internamente (`AppServiceProvider.php:74`) chiama `$user->hasRole('gestore')` — api_client respinto. OK.

**Colonne FK verso users valorizzate dalle scritture:**

| Colonna | Tabella | Scrittura attuale | Accetta api_client? |
|---|---|---|---|
| `checked_in_by` | access_logs | `Auth::id()` in QuickCheckin | non verificato — dipende da implementazione |
| `booked_by` | class_bookings | `ClassBookingService::enroll()` — da verificare se usa `Auth::id()` | non verificato |
| `created_by` | subscriptions | `SubscriptionForm.php` — da verificare | non verificato |
| `created_by` | exercises | `ExerciseForm` — da verificare | non verificato |
| `cancelled_by` | pt_bookings | `PtBookingService::cancel()` — da verificare | non verificato |

**Meccanismi per bloccare login web a un User:**  
La tabella `users` **non ha una colonna `is_active`** (verificato: migration `0001_01_01_000000_create_users_table.php` non la contiene).  
`User` implementa `MustVerifyEmail` (`User.php:17`): un utente non verificato non può accedere alle route con middleware `verified`. Tuttavia le route backoffice e athlete non usano `verified` (non trovato nelle route files esaminate).  
**Meccanismo più pulito per rendere l'account di servizio non-web:** impostare password a null (il driver session di Laravel rifiuta login con password null se si usa `Hash::check`) oppure non assegnare il ruolo `atleta`/backoffice in modo che i middleware `role:` lo blocchino sulle route web. Soluzione più robusta: aggiungere colonna `is_active` / `is_service_account` alla migration — ma questa è una decisione da prendere in API01.

**Assunzione di ruolo in factory/seeder:**  
`UserFactory` (non letto completamente ma da pattern osservato): i seeder assegnano sempre un ruolo a ogni User creato. Query come `User::all()` non assumono ruolo ma Gate e middleware sì. Un User senza nessun ruolo non è un pattern testato dai seeder attuali — comportamento in scenari edge non verificato.

### 3.5 Discrepanze documentazione vs codice

Nessuna discrepanza rilevante trovata nelle aree esaminate. CLAUDE.md descrive correttamente i pattern.

---

*Documento prodotto in sessione read-only — nessun file applicativo modificato.*
