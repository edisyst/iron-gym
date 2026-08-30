# Audit Codice iron-gym — 2026-06-28 (v2)

> Audit v2 read-only. Nessuna modifica applicata al codice sorgente.
> Audit v1 (2026-06-27): finding CRITICAL SessionFeedbackForm IDOR, TemplateBuilder IDOR,
> middleware backoffice receptionist, FK mesocycles, N+1 DeloadEvaluator, indice exercise_sets.completed_at
> erano stati FIXATI nella sessione 2026-06-27. Questa revisione copre il codice post-fix.

---

# Audit v2 — 2026-06-28

## Sommario esecutivo

| Severita | Conteggio |
|---|---|
| CRITICAL | 0 |
| HIGH | 7 |
| MEDIUM | 9 |
| LOW | 8 |

Punto debole principale: assenza di Policy Laravel e mancanza di controlli owner-trainer
sistematici nei Livewire backoffice. Qualsiasi trainer autenticato puo' operare su mesocicli,
landmark e misurazioni di atleti non suoi (IDOR cross-trainer).

---

## 1. Sicurezza

### HIGH

- **app/Livewire/Backoffice/Exercises/ExerciseForm.php:253-257** — Upload immagine salva
  in `public_path('images/exercises')` con estensione client-originale. File accessibile
  senza auth nel public path.
  **Fix:** usare `Storage::disk('public')` con path non predicibile.

- **app/Http/Controllers/ProgressPhotoController.php:20** — Route `/athlete/photos/{photo}`
  serve file con `storage_path('app/'.$photo->file_path)`. Se path e' manipolato, path traversal
  possibile.
  **Fix:** verificare che il path risolta sia sotto `athletes/{id}/photos/` via `Storage::disk('local')->path()`.

### MEDIUM

- **Route download report** — `reports/download/{file}` usa `basename($file)` correttamente,
  ma non verifica che il file appartenga al gestore autenticato.
  **Fix:** includere `auth()->id()` nel nome file generato; verificare prefisso nel controller.

- **app/Livewire/Athlete/Messages.php:21-26** — `save()` non verifica che `$this->trainerId`
  abbia ruolo `trainer/gestore` prima dell'invio.
  **Fix:** `User::role(['trainer', 'gestore'])->findOrFail($this->trainerId)`.

---

## 2. Autorizzazione

### HIGH

- **app/Livewire/Backoffice/Mesocycles/MesocycleDetail.php:33-47, 79-124** — `mount()` carica
  il mesociclo senza ownership check. Trainer T1 vede e modifica mesocicli dell'atleta di T2.
  `applyProgression()` e `forceDeload()` verificano solo il ruolo, non ownership.
  **Fix:** in `mount()` aggiungere `abort_unless($meso->trainer_id === auth()->id() || auth()->user()->hasRole('gestore'), 403)`.

- **app/Livewire/Backoffice/Mesocycles/VolumeLandmarkManager.php:44-68** — Qualsiasi trainer
  puo' modificare i volume landmark di qualsiasi atleta.
  **Fix:** verificare `Mesocycle::where('athlete_id', $athleteId)->where('trainer_id', auth()->id())->exists()`.

- **app/Livewire/Backoffice/Athletes/BodyMeasurementForm.php:47** — Nessun ownership check.
  **Fix:** stessa verifica mesociclo del punto precedente.

- **app/Livewire/Backoffice/Athletes/AthleteAnalytics.php:45** e **AthleteProfile.php** —
  Dati medici e di performance visibili a tutti i trainer.
  **Fix:** ownership check o Policy `view` su atleta.

- **app/Livewire/Backoffice/Reports/TrainingReport.php:29-30, 114** — `openDrilldown(int $athleteId)`
  e' un'azione Livewire pubblica invocabile con qualsiasi `$athleteId`. `loadDrilldown()` non
  applica il filtro trainer.
  **Fix:** aggiungere `$trainerFilter` in `loadDrilldown()` come in `loadAthleteRows()`.

- **app/Livewire/Backoffice/Members/MemberForm.php:102-104** — Il receptionist puo' modificare
  campi sensibili (`fiscal_code`, `medical_cert_expiry`, `notes`) sul path di update.
  **Fix:** `abort_unless(auth()->user()->hasAnyRole(['gestore', 'trainer']), 403)` nel metodo `save()` su update.

- **app/Livewire/Backoffice/Calendar/BookingList.php:62-66** — `confirm(int $bookingId)` non
  verifica che la prenotazione appartenga al trainer autenticato.
  **Fix:** aggiungere `.where('trainer_id', Auth::id())` (bypass per gestore).

### MEDIUM

- **app/Livewire/Backoffice/Mesocycles/MesocycleAssign.php:92-103** — Validazione
  `exists:users,id` non verifica il ruolo `atleta`. Mesociclo assegnabile a trainer/gestore.
  **Fix:** aggiungere check ruolo nel metodo `assign()`.

- **app/Livewire/Athlete/SessionFeedbackForm.php:33-35** — `mount()` non verifica ownership;
  un atleta puo' aprire la pagina feedback di sessione di un altro atleta (200, form renderizzato).
  La protezione esiste solo in `save()`.
  **Fix:** aggiungere check ownership in `mount()`.

- **Assenza totale di Policy Laravel** — `app/Policies/` vuota. Autorizzazione distribuita
  in inline `abort_unless()` sparsi. Ogni nuovo componente rischia di non avere i controlli.
  **Fix:** creare `MesocyclePolicy` (view, update, forceDeload) e `MemberPolicy`; registrare
  in `AuthServiceProvider`.

---

## 3. Correttezza dominio

### OK — Verifiche superate

| Check | Stato |
|---|---|
| CHECK XOR `compound_pattern_id`/`joint_action_id` su exercises | OK — `chk_pattern_xor` in migration |
| ExerciseSet campi `planned_*`/`actual_*` | OK — `planned_weight_kg`, `actual_weight_kg`, ecc. |
| Tabella `training_sessions` (non `sessions`) | OK — `protected $table = 'training_sessions'` |
| E1rmCalculator formula Epley `w * (1 + r/30)` | OK — verificato in `E1rmCalculationTest` |
| DeloadEvaluator 4 trigger | OK — tutti e 4 implementati |
| WeeklyVolumeCalculator usa `contribution_pct` | OK — `SUM(em.contribution_pct / 100.0)` |

### MEDIUM

- **app/Services/WeeklyProgressionService.php:165-167** — `applyDeload()` usa
  `MAX(session_exercises.planned_sets_count)` raggruppato per `exercise_id`. Se lo stesso
  esercizio compare in sessioni diverse nello stesso microciclo, prende il MAX, non il piu' recente.
  **Fix:** usare come riferimento la sessione con `training_sessions.completed_at` piu' recente.

- **app/Services/MesocycleInstantiationService.php:54** — `is_deload` forzato sull'ultima
  settimana senza parametro configurabile.
  **Fix:** aggiungere `deload_last_week` (bool, default true) in `MesocycleAssign` e propagarlo.

### LOW

- **app/Models/SessionFeedback.php** — Campi `sleep_hours` e `stress_level` non presenti nella
  spec (`step-0-discovery.md` sezione 7 — 5 campi: pump, soreness_prev, perceived_effort,
  joint_pain, performance). Nessun danno funzionale; aggiornare la spec.

---

## 4. Performance

### MEDIUM

- **app/Livewire/Backoffice/Athletes/AthleteAnalytics.php:76-117** — `loadE1rmTable()`
  raggruppa per `(exercise_id, actual_weight_kg, actual_reps)` e somma `sessions_count`.
  **Bug overcounting**: 3 sessioni con 3 pesi diversi → `sessions_count = 9`.
  **Fix:** raggruppare solo per `exercise_id`, calcolare `COUNT(DISTINCT s.id)` a quel livello.

- **app/Livewire/Backoffice/Messages/MessageThread.php:60** e **Athlete/Messages.php:72** —
  `Message::conversation(...)->get()` carica l'intera conversazione senza limite.
  **Fix:** `->latest()->paginate(50)`.

- **app/Livewire/Backoffice/Reports/FinancialReport.php:40-53** — `render()` carica tutte
  le subscription dell'anno senza paginazione.
  **Fix:** aggregati SQL (`SUM`, `COUNT`) con dettaglio lazy.

- **app/Livewire/Backoffice/Reports/TrainingReport.php:64-108** — 4 LEFT JOIN con
  `COUNT(DISTINCT ...)`. Indice `completed_at` gia' aggiunto. Valutare CTE per leggibilita'.

### LOW

- **app/Services/WeeklyVolumeCalculator.php:20-25** — Cache `volume:{athleteId}:{weekId}`
  non invalidata da `WeeklyProgressionService` dopo modifica ai set.
  **Fix:** chiamare `$volumeCalc->forget($athleteId, $weekId)` al termine di `distributeSetsDelta()`.

- **app/Livewire/Backoffice/Mesocycles/MesocycleDetail.php:129** — `render()` ricarica il
  mesociclo con eager load completo ad ogni re-render.
  **Fix:** stash in property dopo il mount.

- **app/Livewire/Backoffice/Mesocycles/VolumeLandmarkManager.php:107-113** — `render()`
  interroga `Muscle::whereIn(slug)` due volte.
  **Fix:** una singola query, costruire entrambe le mappe.

---

## 5. Code quality

### MEDIUM

- **app/Livewire/Backoffice/Exercises/ExerciseForm.php** — 305 righe; gestisce caricamento
  dati, validazione, upload, sync pivot muscoli, sync pivot attrezzatura.
  **Fix:** estrarre `ExerciseSyncService`.

### LOW

- **app/Livewire/Athlete/ProgressPhotoUpload.php:79** — Nome file usa `time()`: due upload
  nella stessa seconda si sovrascrivono; vecchio file non eliminato.
  **Fix:** `Str::uuid()` come nome file.

- **app/Livewire/Backoffice/Templates/TemplateBuilder.php:164-165** — `removeExercise()`
  pulisce `group_key` senza filtrare per `template_id` (sicuro per UUID, ma fragile).

---

## 6. Migration e test

### LOW

- **database/migrations/2026_06_08_065330_create_training_sessions_table.php** — Nessun
  indice esplicito su `microcycle_week_id` (MySQL crea l'indice implicitamente sulla FK,
  ma non documentato).

- **Copertura test mancante:**
  - Test IDOR: trainer T1 non puo' vedere/modificare mesociclo dell'atleta di T2
  - Test bug `AthleteAnalytics.loadE1rmTable()` overcounting
  - Test `WeeklyProgressionService` distribuzione delta MEV→MRV settimana per settimana
  - Test `BodyMeasurementForm` backoffice cross-trainer

- **tests/Feature/AthleteHistoryTest.php:43-78** — Test "trainer non vede sessioni di atleta
  non suo" verifica solo `assertDontSee()` in HTML, non un 403. Trainer puo' accedere al profilo
  atleta (200) senza errore — comportamento permissivo da valutare.

- **Factory da verificare:** `MicrocycleWeek`, `SessionExerciseGroup`, `SessionExerciseFeedback`,
  `AthleteVolumeLandmark`, `PtBooking`, `GroupClass`.

---

## Priorita' fix raccomandate

| # | Componente | Severita | Tipo |
|---|---|---|---|
| 1 | `MesocycleDetail` — ownership trainer | HIGH | Auth |
| 2 | `VolumeLandmarkManager` — ownership trainer | HIGH | Auth |
| 3 | `BodyMeasurementForm` backoffice — ownership | HIGH | Auth |
| 4 | `AthleteAnalytics`/`AthleteProfile` — ownership | HIGH | Auth |
| 5 | `TrainingReport.openDrilldown()` — filtro trainer | HIGH | Auth |
| 6 | `MemberForm.save()` — blocco receptionist su update | HIGH | Auth |
| 7 | `BookingList.confirm()` — filtro trainer_id | HIGH | Auth |
| 8 | `AthleteAnalytics.loadE1rmTable()` — overcounting bug | MEDIUM | Bug |
| 9 | `WeeklyProgressionService.applyDeload()` — MAX vs recente | MEDIUM | Dominio |
| 10 | Creare `MesocyclePolicy` + `MemberPolicy` | MEDIUM | Auth |
| 11 | Paginazione `MessageThread`/`Messages` | MEDIUM | Performance |
| 12 | `MesocycleAssign` — verifica ruolo atleta | MEDIUM | Auth |
| 13 | `WeeklyVolumeCalculator` — cache invalidation | LOW | Performance |
| 14 | Test IDOR `MesocycleDetail` cross-trainer | LOW | Test |
| 15 | `ProgressPhotoController` — sanitize file_path | HIGH | Sicurezza |

---

# Audit v1 — 2026-06-27 (storico — finding principali FIXATI)

## Legenda severita
- CRITICAL — rischio sicurezza o dato corrotto
- HIGH — bug domain, auth bypass, performance seria
- MEDIUM — code quality, performance minore, test mancante
- LOW — cosmesi, naming, commento mancante

---

## 1. Sicurezza

| Severita | File | Riga | Problema | Fix proposto |
|---|---|---|---|---|
| CRITICAL | `app/Livewire/Athlete/SessionFeedbackForm.php` | 41 | **IDOR su `sessionId`**: `$sessionId` e' una public property Livewire valorizzata in `mount()`. Il metodo `save()` esegue `SessionFeedback::updateOrCreate(['session_id' => $this->sessionId], ...)` senza verificare che la sessione appartenga a `auth()->id()`. Un atleta puo' forgiare una action Livewire con un `sessionId` arbitrario e inviare/sovrascrivere il feedback di qualunque altra sessione. | In `save()`, aggiungere prima del `updateOrCreate`: `TrainingSession::whereHas('week.mesocycle', fn($q) => $q->where('athlete_id', auth()->id()))->findOrFail($this->sessionId);` |
| HIGH | `app/Livewire/Backoffice/Templates/TemplateBuilder.php` | 113, 123, 128, 149 | **IDOR su TemplateSession e TemplateSessionExercise**: `removeSession(int $sessionId)` esegue `TemplateSession::findOrFail($sessionId)` senza verificare `template_id == $this->template->id`. Stessa vulnerabilita in `updateSessionName()`, `addExerciseById()`, `removeExercise()`. Un trainer puo' eliminare o modificare sessioni di template di altri trainer passando l'ID corretto come parametro Livewire. | Aggiungere scope `->where('template_id', $this->template->id)` in tutte le query su `TemplateSession`. Per `TemplateSessionExercise` aggiungere verifica: `$tse->templateSession->template_id !== $this->template->id && abort(403)`. |
| MEDIUM | `database/migrations/2026_06_08_065328_create_mesocycles_table.php` | 16-18 | **FK mancanti su mesocycles**: `athlete_id` e `trainer_id` sono `unsignedBigInteger` senza FK constraint (`// FK esplicite aggiunte in futuro via ALTER`). Non e' stata trovata nessuna migration successiva che le aggiunga. L'integrita' referenziale non e' garantita: cancellare un User non cascada su Mesocycle. | Creare migration `add_foreign_keys_to_mesocycles_table` con `$table->foreign('athlete_id')->references('id')->on('users')->onDelete('restrict')` e stessa per `trainer_id`. |
| MEDIUM | `database/migrations/2026_06_08_065316_create_exercises_table.php` | 31 | **FK mancante su exercises.created_by**: `unsignedBigInteger('created_by')->nullable()` senza FK. Stessa situazione del punto precedente. | Aggiungere migration con `$table->foreign('created_by')->references('id')->on('users')->onDelete('set null')`. |

---

## 2. Autorizzazione

| Severita | File | Riga | Problema | Fix proposto |
|---|---|---|---|---|
| HIGH | `routes/backoffice.php` | 57-65 | **Receptionist puo' mutare dati training**: le route `/templates/create`, `/templates/{template}/builder`, `/mesocycles/assign`, `/mesocycles/{mesocycle}` usano solo il middleware generico `role:gestore\|trainer\|receptionist`. Il receptionist non dovrebbe mai modificare schede di allenamento o mesocicli. | Aggiungere `->middleware('role:gestore\|trainer')` su quelle route (create/builder template e assign/detail mesocycle). |
| HIGH | `routes/backoffice.php` + `app/Livewire/Backoffice/Mesocycles/MesocycleDetail.php` | 65, 63-104 | **Receptionist puo' applicare progressione e forzare deload**: `applyProgression()` e `forceDeload()` in `MesocycleDetail` non controllano il ruolo. Sono raggiungibili via action Livewire dal receptionist. | Aggiungere `abort_unless(auth()->user()->hasAnyRole(['gestore', 'trainer']), 403)` all'inizio di entrambi i metodi. In alternativa, proteggere la route `/mesocycles/{mesocycle}` solo per gestore\|trainer. |
| HIGH | `routes/backoffice.php` | 65 | **Receptionist puo' modificare volume landmarks**: `/athletes/{athleteId}/volume-landmarks` e' accessibile a tutti i ruoli backoffice. `VolumeLandmarkManager::save()` e `resetToDefaults()` mutano dati medico-sportivi dell'atleta. | Aggiungere `->middleware('role:gestore\|trainer')` sulla route `athletes.volume-landmarks`. |
| HIGH | `routes/backoffice.php` | 51-54 | **Receptionist puo' creare/modificare/archiviare esercizi**: `/exercises/create` e `/exercises/{exercise:slug}/edit` sono accessibili a tutti i ruoli backoffice. `ExerciseForm::save()` e `archive()` non controllano il ruolo. | Aggiungere `->middleware('role:gestore\|trainer')` sulle route `exercises.create` e `exercises.edit`. |
| MEDIUM | `app/Policies/` (directory vuota) | — | **Nessuna Policy definita**: l'intera autorizzazione si basa solo su middleware di route. Le action Livewire (invocate via WebSocket, indipendenti dal routing HTTP) non hanno un secondo layer di Policy. Entita' critica come `Mesocycle`, `WorkoutTemplate`, `Exercise` non hanno Policy. | Definire `MesocyclePolicy`, `WorkoutTemplatePolicy`, `ExercisePolicy`. Chiamare `$this->authorize('update', $mesocycle)` nei metodi mutanti dei component Livewire. |
| MEDIUM | `app/Livewire/Backoffice/Messages/MessageThread.php` | 35-43 | **Invio messaggi a user_id arbitrario**: `sendMessage()` non verifica che `$this->athleteId` abbia il ruolo `atleta`. Un trainer potrebbe inviare messaggi a qualunque `users.id` (inclusi altri trainer o gestore) manipolando la property pubblica. | Aggiungere `User::role('atleta')->findOrFail($this->athleteId)` prima di creare il messaggio. |

---

## 3. Correttezza di dominio

| Severita | File | Riga | Problema | Fix proposto |
|---|---|---|---|---|
| HIGH | `app/Services/DeloadEvaluator.php` | 79-90 | **N+1 in `currentWeek()`**: il metodo itera su `$mesocycle->weeks` (gia' caricate) e per ogni settimana esegue `TrainingSession::where('microcycle_week_id', $week->id)->where('status','completed')->exists()`. Genera N query separate (tipicamente 5 per un mesociclo standard). | Sostituire il loop con una singola query: raccogliere tutti i `week_id` con almeno una sessione completata, poi trovare quello con `week_number` massimo: `TrainingSession::whereIn('microcycle_week_id', $mesocycle->weeks->pluck('id'))->where('status','completed')->select('microcycle_week_id')->distinct()->get()`. |
| MEDIUM | `app/Services/DeloadEvaluator.php` | 160-169 | **`checkRirDrift()` carica tutti i set in PHP**: la query usa `ROW_NUMBER() OVER (PARTITION BY ...)` nel SELECT ma il filtro `$row->rn <= 3` viene applicato in PHP dopo aver caricato l'intera history dei set. Per atleti con mesocicli lunghi puo' essere significativo. | Avvolgere la query come subquery (`DB::table(DB::raw('(...) as ranked'))->where('rn','<=',3)`) cosi' il filtro e' eseguito in SQL. |
| MEDIUM | `app/Models/ExerciseSet.php` | 59-65 | **Duplicazione formula E1RM**: il metodo accessor `getEstimated1rmAttribute()` implementa la formula Epley (`w * (1 + reps / 30)`) invece di delegare a `E1rmCalculator::epley()`. Se la formula viene aggiornata in `E1rmCalculator` il Model non si aggiorna. | Sostituire con: `return \App\Services\E1rmCalculator::epley($this->actual_weight_kg, $this->actual_reps);` |
| LOW | `app/Services/E1rmCalculator.php` | 13-21 | **`epley(0.0, n)` restituisce 0.0**: per esercizi a corpo libero con `weight=0` il risultato e' 0.0 (non null). Questo e' matematicamente corretto ma non e' un 1RM significativo. Nessun rischio critico. | Documentare nel docblock il comportamento atteso per `weight=0`. |
| LOW | `app/Services/WeeklyVolumeCalculator.php` | 113-126 | **Soglia `approaching_mrv` non documentata**: la threshold `mavMax * 0.85` non e' spiegata nel codice. | Aggiungere commento: `// "approaching MRV" se si e' all'85% del MAV massimo o piu'`. |
| LOW | `database/migrations/2026_06_08_065316_create_exercises_table.php` | 41-47 | **CHECK XOR funziona solo su MySQL**: la migration condiziona il vincolo a `DB::getDriverName() === 'mysql'`. Su SQLite (usato nei test con `:memory:`) il vincolo non e' attivo. I test non verificano il vincolo XOR. | Aggiungere in `ExerciseForm::save()` un assert applicativo (gia' parzialmente presente via `required_if`), o un test che prova la violazione XOR tramite DB diretto su MySQL. |

---

## 4. Performance

| Severita | File | Riga | Problema | Fix proposto |
|---|---|---|---|---|
| HIGH | `app/Services/DeloadEvaluator.php` | 79-90 | **N+1 in `currentWeek()`** (vedi Dominio). Ogni render di `MesocycleDetail` richiama `evaluate()` → `currentWeek()` → N query. | Vedi fix in sezione Dominio. |
| MEDIUM | `app/Livewire/Backoffice/Mesocycles/MesocycleDetail.php` | 108-124 | **`DeloadEvaluator::evaluate()` chiamato ad ogni render()**: ogni ciclo Livewire (aggiornamento settimanale, cambio tab, ecc.) ri-esegue l'evaluator completo (volume calc + joint pain query + RIR drift query). Il volume calc e' cachato 15 min, le altre query no. | Spostare `evaluate()` fuori da `render()`: calcolarlo in `mount()` e in un metodo esplicito `refreshDeloadSignal()`, salvando il risultato come property serializzata. |
| MEDIUM | `app/Livewire/Backoffice/Exercises/ExerciseForm.php` | 294-300 | **4 query di lookup ad ogni render()**: `compoundPatterns`, `jointActions`, `allMuscles`, `allEquipment` sono letti dal DB ad ogni ciclo. Queste tabelle cambiano raramente. | Usare `#[Computed(persist: true)]` o `Cache::rememberForever()` per questi lookup. |
| MEDIUM | `app/Livewire/Backoffice/Subscriptions/SubscriptionForm.php` | 83-88 | **Carica tutti i Member attivi senza limit**: `Member::where('is_active', true)->orderBy('last_name')->get()` nel render(). Per palestre con 300+ iscritti genera una query pesante ad ogni ciclo. | Aggiungere `->select('id','first_name','last_name')` e implementare autocomplete Livewire con ricerca server-side (`wire:model.debounce`). |
| MEDIUM | `database/migrations/2026_06_08_065338_create_exercise_sets_table.php` | — | **Indice mancante su `exercise_sets.completed_at`**: usato in `ORDER BY` nelle query di `WeeklyProgressionService::applyDeload()` e `DeloadEvaluator::checkRirDrift()`. Senza indice genera full-scan sul join. | Creare migration con `$table->index('completed_at', 'idx_exercise_sets_completed_at')`. |
| MEDIUM | `database/migrations/2026_06_08_065328_create_mesocycles_table.php` | 18 | **Indice mancante su `mesocycles.trainer_id`**: il campo `trainer_id` non ha un indice (solo `athlete_id` ne ha uno). Se si filtrano/joinano mesocicli per trainer (es. report, calendario) si usa un full-scan. | Creare migration con `$table->index('trainer_id', 'idx_mesocycles_trainer')`. |

---

## 5. Code quality

| Severita | File | Riga | Problema | Fix proposto |
|---|---|---|---|---|
| MEDIUM | `app/Services/WeeklyProgressionService.php` | 163-213 | **`applyDeload()` ha 50+ righe**: mescola logica di business (riduzione set al 50%), query DB inline (last weight per esercizio), formatting della nota trainer, e sync dei set. | Estrarre la costruzione della nota trainer in `buildDeloadNote(SessionExercise $se, ?float $lastWeight): string`. Il sync set e' gia' separato in `syncExerciseSets()`. |
| MEDIUM | `app/Services/WeeklyProgressionService.php` | 222-290 | **`distributeSetsDelta()` ha 70+ righe**: doppio loop con query DB embedded (`$primaryMusclesByExercise`) e logica di selezione dell'esercizio migliore. Difficile da testare unitariamente. | Estrarre `findBestSessionExerciseForMuscle(string $slug, ...)` come metodo privato. |
| MEDIUM | `app/Livewire/Backoffice/Templates/TemplateBuilder.php` | 97-231 | **Logica di persistenza direttamente nel component Livewire**: 9 metodi mutanti con query DB dirette senza service layer. Nessuna validazione di ownership (vedi Sicurezza). | Estrarre in `TemplateBuilderService` i metodi `addSession()`, `removeSession()`, `copyWeek()`, `reorderExercises()`, `toggleGroup()`. |
| MEDIUM | `app/Services/DeloadEvaluator.php` | 14 | **`PRIMARY_MUSCLES` hardcoded**: l'array di slug e' una costante PHP. Se il seeder/DB usa slug diversi i trigger MRV non si attivano silenziosamente. | Caricare i muscoli "primari" da `config('deload.primary_muscles')` cosi' e' modificabile senza deploy. |
| LOW | `app/Livewire/Backoffice/Mesocycles/MesocycleDetail.php` | 29 | **Naming parameter fuorviante**: `public function mount(int $mesocycle)` — il parametro si chiama `$mesocycle` ma contiene un intero ID, non un Model. | Rinominare in `mount(int $mesocycleId)`. |
| LOW | `app/Livewire/Backoffice/Athletes/AthleteSessionHistory.php` | 27-29 | **Nessuna documentazione tipo** sul parametro `$athleteId`: la route lo riceve come `{athleteId}` e il Livewire component non verifica che l'utente sia effettivamente un atleta (controllo del ruolo). | Aggiungere `User::role('atleta')->findOrFail($athleteId)` in `mount()` per fail-fast su ID non-atleta. |

---

## 6. Migration e test

| Severita | File | Riga | Problema | Fix proposto |
|---|---|---|---|---|
| MEDIUM | `tests/Feature/DeloadEvaluatorTest.php` | — | **Trigger `rir_drift` e `end_of_mesocycle` non testati**: il file copre solo `mrv_reached`, `persistent_joint_pain`, e il caso "nessun deload". I restanti 2 trigger del `DeloadEvaluator` non hanno test di unita'. | Aggiungere `it('deload suggerito per RIR drift su 3 set consecutivi', ...)` e `it('deload suggerito per fine programmata mesociclo', ...)`. |
| MEDIUM | `database/factories/` | — | **Factory mancanti**: non esistono factory per `BodyMeasurement`, `AthleteVolumeLandmark`, `PtBooking`, `ClassBooking`, `Subscription`. Questo rende i test di integrazione che coinvolgono tracking corporeo e prenotazioni difficili da scrivere. | Creare le factory mancanti. Priorita': `BodyMeasurementFactory`, `SubscriptionFactory`, `AthleteVolumeLandmarkFactory`. |
| LOW | `database/migrations/` | — | **Timestamp duplicato**: `2026_06_08_065317_create_exercise_muscle_table.php` e `2026_06_08_065317_create_exercise_equipment_table.php` hanno lo stesso timestamp. L'ordine di esecuzione in `migrate:fresh` e' determinato dal filename alfabetico, ma e' ambiguo e fragile. | Rinominare `create_exercise_equipment_table.php` con timestamp `2026_06_08_065318`. |
| LOW | `database/migrations/` | — | **`down()` nei template/mesociclo**: le migration che usano `DB::statement()` nell'`up()` (es. il CHECK XOR in `create_exercises_table`) hanno un `down()` che fa solo `Schema::dropIfExists()`. Su MySQL con `migrate:rollback` senza fresh questo e' corretto (drop table rimuove i constraint). Verificare che non vi siano `down()` che non annullano esattamente l'`up()` per le migration di ALTER. | Ispezionare le migration di tipo `add_*` (es. `add_execution_description_to_exercises_table.php`) per verificare che `down()` rimuova la colonna aggiunta. |
| LOW | `tests/Feature/QueryCountTest.php` | — | **Test N+1 esistente**: punto positivo — esiste un test dedicato al conteggio query. Verificare che copra il path `DeloadEvaluator::evaluate()` e `WorkoutSession::mount()` che sono i punti piu' critici. | Aggiungere assertion sul numero di query in `DeloadEvaluator::evaluate()` (atteso: massimo 4-5, non N+settimane). |

---

## Riepilogo

| Area | Critical | High | Medium | Low |
|---|---|---|---|---|
| 1. Sicurezza | 1 | 1 | 2 | 0 |
| 2. Autorizzazione | 0 | 4 | 2 | 0 |
| 3. Correttezza di dominio | 0 | 1 | 2 | 3 |
| 4. Performance | 0 | 1 | 5 | 0 |
| 5. Code quality | 0 | 0 | 4 | 3 |
| 6. Migration e test | 0 | 0 | 2 | 3 |
| **Totale** | **1** | **7** | **17** | **9** |

---

## Priorita' di intervento consigliata

1. **CRITICAL — SessionFeedbackForm IDOR** (sicurezza): fix di 2 righe, impatto alto.
2. **HIGH — TemplateBuilder IDOR** (sicurezza): aggiungere scope `template_id` in 4 metodi.
3. **HIGH — Receptionist accesso training** (autorizzazione): aggiungere `middleware()` a 4-5 route.
4. **HIGH — MesocycleDetail azioni senza ruolo** (autorizzazione): `abort_unless()` in 2 metodi.
5. **HIGH — FK mancanti mesocycles** (sicurezza/integrita'): migration di 4 righe.
6. **HIGH — N+1 in DeloadEvaluator::currentWeek()** (performance/dominio): refactoring singola query.
7. **Resto MEDIUM/LOW**: schedulabili nel backlog pre-produzione.
