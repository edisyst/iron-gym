# Audit prestazioni — iron-gym

Data: 2026-08-30
Revisore: Claude (Sonnet 4.6)
Scope: servizi ad alto traffico, componenti Livewire principali, pattern cache Redis

---

## Metodologia

Revisione statica dei file sorgente. Nessun profiling runtime, nessuna misura wall-clock.
Severita': CRITICO (fix prima del go-live) | IMPORTANTE (fix nel prossimo sprint) | MINORE (backlog tecnico).

---

## Risultati

### CRITICO-1 — KpiService::churnRate() N+1 loop

**File:** `app/Services/KpiService.php` righe 153–168

```php
foreach ($expired as $sub) {
    $renewed = DB::table('subscriptions')
        ->where('member_id', $sub->member_id)
        ...
        ->exists();  // 1 query per riga
}
```

Con N abbonamenti scaduti nel periodo = N+1 query su cache miss.
Cache TTL 3600s mitiga i repeated hits ma il costo sul miss e' O(N) query.
Con 200 abbonamenti scaduti nell'anno = 201 query in una singola chiamata KPI.

**Fix:** sostituire il loop con una LEFT JOIN subquery o `whereIn` per calcolare
i rinnovi in batch:

```php
$expiredIds = $expired->pluck('id');
$memberIds  = $expired->pluck('member_id')->unique();

$renewedMemberIds = DB::table('subscriptions')
    ->whereIn('member_id', $memberIds)
    ->whereNotIn('id', $expiredIds)
    ->whereIn('member_id', function ($sub) use ($expired) {
        // ...
    })
    ->pluck('member_id')
    ->unique();
```

Alternativa piu' pulita: rewrite come unica query con LEFT JOIN su subquery
`MAX(started_at) per member` filtrata per range.

---

### CRITICO-2 — KpiService::trainerOccupancy() N+1 per trainer

**File:** `app/Services/KpiService.php` (area righe 259–307 circa)

Loop sui trainer, 3–4 query per iterazione (slot ricorrenti, eccezioni, prenotazioni).
Con 10 trainer = ~35 query su cache miss.
Cache TTL 3600s mitiga, ma il miss e' costoso.

**Fix:** aggregare in 3 query totali con `GROUP BY trainer_id`:
- 1 query: somma slot disponibili per trainer nel periodo
- 1 query: somma slot bloccati per trainer nel periodo
- 1 query: COUNT prenotazioni per trainer
Poi merge in PHP. Stesso risultato, query costante indipendente dal numero trainer.

---

### IMPORTANTE-3 — Backoffice\Dashboard::mount() — 6 COUNT senza cache

**File:** `app/Livewire/Backoffice/Dashboard.php` righe 27–51

6 COUNT distinte (`Member`, `Subscription` x2, `AccessLog`, `Member` x2) eseguite
su ogni mount del componente, senza alcun caching Redis.
Dashboard backoffice e' la pagina piu' visitata dai receptionist (ogni apertura browser,
ogni refresh).

Problema aggiuntivo: `expiringSoon(30)` (riga 29) e `expiringSoon(7)` (riga 50)
sono due scope separati sulla stessa tabella; una singola query con `CASE WHEN`
o un subquery basterebbe.

**Fix:** avvolgere le 6 COUNT in `Cache::remember('backoffice_dashboard_counts', 300, fn () => [...])`.
TTL 5 minuti e' sufficiente per una dashboard operativa.

---

### IMPORTANTE-4 — WorkoutSession::generateWarmup() N+1 UPDATE

**File:** `app/Livewire/Athlete/WorkoutSession.php` righe 416–420

```php
ExerciseSet::where('session_exercise_id', $sessionExerciseId)
    ->where('is_warmup', false)
    ->orderByDesc('set_index')
    ->get()
    ->each(fn ($s) => $s->update(['set_index' => $s->set_index + $warmupCount]));
```

Per ogni working set (tipicamente 3–6) viene eseguito 1 UPDATE separato.
Con 3 warm-up e 5 working set = 5 UPDATE invece di 1.

**Fix:**

```php
ExerciseSet::where('session_exercise_id', $sessionExerciseId)
    ->where('is_warmup', false)
    ->increment('set_index', $warmupCount);
```

`increment()` emette un singolo `UPDATE ... SET set_index = set_index + N WHERE ...`.
Risultato identico, 1 query invece di N.

---

### IMPORTANTE-5 — ManagerDashboard::render() atRiskMembers non cachato

**File:** `app/Livewire/Backoffice/Reports/ManagerDashboard.php` righe 71–92

```php
$atRiskMembers = DB::table('subscriptions as s')
    ->join('members as m', ...)
    ->leftJoin('subscriptions as s2', ...)
    ->whereNull('s2.id')
    ->select(..., DB::raw('(SELECT MAX(checked_in_at) FROM access_logs WHERE member_id = m.id) as last_access'))
    ...
    ->get();
```

- Eseguita su ogni `render()` (ogni cambio filtro data, ogni tick Livewire)
- Correlated subquery `(SELECT MAX(checked_in_at) FROM access_logs WHERE member_id = m.id)`:
  un accesso a `access_logs` per ogni riga nel result set
- Nessun caching

**Fix 1 (correlated subquery):** sostituire con LEFT JOIN:

```php
->leftJoin('access_logs as al', function ($j) {
    $j->on('al.member_id', '=', 'm.id');
})
->groupBy('m.id', 'm.first_name', 'm.last_name', 's.expires_at', 's2.id')
->select(..., DB::raw('MAX(al.checked_in_at) as last_access'))
```

**Fix 2 (caching):** avvolgere in `Cache::remember("at_risk_members:{$cacheKey}", 300, ...)`.

---

### IMPORTANTE-6 — TrainingReport::render() query complessa senza cache

**File:** `app/Livewire/Backoffice/Reports/TrainingReport.php`

`loadAthleteRows()` viene rieseguita su ogni render (ogni cambio filtro).
La query e' un multi-JOIN su `training_sessions`, `mesocycles`, `exercise_sets`,
`members`. Nessuna cache.

**Fix:** aggiungere `Cache::remember()` con chiave basata sui filtri correnti
(periodo, trainer_id) e TTL breve (60–120s) oppure usare `wire:dirty` + lazy
loading per evitare re-render su ogni keystroke.

---

### MINORE-7 — PersonalRecordDetector::hasSufficientHistory() senza cache

**File:** `app/Services/PersonalRecordDetector.php`

4-table JOIN con `COUNT(DISTINCT ts.id)` chiamato su ogni `check()`,
che viene invocato per ogni set completato (`quickLog`, `completeSet`).
In una sessione di 20 set = 20 esecuzioni della stessa query (stesso atleta,
stesso esercizio per i set dello stesso esercizio).

**Fix:**

```php
$key = "pr_history:{$athleteId}:{$exerciseId}";
return Cache::remember($key, 300, fn () => /* query JOIN */);
```

TTL 5 minuti e' sicuro (il conteggio sessioni non cambia mid-set).

---

### MINORE-8 — Backoffice\Dashboard: medicalCertIssuesCount e certExpiring30Count sovrapposti

**File:** `app/Livewire/Backoffice/Dashboard.php` righe 36–50

Due query su `members` con condizioni quasi identiche; la seconda e' un subset
della prima. Insieme al fix IMPORTANTE-3, consolidare in una singola query con
contatori calcolati in PHP da un unico result set.

---

## Non problematici (verificati)

| Servizio | Stato |
|---|---|
| `WeeklyVolumeCalculator` | OK — 3 query, cache Redis TTL 900s |
| `KpiService` (altri metodi) | OK — tutti con `Cache::tags(['kpi'])->remember()` TTL 3600s |
| `SessionRecapBuilder` | OK — 5 query fisse, no N+1 |
| `ReadinessEvaluator` | OK — solo config reads + aritmetica |
| `WorkoutSession::mount()` | OK — eager load completo con `load([...])` |
| `WorkoutSession::loadPreviousPerformance()` | OK — singola query aggregata con JOIN |
| `MemberList` | OK — paginato 15/page, `with(['activeSubscription.plan'])` |
| `ManagerDashboard::revenueByPeriod` doppia chiamata | Non duplicato — righe 62 e 65 usano range diversi (periodo filtro vs 12 mesi rolling) |

---

## Priorita' suggerita

| # | Finding | Severita' | Effort | Impatto |
|---|---|---|---|---|
| 1 | KpiService::churnRate() N+1 | CRITICO | Medio | Alto su cache miss |
| 2 | KpiService::trainerOccupancy() N+1 | CRITICO | Medio | Alto su cache miss |
| 3 | Backoffice Dashboard no cache | IMPORTANTE | Basso | Alto (pagina piu' visitata) |
| 4 | generateWarmup() N+1 UPDATE | IMPORTANTE | Basso | Medio (sessioni atleta) |
| 5 | atRiskMembers correlated subquery | IMPORTANTE | Basso | Medio |
| 6 | TrainingReport no cache | IMPORTANTE | Basso | Medio |
| 7 | PersonalRecordDetector no cache | MINORE | Basso | Basso |
| 8 | Dashboard COUNT sovrapposti | MINORE | Basso | Basso |

---

## Indici da verificare

Prima del go-live, verificare che esistano indici su:

- `subscriptions(member_id, expires_at, started_at)` — usato da churnRate, expiringSoon
- `subscriptions(expires_at)` — expiringSoon scope
- `access_logs(member_id, checked_in_at)` — atRiskMembers + last_access
- `training_sessions(mesocycle_id, status, completed_at)` — SessionRecapBuilder + TrainingReport
- `exercise_sets(session_exercise_id, is_warmup, set_index)` — generateWarmup + quickLog
- `pt_bookings(trainer_id, booked_date, status)` — trainerOccupancy + ptSessionsPerTrainer
- `members(is_active, medical_cert_expiry)` — Dashboard counts + ExpiryDashboard

Verificare con `EXPLAIN ANALYZE` su MySQL prima del go-live in produzione.
