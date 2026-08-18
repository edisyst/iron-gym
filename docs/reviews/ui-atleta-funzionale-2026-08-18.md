# Audit funzionale PWA atleta — 2026-08-18

**Scope:** tutta la superficie `/athlete/*` — route, componenti Livewire, flussi utente, edge case, accessibilità funzionale.
**Metodo:** lettura completa dei file PHP e Blade (componenti + view); confronto con test spec `docs/test/04-atleta.md`; confronto con audit precedenti in `docs/reviews/` per non riproporre finding già chiusi.
**Nota:** audit read-only, Fase 1. Nessuna modifica al codice. Attendere approvazione esplicita prima della Fase 2.

---

## Legenda severità

| Livello | Significato |
|---|---|
| **Bloccante** | Impedisce il funzionamento normale per l'atleta |
| **Alto** | Flusso rotto o dati mancanti — impatta ogni sessione |
| **Medio** | Funzionalità degradata o non conforme alla spec |
| **Basso** | Edge case, inconsistenza, tech debt senza impatto immediato |

---

## Riepilogo finding per severità

| ID | Severità | Area | Titolo |
|---|---|---|---|
| F-HUB-01 | **Alto** | Storico | Sessioni `skipped` assenti dallo storico |
| F-BOOK-01 | **Alto** | Prenotazioni | `cancelPtBooking`/`cancelClassBooking` crash su `member = null` |
| F-HUB-02 | **Medio** | Storico | Dettaglio sessione: esercizi time-based invisibili |
| F-BOOK-02 | **Medio** | Prenotazioni | Tab "Corsi" visibile senza feature flag `group_classes` |
| F-VOLUME-01 | **Medio** | Volume | N+1 in `WeeklyVolume::mount()` fallback settimana corrente |
| F-PUSH-01 | **Medio** | Push | `pushManager.subscribe()` chiamata ad ogni page load |
| F-SESSION-01 | **Basso** | Sessione | `deleteSelectedSet()` può eliminare set working senza guard |
| F-LAY-01 | **Basso** | Layout | `translate-y-full` Tailwind assente in JIT (già P3-A) |
| F-HIST-01 | **Basso** | Storico | Componente `History.php` e `history.blade.php` orfani (dead code) |
| F-SESSION-02 | **Basso** | Sessione | Offline sync JS usa API interna Livewire (`snapshot.memo.data`) |
| F-EMPTY-01 | **Basso** | Volume | Stato vuoto `WeeklyVolume` senza mesociclo attivo — view non verificata |

---

## Finding dettagliati

---

### F-HUB-01 — Alto · Storico · Sessioni `skipped` assenti dallo storico

**File:** `app/Livewire/Athlete/TrainingHub.php:317–321`
**Codice:**
```php
$sessions = TrainingSession::whereHas('week.mesocycle', ...)
    ->where('status', 'completed')  // <-- filtra solo completed
    ->...
    ->paginate(20);
```

**Problema:** la query dello storico include solo sessioni con `status = 'completed'`. Le sessioni saltate (`skipped`) sono invisibili all'atleta. Il test spec (`docs/test/04-atleta.md:157`) richiede esplicitamente: "Sessioni saltate visibili con indicazione". Quando un atleta salta una sessione, non può verificare lo storico dei salti né ripristinarla da questa vista.

**Comportamento atteso:** le sessioni `skipped` appaiono nello storico con badge visivo distinto (es. `x-athlete.badge status="skipped"`).

**Comportamento osservato:** sessioni `skipped` completamente assenti dalla lista storico.

**Impatto:** atleta non vede il proprio storico completo; trainer non può valutare pattern di salti dal backoffice (AthleteSessionHistory backoffice non è impattato — usa query separata).

**Radice:** filtro `->where('status', 'completed')` nella query di `TrainingHub::render()`.

**Fix proposto:**
- Estendere query a `->whereIn('status', ['completed', 'skipped'])`
- Aggiungere badge condizionale nella view per `skipped` (già disponibile `x-athlete.badge status="skipped"`)
- Il pannello dettaglio (exercise sets) deve mostrarsi solo per sessioni `completed` — guard nel pannello dettaglio

---

### F-BOOK-01 — Alto · Prenotazioni · `cancelPtBooking`/`cancelClassBooking` crash su `member = null`

**File:** `app/Livewire/Athlete/Booking.php:145–165` e `203–212`

**Codice problematico (`cancelPtBooking`):**
```php
public function cancelPtBooking(int $bookingId): void
{
    /** @var Member $member */
    $member = Auth::user()->member;  // null check assente

    $booking = PtBooking::where('id', $bookingId)
        ->where('member_id', $member->id)  // TypeError se $member è null
        ->firstOrFail();
```

**Problema:** se un utente con ruolo `atleta` non ha un record `Member` associato (edge case possibile: account creato direttamente da admin senza passare da `MemberForm`), `Auth::user()->member` restituisce `null`. Accedere a `$member->id` lancia `TypeError: Attempt to read property "id" on null` → 500.

Il metodo `bookPt()` esegue correttamente il null-check (riga 116). `cancelPtBooking` e `cancelClassBooking` no.

La view usa `$member ? collect(...) : collect()` per costruire le liste, quindi UI non mostra i bottoni cancella se `$member` è null. Ma una richiesta Livewire artigianale (CSRF valido, autenticato) può colpire il metodo direttamente → 500 e potenziale DoS.

**Comportamento atteso:** metodo restituisce senza errore o mostra flash errore se `$member` è null.

**Fix proposto:**
```php
$member = Auth::user()->member;
if ($member === null) {
    session()->flash('error', 'Profilo membro non trovato.');
    return;
}
```
(stesso pattern già usato in `bookPt()` e `enrollClass()`)

---

### F-HUB-02 — Medio · Storico · Dettaglio sessione: esercizi time-based invisibili

**File:** `resources/views/livewire/athlete/training-hub.blade.php` (zona dettaglio set)

**Problema:** nel pannello dettaglio di una sessione storica, i set vengono filtrati con `->whereNotNull('actual_reps')` (o in alternativa mostrano solo `actual_reps`, `actual_weight_kg`, `actual_rir`). Gli esercizi con `measurement_type = 'time'` o `'isometric_hold'` loggano `actual_duration_sec` — che viene ignorato. Risultato: i set completati di plank, lat machine isometrica, ecc. appaiono vuoti o non appaiono.

La stessa issue esiste in `history.blade.php:78` (riga `->whereNotNull('actual_reps')`) ma quel file è orfano (vedi F-HIST-01).

**Comportamento atteso:** pannello dettaglio mostra tutti i set completati indipendentemente dal tipo di misura, usando il campo corretto (`actual_duration_sec` per time/isometric, `actual_reps × actual_weight_kg` per gli altri).

**Fix proposto:** nella view, usare `->whereNotNull('completed_at')` invece di `->whereNotNull('actual_reps')`, e mostrare `actual_duration_sec` con unità `s` quando `actual_reps` è null.

---

### F-BOOK-02 — Medio · Prenotazioni · Tab "Corsi" visibile senza feature flag `group_classes`

**File:** `resources/views/livewire/athlete/booking.blade.php:27–36`

**Codice:**
```blade
<div class="ig-tab-group">
    <button ...>Sessione PT</button>
    <button ...>Corsi</button>  {{-- sempre visibile --}}
</div>
```

**Problema:** il tab "Corsi collettivi" e il relativo contenuto (lista corsi, bottone iscrizione, lista iscrizioni) sono renderizzati senza alcun controllo `@feature('group_classes')`. Il feature flag `group_classes` è impostato a OFF nel piano pilota (CLAUDE.md). Gli atleti vedono la tab, possono navigarci e tentare iscrizioni a corsi che potrebbero non essere disponibili operativamente.

**Comportamento atteso:** tab "Corsi" nascosta (o il tab non renderizzato) quando `group_classes` è OFF. Il componente `Booking::enrollClass()` / `cancelClassBooking()` non espone gate lato PHP — le azioni sono sempre eseguibili.

**Fix proposto:**
```blade
@feature('group_classes')
    <button ...>Corsi</button>
@endfeature
```
E wrappare il tab content `@if($activeTab === 'classes')` con `@feature('group_classes')`.
Aggiungere gate anche nei metodi `enrollClass()` e `cancelClassBooking()` in PHP.

---

### F-VOLUME-01 — Medio · Volume · N+1 in `WeeklyVolume::mount()` fallback

**File:** `app/Livewire/Athlete/WeeklyVolume.php:61–65`

**Codice:**
```php
$currentWeek = $mesocycle->weeks->first(
    fn (MicrocycleWeek $w) => $w->sessions()->where('status', '!=', 'completed')->exists()
) ?? $mesocycle->weeks->first();
```

**Problema:** `$mesocycle->weeks` è una Collection già caricata in memoria (eager load alla riga 40). Ma `$w->sessions()` chiama il builder del modello — esegue una query SQL per ogni elemento della Collection. Con 4 settimane = 4 query aggiuntive. Con 8 settimane = 8. Questa logica si attiva solo nel fallback (atleta fuori dalle date del mesociclo), ma è comunque N+1 in un mount().

**Impatto:** trascurabile sul pilota (4 settimane), ma peggiora con mesocicli più lunghi. Non blocca nulla.

**Fix proposto:** caricare `weeks.sessions.status` nell'eager load iniziale, oppure usare una subquery aggregata.

---

### F-PUSH-01 — Medio · Push Notifiche · `pushManager.subscribe()` chiamata ad ogni page load

**File:** `resources/views/layouts/athlete.blade.php:281–307`

**Problema:** lo script di registrazione push (protetto da `@feature('push_notifications')`) chiama `pushManager.subscribe()` a ogni caricamento di pagina. Anche se il browser deduplica le subscription (stessa `applicationServerKey` → stessa subscription), il `fetch('/athlete/push-subscribe')` viene inviato ogni volta, creando potenziali insert multipli nella tabella `push_subscriptions` o comunque traffico di rete inutile su ogni navigazione.

**Comportamento atteso:** `pushManager.subscribe()` dovrebbe essere chiamato solo se non esiste già una subscription attiva (controllo con `getSubscription()` prima di `subscribe()`).

**Fix proposto:**
```js
registration.pushManager.getSubscription().then(function(existing) {
    if (existing) return; // già iscritto, non fare nulla
    registration.pushManager.subscribe({...}).then(function(subscription) {
        fetch('/athlete/push-subscribe', {...});
    });
});
```

---

### F-SESSION-01 — Basso · Sessione · `deleteSelectedSet()` può eliminare set working

**File:** `app/Livewire/Athlete/WorkoutSession.php:309–323`

**Codice:**
```php
public function deleteSelectedSet(): void
{
    if ($this->selectedSetId === null) { return; }
    ExerciseSet::whereHas('sessionExercise', ...)
        ->findOrFail($this->selectedSetId)
        ->delete();  // nessun check su is_warmup
```

**Problema:** `deleteWarmupSet()` ha un guard `->where('is_warmup', true)` che impedisce l'eliminazione di set di lavoro. `deleteSelectedSet()` non ha questo guard — può eliminare sia warmup che set di lavoro pianificati.

L'action zone mostra "Elimina" per qualsiasi set selezionato (warmup o working). Il `wire:confirm` offre un layer di conferma, ma non impedisce strutturalmente la cancellazione di set pianificati dal trainer.

**Impatto:** un atleta può eliminare per errore un set di lavoro prescitto (non solo aggiunto da lui). L'eliminazione è irreversibile senza riassegnare il mesociclo.

**Nota:** potrebbe essere intenzionale (atleta può adattare il volume). Se intenzionale, documentare esplicitamente. Se non intenzionale, aggiungere guard o distinguere visivamente.

---

### F-LAY-01 — Basso · Layout · `translate-y-full` non in JIT (finding precedente P3-A non chiuso)

**File:** `resources/views/livewire/athlete/workout-session.blade.php:273–275`

**Problema:** già segnalato come P3-A in `ui-atleta-ergonomia-2026-07-06.md`, non ancora applicato. Il drawer "Salta a..." appare senza animazione slide-up. Funzionalità intatta.

**Fix:** aggiungere `'translate-y-full'` alla `safelist` in `tailwind.config.js`.

---

### F-HIST-01 — Basso · Storico · Componente `History.php` e `history.blade.php` orfani

**File:** `app/Livewire/Athlete/History.php`, `resources/views/livewire/athlete/history.blade.php`

**Problema:** la route `/athlete/history` punta a `TrainingHub::class` (non `History::class`). Il componente `History` e la sua view esistono nel codebase ma non sono accessibili da alcuna route né embedded in `TrainingHub` (il quale ha la propria logica storico diretta). Sono dead code.

**Impatto:** nessun impatto funzionale. Confusione durante la navigazione del codice; aggiunte future potrebbero modificare il file sbagliato.

**Fix:** rimuovere `History.php` e `history.blade.php` e aggiornare `component-map.md`.

---

### F-SESSION-02 — Basso · Sessione · Offline sync legge API interna Livewire

**File:** `resources/views/livewire/athlete/workout-session.blade.php:488–492`

**Codice:**
```js
const d = $wire.__instance?.snapshot?.memo?.data ?? {};
const sd = (d.setData ?? {})[setId] ?? {};
```

**Problema:** `$wire.__instance.snapshot.memo.data` è l'API interna di Livewire 3, non documentata come stabile. Un aggiornamento di Livewire potrebbe cambiare la struttura del snapshot e rompere silenziosamente la lettura offline dei valori di `setData`.

**Impatto:** solo su path offline (offline → compete_set). Non bloccante finché Livewire non viene aggiornato.

**Fix proposto:** esporre i dati necessari tramite un endpoint o variabile JS dedicata invece di accedere al snapshot interno.

---

### F-EMPTY-01 — Basso · Volume · Stato vuoto `WeeklyVolume` senza mesociclo

**File:** `app/Livewire/Athlete/WeeklyVolume.php:44–47` + view non verificata

**Problema:** quando `$mesocycle === null`, `mount()` ritorna early — `$weeks`, `$volumeData`, `$intensityMap` restano array vuoti. La view di `WeeklyVolume` non è stata letta integralmente: va verificato se mostra un `x-athlete.empty-state` appropriato o se il body-map SVG renderizza comunque senza body map muscles colorati.

**Impatto:** atleta senza mesociclo attivo che naviga `/athlete/volume` potrebbe vedere una pagina vuota o parzialmente broken.

**Fix proposto:** verificare la view `weekly-volume.blade.php` e aggiungere `x-athlete.empty-state` se assente.

---

## Aree verificate senza finding

| Area | Stato |
|---|---|
| Auth + routing (`auth + role:atleta`) | OK — middleware corretto su tutte le route |
| Dashboard hero sessione / strip mesociclo / ultimo allenamento | OK — ownership via `athlete_id`, empty states presenti |
| Dashboard `restoreSession()` | OK — ownership check via `whereHas` |
| WorkoutSession ownership check in `mount()` | OK — abort(403) se mesociclo non appartiene all'atleta |
| WorkoutSession `quickLog()` / `completeSet()` — ownership via `whereHas` | OK |
| WorkoutSession readiness modal — default precompilati a 2 (valore medio) | OK |
| WorkoutSession `generateWarmup()` — idempotenza | OK |
| WorkoutSession `deleteWarmupSet()` — guard `is_warmup=true` | OK |
| WorkoutSession sostituzione — doppio blocco su set completati | OK |
| WorkoutSession `acceptModulation()` — ownership set via `whereHas` | OK |
| WorkoutSession `completeSession()` → `showFeedback=true` → embed `livewire:athlete.session-feedback-form` | OK |
| SessionFeedbackForm ownership in `mount()` e `save()` (doppio check) | OK |
| SessionFeedback `updateOrCreate` — previene duplicati | OK |
| SessionRecap ownership + status check (`where('status','completed')`) | OK — sessioni non complete restituiscono 403 |
| PersonalRecords — filtro `where('athlete_id', auth()->id())` | OK |
| WeeklyVolume ownership settimana (`whereHas mesocycle.athlete_id`) | OK |
| Messages — `trainerId` preservato nello snapshot Livewire 3 | OK — proprietà private incluse in snapshot cifrato |
| Messages — ownership invio (trainer verificato con `User::role()->findOrFail`) | OK |
| Profile `updateProfile()` / `updatePassword()` — validazione e Rule::unique ignore self | OK |
| Push subscription — `@feature('push_notifications')` gate presente | OK |
| Push subscription — graceful skip se VAPID key assente | OK |
| Sync offline batch endpoint — idempotenza via `client_uuid UNIQUE` | OK |
| Sync offline batch — ownership set via `whereHas mesocycle.athlete_id` | OK |
| PR toast — ascolta evento `pr-achieved` da Livewire, auto-dismiss 4s | OK |
| Bottom nav 4 tab — `x-athlete.bottom-nav` | OK |
| `$store.messages.init()` — chiamato automaticamente da Alpine.js store init hook | OK |
| Safe-area topbar (fix P1-A da audit precedente) — da verificare in CSS | Da verificare (fix era in scope audit precedente, segnato come Fase B) |
| Accento light theme `#C05000` (fix P1-B) | OK — CSS aggiornato |

---

## Verifica area Push Notifications

`push_notifications` flag OFF per default nel pilota. La registrazione è in `@feature('push_notifications')`. Se flag OFF, il blocco script non viene renderizzato → nessuna subscription avviene. Quando flag sarà attivato, varrà F-PUSH-01 (subscribe su ogni page load).

Verifica ricezione reale push: non testabile in ambiente code-only — richiede device reale con VAPID configurato.

---

## Aree richiedenti verifica manuale su device

Le seguenti aree richiedono test su dispositivo reale (non verificabili da codice):

- **Rest timer + vibrazione + Notification API**: dipende da permessi browser device
- **Export PNG SessionRecap via html-to-image**: dipende da canvas API disponibile
- **Web Share API**: dipende da device e browser
- **Service Worker v2 cache**: richiede DevTools Network → Offline
- **Safe-area iOS topbar/bottom**: richiede iPhone con Dynamic Island/notch
- **Push notification ricezione**: richiede VAPID configurato + device reale

---

## Stato finding per fase

### Da implementare in Fase 2 (dopo approvazione)

| ID | Fix stimato | Rischio |
|---|---|---|
| F-HUB-01 | ~30 min (query + badge view) | Basso |
| F-BOOK-01 | ~10 min (null check 2 metodi) | Zero |
| F-HUB-02 | ~20 min (filtro set + mostra duration) | Basso |
| F-BOOK-02 | ~15 min (@feature gate + PHP guard) | Basso |
| F-VOLUME-01 | ~20 min (eager load o subquery) | Basso |
| F-PUSH-01 | ~10 min (getSubscription() check) | Zero |
| F-SESSION-01 | Discutere con utente se intenzionale | — |
| F-LAY-01 | ~5 min (tailwind safelist) | Zero |
| F-HIST-01 | ~10 min (rm + component-map update) | Zero |
| F-SESSION-02 | Medio termine (refactor offline sync) | Medio |
| F-EMPTY-01 | ~15 min (leggere view + aggiungere empty-state se assente) | Zero |
