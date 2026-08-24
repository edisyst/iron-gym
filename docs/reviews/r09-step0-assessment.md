# R09 Step 0 — Assessment corsi di gruppo

**Data:** 2026-08-23  
**Branch:** develop  
**Scope:** read-only, nessuna modifica al codice

---

## 1. Inventario esistente

### 1.1 Migration

#### `group_classes` — `database/migrations/2026_06_20_000003_create_group_classes_table.php`

| Colonna | Tipo | Note |
|---|---|---|
| `id` | bigIncrements | PK |
| `trainer_id` | foreignId → `users` | RESTRICT on delete |
| `name` | string(128) | |
| `description` | text nullable | |
| `scheduled_at` | dateTime | data+ora inizio |
| `duration_minutes` | smallInteger unsigned | default 60 |
| `max_participants` | tinyInteger unsigned | default 10 |
| `status` | enum(scheduled/completed/cancelled) | default scheduled |
| `cancellation_reason` | text nullable | |
| `created_at` / `updated_at` | timestamps | |

Indici: `idx_class_scheduled` su `scheduled_at`, `idx_class_status` su `status`.  
No soft delete. No `slug`. No `room`, `color`, `is_active`, `default_capacity`.

#### `class_bookings` — `database/migrations/2026_06_20_000004_create_class_bookings_table.php`

| Colonna | Tipo | Note |
|---|---|---|
| `id` | bigIncrements | PK |
| `class_id` | foreignId → `group_classes` | CASCADE on delete |
| `member_id` | foreignId → `members` | RESTRICT on delete |
| `status` | enum(confirmed/waitlisted/cancelled) | default confirmed |
| `position` | tinyInteger unsigned nullable | posizione waitlist |
| `created_at` | timestamp nullable | no `updated_at` (`UPDATED_AT = null`) |

Unique: `uq_class_member` su `(class_id, member_id)`.  
Indice: `idx_class_booking_status` su `(class_id, status)`.  
No `attended_at`. No `booked_by`. No `cancelled_by_athlete`/`cancelled_by_gym`/`no_show` status. Non punta a una `class_occurrence_id`.

---

### 1.2 Model GroupClass — `app/Models/GroupClass.php`

**Fillable:** `trainer_id`, `name`, `description`, `scheduled_at`, `duration_minutes`, `max_participants`, `status`, `cancellation_reason`.  
**Casts:** `scheduled_at` → datetime.  
**Relazioni:** `trainer()` BelongsTo User, `bookings()` HasMany ClassBooking, `confirmedBookings()` HasMany (where status=confirmed), `waitlist()` HasMany (where status=waitlisted, orderBy position).  
**Accessor (stile getX):** `confirmed_count`, `available_spots`, `is_full`.  
**Soft delete:** assente.  
**Scopes:** nessuno.

---

### 1.3 Model ClassBooking — `app/Models/ClassBooking.php`

**Fillable:** `class_id`, `member_id`, `status`, `position`.  
**Casts:** nessuno esplicito.  
**Relazioni:** `groupClass()` BelongsTo GroupClass, `member()` BelongsTo Member.  
**Azioni di dominio:** `promote()` — promuove da waitlisted a confirmed e decrementa posizioni successive.  
**Soft delete:** assente.

---

### 1.4 ClassBookingService — `app/Services/ClassBookingService.php`

Metodi pubblici:

| Metodo | Firma | Comportamento |
|---|---|---|
| `enroll` | `(GroupClass, Member): ClassBooking` | Controlla doppia iscrizione, usa `lockForUpdate()` su GroupClass, crea confirmed se posto libero, altrimenti crea waitlisted con posizione progressiva. Lancia `BookingException` se già iscritto/in waitlist. |
| `cancel` | `(ClassBooking): void` | Imposta status=cancelled, se era confirmed e il corso è futuro chiama `promoteFirstWaitlisted`. |

Metodo privato: `promoteFirstWaitlisted(GroupClass)` — recupera primo waitlisted, chiama `promote()` sul model, dispatcha `NotifyWaitlistPromotion` job via `afterResponse()`.

**Logica waitlist:** presente e funzionante per il modello attuale. Nessun controllo su abbonamento attivo o certificato medico.

---

### 1.5 Componenti Livewire

#### `app/Livewire/Athlete/Booking.php`
Componente full-page atleta. Gestisce due tab: PT e Corsi.  
Metodi corsi: `enrollClass(int $classId)`, `cancelClassBooking(int $bookingId)`.  
Entrambi gate su `Feature::active('group_classes')` con `abort_unless`.  
`render()` carica `futureClasses`, `myClassBookings`, `myEnrolledClassIds`.  
Nessun controllo su abbonamento attivo o scadenza certificato in questi metodi.

#### `app/Livewire/Backoffice/Calendar/GroupClassManager.php`
CRUD completo backoffice: lista con filtri e paginazione, form creazione/modifica, pannello dettaglio con iscritti e waitlist, eliminazione (fisica se no partecipanti, soft-cancel altrimenti).  
Protezione inline: `abort_unless(Auth::user()->hasAnyRole(['gestore', 'trainer']), 403)`.  
Nessuna Policy dedicata.

#### `app/Livewire/Backoffice/Calendar/TrainerCalendar.php`
Calendario settimanale. Mostra corsi collettivi (`scheduled`) nello stesso calendario di PT e disponibilità. Supporta apertura modale dettaglio per tipo `class`. Colore eventi corsi: `#f59e0b`.

#### `app/Livewire/Backoffice/Admin/FeatureFlagManager.php`
Lista il flag `group_classes` nell'array dei flag gestibili.

---

### 1.6 Route

**Backoffice** (`routes/backoffice.php`):
- `GET /backoffice/group-classes` → `GroupClassManager` — name: `backoffice.group-classes.index`
- Nessun middleware aggiuntivo oltre l'auth+ruolo-base backoffice. La route è sotto il gruppo generale (accessibile a gestore, trainer, receptionist). Il controllo di ruolo è inline nel componente (`abort_unless hasAnyRole(['gestore','trainer'])`).
- `GET /backoffice/calendar` → `TrainerCalendar` (include corsi nel calendario)
- `GET /backoffice/bookings` → `BookingList` (lista prenotazioni PT, non corsi)

**Atleta** (`routes/athlete.php`):
- `GET /athlete/bookings` → `Booking` — name: `athlete.bookings` — tab PT + tab Corsi (condizionato da feature flag)

Nessuna route API per corsi. Nessuna route per occorrenze o palinsesto.

---

### 1.7 Policy / Gate

Nessuna Policy Eloquent (`GroupClassPolicy`) trovata in `app/Policies/`.

Gate definiti in `AppServiceProvider`:
- `view-group-classes` → `Feature::active('group_classes')` — usato dalla sidebar AdminLTE per mostrare la voce "Corsi collettivi".

Controllo accesso scrittura: `abort_unless` inline nel componente Livewire (`GroupClassManager`).

---

### 1.8 Factory e Seeder

**Factory:**
- `database/factories/GroupClassFactory.php` — name casuale da lista fissa, scheduled_at +1..+30 giorni, status sempre `scheduled`.
- `database/factories/ClassBookingFactory.php` — default confirmed; stati `waitlisted()` e `cancelled()` come named states.

**Seeder:** nessun seeder dedicato per GroupClass/ClassBooking trovato in `database/seeders/`.

---

### 1.9 Test

File: `tests/Feature/BookingTest.php`

4 test riguardano ClassBookingService:
1. Membro in waitlist se corso pieno.
2. Cancellazione confirmed promuove primo in waitlist.
3. Doppia iscrizione allo stesso corso lancia `BookingException`.
4. (Implicitamente) Race condition gestita da `lockForUpdate`.

File: `tests/Feature/ReceptionistCheckinTest.php`  
Usa `GroupClass` e `ClassBooking` factory per il test di accesso `GroupClassManager.removeParticipant()` via Livewire. Verifica che receptionist non possa `save()` né `deleteClass()` ma possa `removeParticipant()` (3 test).

Nessun test per la view atleta (`Booking` Livewire), nessun test per il feature flag condizionante il tab Corsi.

---

### 1.10 Feature flag `group_classes`

**Definizione:** `AppServiceProvider::boot()` riga 57:
```php
Feature::define('group_classes', function (): bool {
    return (bool) config('features.group_classes_enabled', false);
});
```
Flag globale (non per-user), default `false`.

**Gate correlato:** `view-group-classes` (sidebar AdminLTE).

**Dove viene letto nel codice:**
- `app/Livewire/Athlete/Booking.php` — `abort_unless(Feature::active('group_classes'))` in `enrollClass()` e `cancelClassBooking()`.
- `resources/views/livewire/athlete/booking.blade.php` — direttiva `@feature('group_classes')` avvolge il tab "Corsi" e l'intero blocco del tab content.

**UI condizionata:** il tab "Corsi" nella vista atleta è completamente nascosto se flag OFF. L'utente vede solo il tab PT.

**View del tab Corsi:** funzionante a livello di rendering — mostra lista corsi futuri con posti disponibili/pieno, bottone Iscriviti / lista d'attesa, lista iscrizioni attive, cancellazione iscrizione. Tutto cablato su `scheduled_at` della singola istanza `GroupClass` (no palinsesto, no occorrenze).

---

## 2. Inventario area confinante

### 2.1 Migration TrainerAvailability — `database/migrations/2026_06_20_000001_create_trainer_availability_table.php`

| Colonna | Tipo | Note |
|---|---|---|
| `id` | bigIncrements | PK |
| `trainer_id` | foreignId → `users` | RESTRICT |
| `day_of_week` | tinyInteger unsigned nullable | 0=lun..6=dom |
| `specific_date` | date nullable | override puntuale |
| `start_time` | time | |
| `end_time` | time | |
| `is_available` | boolean | false = blocco |
| `notes` | string nullable | |
| `created_at` / `updated_at` | timestamps | |

Vincolo XOR (MySQL only): esattamente uno tra `day_of_week` e `specific_date`.  
Indici: `(trainer_id, day_of_week)`, `(trainer_id, specific_date)`.

**Rappresentazione dello slot temporale:** separata in `day_of_week` (o `specific_date`) + `start_time` + `end_time`. Nessun `datetime` unificato. Durata implicita dalla differenza `end - start`.

### 2.2 Migration PtBooking — `database/migrations/2026_06_20_000002_create_pt_bookings_table.php`

| Colonna | Tipo | Note |
|---|---|---|
| `id` | bigIncrements | PK |
| `trainer_id` | foreignId → `users` | RESTRICT |
| `member_id` | foreignId → `members` | RESTRICT |
| `session_id` | unsignedInteger nullable → `training_sessions` | NULL on delete |
| `booked_date` | date | |
| `start_time` | time | |
| `end_time` | time | |
| `status` | enum(pending/confirmed/cancelled/completed/no_show) | |
| `cancelled_by` | foreignId nullable → `users` | NULL on delete |
| `cancellation_reason` | text nullable | |
| `cancellation_deadline` | dateTime nullable | |
| `notes` | text nullable | |
| `created_at` / `updated_at` | timestamps | |

Indici: `(trainer_id, booked_date)`, `member_id`, `status`.

**Rappresentazione slot:** `booked_date` (date) + `start_time` + `end_time` (time). Stessa convenzione di TrainerAvailability.

### 2.3 PtBookingService — `app/Services/PtBookingService.php`

Metodo `book()`: verifica sovrapposizione con PtBooking esistenti (`start_time < end2 AND end_time > start1`), verifica che lo slot sia nei `getAvailableSlots()` del trainer, calcola `cancellation_deadline` = 24h prima, crea record confermato.

**Controllo sovrapposizione** — a livello trainer, non a livello atleta. Non controlla se l'atleta ha già un PT nello stesso orario né se ha un corso sovrapposto.

Metodo `cancel()`: solo cambia status, salva `cancelled_by` e `cancellation_reason`. Non promuove nessuno (PT non ha waitlist).

### 2.4 PtBookingObserver — `app/Observers/PtBookingObserver.php`

Reagisce a `saved` e `deleted`: invalida cache `slots:{trainer_id}:{date}` e flush tag `kpi`. **Nessuna notifica** agli utenti tramite questo observer — la notifica è delegata altrove (nota: CLAUDE.md dichiara che PtBookingObserver notifica atleta+trainer su conferma/cancellazione, ma il file effettivo contiene solo logica di cache invalidation).

### 2.5 Legame utente-trainer e utente-atleta

- `users` ha ruoli via spatie (`atleta`, `trainer`, `gestore`, `receptionist`).
- `members` ha colonna `user_id` nullable → collega il tesserato all'account app.
- `pt_bookings` usa `member_id` per l'atleta, `trainer_id` per il trainer (FK su `users`).
- `class_bookings` usa `member_id` per l'atleta (nessun riferimento diretto al `user_id`).
- `group_classes` usa `trainer_id` (FK su `users`).

### 2.6 Verifica abbonamento attivo e certificato medico

**Model Member** (`app/Models/Member.php`):
- `activeSubscription()` HasOne — scope: `status=active AND expires_at >= today`, `latestOfMany('started_at')`. Riutilizzabile come relazione o come query scope.
- `has_medical_cert_valid` accessor (stile getX): `medical_cert_expiry !== null AND isFuture()`.

**Dove vengono usati oggi:** `CommunicationTemplate` li legge per sostituire variabili nelle campagne. **Non vengono controllati in `ClassBookingService::enroll()` né in `Booking::enrollClass()`**.

---

## 3. Gap analysis

| # | Feature target R09 | Stato |
|---|---|---|
| 1 | `GroupClass` come definizione del corso (name, slug, description, duration_minutes, default_capacity, room, color, is_active) | **Presente ma incompleta** — mancano `slug`, `room`, `color`, `is_active`, `default_capacity`; `max_participants` è per-istanza non default |
| 2 | `class_trainer` pivot abilitazione trainer-corso | **Assente** — oggi il trainer è FK diretta sulla singola istanza; non esiste concetto di "corso abilitato per trainer X" |
| 3 | `ClassSchedule` — palinsesto ricorrente (group_class_id, weekday, start_time, trainer_id default, valid_from, valid_until, is_active) | **Assente** |
| 4 | `ClassOccurrence` — istanza datata (group_class_id, class_schedule_id nullable, date, start_time, end_time, trainer_id, capacity, status planned/cancelled/completed) | **Assente** — oggi `group_classes` è allo stesso tempo definizione E singola istanza datata (monolite) |
| 5 | `ClassBooking` rivista: punta a `class_occurrence_id`, status arricchito con `cancelled_by_athlete`/`cancelled_by_gym`/`no_show`, `attended_at`, `booked_by` | **Assente** — status attuale: confirmed/waitlisted/cancelled; FK su `class_id` (GroupClass monolite); mancano `attended_at`, `booked_by` |
| 6 | `config/classes.php` con `booking_opens_days`, `booking_closes_minutes`, `free_cancel_hours`, `generation_horizon_days` | **Assente** — nessun file `config/classes.php` |
| 7 | Command schedulato per materializzare occorrenze dal palinsesto, idempotente | **Assente** |
| 8 | Controllo conflitti bidirezionale trainer tra occorrenze corso e PtBooking | **Assente** — TrainerCalendar mostra entrambi ma non blocca la creazione di un PT sovrapposto a un corso |
| 9 | Controllo sovrapposizione oraria lato atleta tra corsi confermati e PtBooking confermati | **Assente** — né ClassBookingService né PtBookingService verifica l'altra entità |
| 10 | Corsi inclusi nell'abbonamento, nessun consumo ingressi | **Non applicabile** — l'abbonamento attuale non ha logica "consumo ingressi per corso"; i corsi non consumano ingressi oggi. Nessuna logica da rimuovere, ma nessun controllo che il requisito "abbonamento attivo" sia soddisfatto |
| 11 | Prerequisiti prenotazione: abbonamento attivo + certificato medico non scaduto | **Assente** — `ClassBookingService::enroll()` e `Booking::enrollClass()` non verificano né abbonamento né certificato. Gli strumenti esistono: `Member::activeSubscription()` e accessor `has_medical_cert_valid` |
| 12 | Backoffice CRUD corsi | **Presente** — `GroupClassManager` funzionante con lista, form create/edit, dettaglio iscritti, promozione waitlist |
| 13 | View atleta tab Corsi | **Presente** — rendering completo, condizionato da feature flag |
| 14 | Waitlist con promozione automatica | **Presente** — `ClassBookingService`, `ClassBooking::promote()`, `NotifyWaitlistPromotion` job |
| 15 | Test di unità/feature per ClassBookingService | **Presente parzialmente** — 4 test coprono enroll/cancel/waitlist, ma nessun test per prerequisiti abbonamento/certificato (non implementati) e nessun test del componente Livewire atleta |

---

## 4. Rischi migrazione dati esistenti

La migration `class_bookings` ha `class_id` FK con `cascadeOnDelete` verso `group_classes`. Se R09 introduce `ClassOccurrence` e riscrive `ClassBooking` con `class_occurrence_id`, i record esistenti in `class_bookings` che puntano a `group_classes.id` devono essere migrati o eliminati.

**Rischio concreto:**
- Se il database di produzione ha già record in `group_classes` e `class_bookings` (il flag `group_classes` è OFF, ma la tabella esiste dalla migrazione iniziale), un rename/drop di `class_id` in `class_bookings` richiede una data migration esplicita.
- La migration `down()` di `class_bookings` fa `dropIfExists`, non restaura nulla — va bene.
- Nessun record di produzione atteso (flag OFF, atleti non potevano iscriversi), ma lo step 1 deve includere un controllo `SELECT COUNT(*) FROM class_bookings` prima di procedere.

---

## 5. Raccomandazione

### Riscrivi, non migrare

La struttura esistente (`GroupClass` come istanza singola datata + `class_bookings` flat) è un'implementazione MVP a evento unico, non un sistema di palinsesto. Il target R09 richiede tre livelli (`GroupClass → ClassSchedule → ClassOccurrence`) che sono architetturalmente incompatibili con la struttura attuale.

**Motivazione concreta:**
1. `group_classes` mescola definizione del corso e singola occorrenza: non è possibile aggiungere `ClassSchedule` e `ClassOccurrence` come layer superiore senza separare queste responsabilità.
2. `class_bookings.class_id` punta a `group_classes` — deve puntare a `class_occurrences`. Non è un rename, è un cambio di semantica.
3. I campi mancanti su `group_classes` (`slug`, `room`, `color`, `is_active`, `default_capacity`) sono tutti aggiungibili con una migration additive, ma perdono senso se la tabella diventa "definizione del corso" invece di "evento singolo".

**Piano consigliato per Step 1:**
1. Aggiungere colonne mancanti a `group_classes` (slug, room, color, is_active, default_capacity) — additive, non distruttiva.
2. Creare `class_trainer` pivot.
3. Creare `class_schedules`.
4. Creare `class_occurrences`.
5. Aggiungere colonne a `class_bookings` (`class_occurrence_id`, `attended_at`, `booked_by`) e migrare `class_id → class_occurrence_id` (con `down()` che ripristina).
6. Aggiornare `ClassBooking.status` enum con i nuovi valori.
7. Aggiornare models e service, aggiungere config/classes.php, aggiungere command.

Il codice esistente di `ClassBookingService` (enroll/cancel/waitlist) è solido e riutilizzabile con adattamenti minimali una volta che `ClassBooking` punta a `ClassOccurrence`. Non va buttato, va adattato.

Il componente `GroupClassManager` dovrà essere riscritto o esteso per gestire il palinsesto, ma il pattern Livewire è corretto.
