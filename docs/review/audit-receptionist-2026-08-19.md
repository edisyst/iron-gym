# Audit receptionist — 2026-08-19

Perimetro: ruolo `receptionist` — anagrafica tesserati, certificati medici, abbonamenti/ingressi, check-in, consultazione training in sola lettura.

---

## 1. Authorization e confini persona

### P0 — CommunicationCampaign accessibile a receptionist

**File:** `routes/backoffice.php:62-65` / `app/Livewire/Backoffice/Communications/CommunicationCampaign.php:45`

**Problema:** La route `/backoffice/communications/campaign` non è inclusa nel gruppo `middleware('role:gestore|trainer')`. Il componente `CommunicationCampaign::send()` non ha alcun check di ruolo interno. Receptionist può inviare campagne email/SMS massive a tutti i tesserati.

**Impatto:** Invio non autorizzato di comunicazioni (email, SMS) a intera base tesserati. Il job `SendCampaignMessages` viene dispatchato senza restrizioni.

**Fix proposto:** Aggiungere la route al gruppo `role:gestore|trainer`. Aggiungere gate sidebar. Valutare se receptionist debba avere accesso in sola lettura (es. solo vedere i template).

---

### P0 — AvailabilityManager accessibile a receptionist

**File:** `routes/backoffice.php:63` / `app/Livewire/Backoffice/Calendar/AvailabilityManager.php:79-105`

**Problema:** Route `/backoffice/calendar/availability` fuori dal gruppo `role:gestore|trainer`. I metodi `addSlot()`, `deleteSlot()`, `addOverride()`, `deleteOverride()` non hanno check di ruolo. Il componente usa `Auth::id()` come `trainer_id`, quindi receptionist creerebbe slot di disponibilità intestati a sé stesso (non a un trainer), inquinando i dati.

**Impatto:** Creazione/cancellazione slot disponibilità trainer non autorizzata. Record `trainer_availabilities` con `trainer_id` di un receptionist.

**Fix proposto:** Aggiungere la route al gruppo `role:gestore|trainer`. Aggiungere gate sidebar.

---

### P1 — GroupClassManager: mutazioni accessibili a receptionist

**File:** `routes/backoffice.php:65` / `app/Livewire/Backoffice/Calendar/GroupClassManager.php:88-172`

**Problema:** Route `/backoffice/group-classes` accessibile a tutti (incluso receptionist). I metodi `save()` (crea/modifica corsi), `deleteClass()`, `removeParticipant()` non hanno check di ruolo. Receptionist può creare corsi intestandoli a qualsiasi trainer, modificarli, cancellarli, rimuovere partecipanti.

**Impatto:** Gestione non autorizzata del calendario corsi collettivi (creazione, modifica, cancellazione). La cancellazione di un corso con partecipanti imposta `status=cancelled` e blocca definitivamente il corso.

**Fix applicato:** `save()` e `deleteClass()` limitati a `gestore|trainer` con `abort_unless` (commit `3f3d79e`).

**Decisione dominio (2026-08-19):** `removeParticipant()` intenzionalmente accessibile a receptionist — gestione iscrizioni ai corsi è operazione front-desk.

---

### P1 — TrainerCalendar.cancelBooking() senza authorization check

**File:** `app/Livewire/Backoffice/Calendar/TrainerCalendar.php:221-240`

**Problema:** `cancelBooking(int $bookingId)` chiama `PtBookingService::cancel()` senza verificare: (a) che l'utente abbia il ruolo appropriato; (b) che la prenotazione appartenga al trainer autenticato. Il commento in route suggerisce che receptionist gestisca il calendario, ma la cancellazione senza ownership check consente a chiunque di cancellare qualsiasi PT booking tramite ID.

**Impatto:** Receptionist (o qualunque ruolo backoffice) può cancellare PT booking altrui. La notifica all'atleta e al trainer viene inviata automaticamente dall'observer.

**Fix proposto:** Aggiungere `abort_unless($booking->trainer_id === Auth::id() || Auth::user()->hasRole('gestore'), 403)` prima del `cancel()`.

---

### P1 — BookingList.cancel() senza role check

**File:** `app/Livewire/Backoffice/Calendar/BookingList.php:75-95`

**Problema:** `cancel()` non verifica che l'utente abbia il ruolo adatto per annullare la prenotazione. Il metodo `confirm()` (riga 66) ha un filtro parziale per trainer_id quando non è gestore, ma `cancel()` usa `PtBooking::findOrFail()` senza ownership check.

**Impatto:** Receptionist può annullare qualsiasi PT booking.

**Fix proposto:** Aggiungere ownership check analogo a quello di `confirm()` anche in `cancel()`.

---

### P1 — Nessun check certificato medico nel check-in (gap di dominio)

**File:** `app/Livewire/Backoffice/Access/AccessLogList.php:55-79` (`registerAccess()`)

**Problema:** Il flusso di check-in verifica abbonamento attivo e accessi residui, ma NON verifica la validità del certificato medico (`medical_cert_expiry`). Un tesserato con certificato scaduto o mancante può fare check-in liberamente.

**Impatto:** Se il dominio prevede che l'accesso sia bloccato senza certificato valido (obbligo legale in molte strutture sportive), questa mancanza espone la palestra a responsabilità.

**Fix proposto:** Aggiungere controllo in `registerAccess()`:
```php
$member = Member::findOrFail($this->checkinMemberId);
if (! $member->has_medical_cert_valid) {
    $this->checkinError = 'Certificato medico scaduto o mancante. Accesso non consentito.';
    return;
}
```
**CHECKPOINT RICHIESTO:** Applicare solo dopo conferma esplicita — impatta il flusso operativo quotidiano.

---

### P2 — BookingList.confirm() chiamabile da receptionist senza effetto reale ma semanticamente errato

**File:** `app/Livewire/Backoffice/Calendar/BookingList.php:55-70`

**Problema:** `confirm()` è un metodo pubblico Livewire chiamabile da qualsiasi utente autenticato nel backoffice. Il filtro `trainer_id = Auth::id()` per i non-gestore non restituirà risultati per un receptionist (che non ha prenotazioni intestate a sé), ma il metodo è esposto senza role check esplicito.

**Impatto:** Basso — nessuna mutazione effettiva per receptionist. Però è logicamente sbagliato e potrebbe diventare un problema se la logica cambia.

**Fix proposto:** Aggiungere `abort_unless(Auth::user()->hasAnyRole(['gestore', 'trainer']), 403)` in `confirm()`.

---

## 2. Dominio e logica

### P2 — Nessuna ricerca per nome in SubscriptionList

**File:** `app/Livewire/Backoffice/Subscriptions/SubscriptionList.php` / view

**Problema:** La lista abbonamenti ha solo il filtro per stato (attivo/in scadenza/scaduto) ma non una ricerca per nome tesserato. In una palestra anche piccola, trovare l'abbonamento di uno specifico tesserato richiede navigare la lista o andare su Tesserati.

**Impatto:** UX degradata per il front-desk, rallentamento operativo.

**Fix proposto:** Aggiungere `public string $search = ''` e `->when($this->search, fn($q) => $q->whereHas('member', fn($mq) => $mq->where(...)))` nella query, con debounce 300ms.

---

### P3 — SubscriptionList: badge status in inglese

**File:** `resources/views/livewire/backoffice/subscriptions/subscription-list.blade.php:27`

**Problema:** `ucfirst($sub->status)` mostra "Active", "Expired", "Suspended", "Cancelled". Tutta la UI è in italiano.

**Fix proposto:**
```php
$label = match($sub->status) {
    'active'    => 'Attivo',
    'expired'   => 'Scaduto',
    'suspended' => 'Sospeso',
    'cancelled' => 'Cancellato',
    default     => ucfirst($sub->status),
};
```

---

### P3 — Nessun Form Request per operazioni receptionist

**File:** Tutti i componenti Members, Subscriptions, Access

**Problema:** Le convenzioni CLAUDE.md specificano "Form Request per validazione, mai inline nei controller/componenti". Tutta la validazione è con `rules()` / `#[Validate]` nei componenti Livewire.

**Impatto:** Deviazione da convenzione architetturale. Non introduce vulnerabilità ma rende la validazione meno testabile in isolamento.

**Fix proposto:** Bassa priorità — refactor graduale solo se richiesto. Non applicare in questa fase.

---

## 3. Sicurezza

### P2 — MemberList mostra link "Profilo allenamento" a receptionist

**File:** `resources/views/livewire/backoffice/members/member-list.blade.php:62-66`

**Problema:** Il link a `backoffice.athletes.profile` è mostrato incondizionatamente se `$member->user_id` è valorizzato. La route è riservata a `gestore|trainer`. Receptionist vede il link ma ottiene 403 al click.

**Fix proposto:** Wrappare il link in `@if(auth()->user()->hasAnyRole(['gestore', 'trainer']))`.

---

### P2 — MemberList mostra link "Modifica" a receptionist (UX incongruente)

**File:** `resources/views/livewire/backoffice/members/member-list.blade.php:59-61`

**Problema:** Il link di modifica è visibile a tutti i ruoli. Il receptionist può aprire il form di modifica, compilarlo, ma all'invio riceve un 403 (`abort_unless` in `save()` al ramo update). Confuso e non intuitivo.

**Fix proposto:** Wrappare il link di modifica in `@if(auth()->user()->hasAnyRole(['gestore', 'trainer']))`. Alternativa: mostrare bottone disabilitato con tooltip "Solo gestore/trainer".

---

### P2 — Sidebar AdminLTE mostra voci inaccessibili a receptionist

**File:** `config/adminlte.php:369-401`

**Problema:**
- "Disponibilità" (`backoffice/calendar/availability`) → non gatata → receptionist la vede e può accedervi
- "Campagne" (`backoffice/communications/campaign`) → non gatata → receptionist vede e può accedere

**Fix proposto:** Aggiungere gate per queste voci. Esempio:
```php
['text' => 'Disponibilità', ..., 'can' => 'manage-trainer-availability'],
['text' => 'Campagne', ..., 'can' => 'send-campaigns'],
```
Definire i gate in `AppServiceProvider::defineGates()`.

---

## 4. UI e coerenza AdminLTE

### P2 — wire:loading mancante su "Registra accesso" (modale check-in)

**File:** `resources/views/livewire/backoffice/access/access-log-list.blade.php:28-33`

**Problema:** Il bottone "Confirma accesso" ha `wire:loading.attr="disabled"` ma il bottone "Registra accesso" che apre la modale non ha feedback visivo. Se la connessione è lenta, il doppio click è possibile.

**Fix proposto:** `wire:loading.attr="disabled"` + spinner sul bottone "Registra accesso" nel card-header.

Nota: il bottone interno "Conferma accesso" ha già `wire:loading.attr="disabled"` e spinner — corretto.

---

### P3 — AccessLogList: nessun filtro per assenza di certificato medico

**File:** `app/Livewire/Backoffice/Access/AccessLogList.php:render()`

**Problema:** Il registro accessi mostra solo data/ora, tesserato, piano, receptionist. Non è visibile se il tesserato aveva certificato valido al momento dell'accesso.

**Fix proposto:** Aggiungere colonna "Cert." nella tabella accessi con badge verde/rosso basato su `member.medical_cert_expiry` alla data dell'accesso. (Minore, basso impatto operativo.)

---

## 5. Performance

### P2 — LIKE su colonne non indicizzate (first_name, last_name)

**File:** `app/Livewire/Backoffice/Members/MemberList.php:25-29` / `app/Livewire/Backoffice/Access/AccessLogList.php:73-76`

**Problema:** Le query di ricerca usano `WHERE first_name LIKE '%...%'` e `WHERE last_name LIKE '%...%'` senza indice. Con LIKE a prefisso `%` un indice B-tree non è efficace, ma un indice può comunque aiutare MySQL per ricerche con prefisso noto (`'Mario%'`). Il vero problema è l'assenza di qualsiasi indice su queste colonne.

**Impatto:** Full table scan su `members` per ogni keystroke. Con qualche migliaio di tesserati la risposta degraderà.

**Fix proposto:** Aggiungere indici:
```php
// migration: add_search_indexes_to_members_table
$table->index(['last_name', 'first_name']); // ordine utile per ordinamento cognome
```
Alternativa a lungo termine: full-text search con `MATCH ... AGAINST`.

---

### P3 — SubscriptionForm carica tutti i tesserati attivi in memoria

**File:** `app/Livewire/Backoffice/Subscriptions/SubscriptionForm.php:68`

**Problema:** `Member::where('is_active', true)->...->get()` carica l'intera collection in memoria per popolare la `<select>`. Con molti tesserati diventa lento.

**Fix proposto:** Convertire il select in ricerca live (typeahead) con wire:model + query paginata, simile al selettore tesserato nel check-in.

---

## Riepilogo priorità

| ID | Priorità | Area | Titolo |
|---|---|---|---|
| 1 | P0 | Auth | CommunicationCampaign accessibile a receptionist |
| 2 | P0 | Auth | AvailabilityManager accessibile a receptionist |
| 3 | P1 | Auth | GroupClassManager: save/delete senza role check |
| 4 | P1 | Auth | TrainerCalendar.cancelBooking() senza ownership check |
| 5 | P1 | Auth | BookingList.cancel() senza role check |
| 6 | P1 | Dominio | Check-in senza verifica certificato medico (gap di dominio — CHECKPOINT) |
| 7 | P2 | Auth | BookingList.confirm() callable da receptionist |
| 8 | P2 | UI | MemberList: link "Profilo allenamento" visibile a receptionist |
| 9 | P2 | UI | MemberList: link "Modifica" visibile a receptionist |
| 10 | P2 | UI | Sidebar: voci "Disponibilità" e "Campagne" non gatate |
| 11 | P2 | UI | wire:loading mancante su pulsante apertura modale check-in |
| 12 | P2 | Dominio | SubscriptionList: nessuna ricerca per nome |
| 13 | P2 | Performance | LIKE senza indice su first_name/last_name |
| 14 | P3 | UI | SubscriptionList: badge status in inglese |
| 15 | P3 | Performance | SubscriptionForm: get() di tutti i tesserati attivi |
| 16 | P3 | Arch | Nessun Form Request per operazioni receptionist |
| 17 | P3 | UI | AccessLogList: colonna "Cert." mancante nel registro accessi |

### Stato finale (post Fase 2-3)

| # | Stato | Note |
|---|---|---|
| 1-2 | ✅ applicato | route + gate sidebar |
| 3 | ✅ applicato | `save()` + `deleteClass()` — `removeParticipant()` intenzionalmente lasciato al receptionist (front-desk) |
| 4-7 | ✅ applicato | ownership/role check |
| 6 | ✅ applicato | confermato dall'utente come bloccante |
| 8-11, 13-14 | ✅ applicato | UI + perf |
| 12, 15-17 | ⏳ backlog | nuova feature / refactor / cosmético |

---

*Generato: 2026-08-19. Chiuso: 2026-08-19.*
