# Test Funzionali — Iron Gym (R09–R31)

Guida ai test manuali per verificare tutte le funzionalità introdotte da R09 a R31.

## Setup ambiente demo

```bash
php artisan migrate:fresh --seed
```

Per i test manuali delle aree CLS/CHK/EXP/REG (piano `docs/testing/r09-plus-functional-test-plan.md`) aggiungere anche il seeder funzionale:

```bash
php artisan db:seed --class=FunctionalTestSeeder
```

**Account disponibili dopo il seed:**

| Ruolo | Email | Password | Note |
|---|---|---|---|
| Gestore | `admin@admin.admin` | `admin` | |
| Trainer 1 | `trainer@trainer.trainer` | `trainer` | |
| Trainer 2 | `trainer2@trainer.trainer` | `trainer` | |
| Receptionist | `receptionist@receptionist.receptionist` | `receptionist` | |
| Atleta demo | `atleta@atleta.atleta` | `atleta` | |
| Giovanni Ferrari | `giovanni.ferrari@example.com` | `atleta` | |
| Alessia Colombo | `alessia.colombo@example.com` | `atleta` | cert scaduto |
| Marco Ricci | `marco.ricci@example.com` | `atleta` | |
| Federica Esposito | `federica.esposito@example.com` | `atleta` | in waitlist yoga |
| Davide Martini | `davide.martini@example.com` | `atleta` | |

**Account aggiuntivi da `FunctionalTestSeeder` (password: `demo1234`):**

| Nome | Email | Scenario |
|---|---|---|
| Carlo Accessi | `carlo.accessi@functional-test.demo` | Abbonamento a ingressi esauriti (`accesses_remaining=0`) — TC-CHK-004 |
| Stefano NoAbb | `stefano.noabb@functional-test.demo` | Nessun abbonamento — TC-CHK-003 |
| Giulia Scadenza | `giulia.scadenza@functional-test.demo` | Abbonamento in scadenza a 5 giorni — TC-EXP-002/005 |

---

## R09 — Corsi Collettivi (GroupClass → ClassSchedule → ClassOccurrence)

### R09 Step 1 — Schema e struttura

> 🔐 Login: `admin@admin.admin` / `admin`

- [ ] Vai a `/backoffice/group-classes/catalog` → verifica lista corsi (Functional Training, Spinning, Yoga, Circuit Training, ecc.)
- [ ] Fai clic su un corso → verifica campi: Nome, Slug, Descrizione, Durata, Capienza, Stanza, Colore, Attivo
- [ ] Crea un nuovo corso: Nome "Pilates Avanzato", Durata 60 min, Capienza 10 → Salva → verifica comparsa in lista
- [ ] Vai a `/backoffice/group-classes/schedules` → verifica palinsesto settimanale
- [ ] Aggiungi un palinsesto: corso "Pilates Avanzato", Mercoledì, 07:00, Trainer Luca Bianchi → Salva
- [ ] Verifica che il palinsesto sia listato con data inizio e stato Attivo

### R09 Step 2 — Prenotazione atleta con prerequisiti

> 🔐 Login: `atleta@atleta.atleta` / `atleta`

- [ ] Vai a `/athlete/booking` → verifica lista occorrenze future disponibili
- [ ] Tenta prenotazione su un corso con abbonamento attivo → deve procedere senza errori
- [ ] Verifica badge "Confermato" dopo prenotazione
- [ ] Tenta prenotazione su corso già prenotato → deve mostrare errore di duplicato

### R09 Step 3 — Attendance tracking

> 🔐 Login: `admin@admin.admin` / `admin`  *(oppure `trainer@trainer.trainer` / `trainer`)*

- [ ] Vai a `/backoffice/group-classes` → individua un'occorrenza "completed" (settimana scorsa)
- [ ] Fai clic sull'occorrenza → verifica lista partecipanti con badge Presente/Assente
- [ ] Marca un partecipante come "No-show" → verifica che lo status cambi a `no_show`
- [ ] Completa un'occorrenza "planned" → verifica cambio status a `completed`

### R09 Step 4 — Notifica cancellazione corso

> 🔐 Login: `admin@admin.admin` / `admin`

- [ ] Individua un'occorrenza futura con partecipanti
- [ ] Cancella l'occorrenza → verifica status `cancelled`

> 🔐 Poi: `atleta@atleta.atleta` / `atleta`

- [ ] Vai a `/athlete/notifications` → verifica notifica "corso cancellato"

### R09 Step 5 — Catalogo corsi e dashboard atleta

> 🔐 Login: `admin@admin.admin` / `admin`

- [ ] `/backoffice/group-classes/catalog` → crea/modifica corso → verifica CRUD completo
- [ ] Verifica sidebar backoffice: sottomenu "Corsi" con 3 voci (Occorrenze / Palinsesto / Catalogo)

> 🔐 Poi: `atleta@atleta.atleta` / `atleta`

- [ ] Dashboard atleta → verifica card "Prossimi corsi" con al massimo 3 occorrenze future prenotate

### R09 Step 6 — Finestre di prenotazione e cancellazione

> 🔐 Login: `atleta@atleta.atleta` / `atleta`

- [ ] Tenta prenotare un corso la cui finestra di prenotazione non è ancora aperta → deve mostrare errore con data apertura
- [ ] Prenota un corso futuro → tenta di cancellare entro la finestra gratuita → deve procedere
- [ ] Se la cancellazione è fuori finestra gratuita → deve mostrare avviso

> 🔐 Poi: `admin@admin.admin` / `admin`

- [ ] Rimuovi un partecipante da un'occorrenza (pulsante "Rimuovi") → verifica status `cancelled_by_gym`
- [ ] Verifica che il primo in lista d'attesa venga promosso a "Confermato"

---

## R10 — Centro Notifiche Atleta

> 🔐 Login: `atleta@atleta.atleta` / `atleta`

- [ ] Vai a `/athlete/notifications` → verifica lista con: promemoria corso, scadenza abbonamento, messaggio ricevuto, cancellazione corso (dati dal seed)
- [ ] Verifica badge numero notifiche non lette nella sidebar
- [ ] Fai clic su "Segna tutte come lette" → badge deve azzerarsi
- [ ] Fai clic su una singola notifica → deve diventare letta (sfondo cambia)
- [ ] Sidebar: link `/athlete/notifications` raggiungibile dal menu

---

## R11 — Promemoria Corsi Collettivi

> 🔐 Login: `atleta@atleta.atleta` / `atleta`

- [ ] Centro notifiche → verifica presenza notifica tipo `class_reminder` con testo "Domani hai [corso] alle [ora]"
- [ ] Notifica class_reminder deve avere icona specifica (distinguibile da altri tipi)

> 🔐 Terminale (nessun login richiesto)

- [ ] `php artisan schedule:list` → verifica `SendClassReminders` schedulato `dailyAt('08:00')`
- [ ] `php artisan classes:send-reminders --sync` → esegui manualmente → verifica log senza errori

---

## R12 — Periodization Engine

> 🔐 Login: `trainer@trainer.trainer` / `trainer`  *(oppure `admin@admin.admin` / `admin`)*

- [ ] Vai a `/backoffice/mesocycles` → seleziona un mesociclo atleta
- [ ] Verifica tab "Volume Landmarks" → mostra MEV/MAV/MRV per gruppo muscolare
- [ ] Fai clic "Applica progressione" → verifica che i carichi aumentino nella settimana successiva
- [ ] Fai clic "Forza deload" → verifica che la settimana attuale diventi deload

> 🔐 Login: `trainer2@trainer.trainer` / `trainer`  *(trainer diverso dall'atleta)*

- [ ] Tenta di accedere al mesociclo di un atleta non assegnato → deve ottenere 403

> 🔐 Login: `trainer@trainer.trainer` / `trainer`

- [ ] `/backoffice/athletes/{id}/volume-landmarks` → modifica un landmark → Salva → verifica persistenza
- [ ] "Ripristina default" → verifica reset ai valori di default

---

## R13 — Sezione Abbonamento Profilo Atleta

> 🔐 Login: `atleta@atleta.atleta` / `atleta`

- [ ] Vai a `/athlete/profile` → verifica tab "Abbonamento" visibile
- [ ] Sezione mostra: nome piano, data inizio, data scadenza, badge stato (Attivo/Scaduto/Sospeso)

> 🔐 Poi: `alessia.colombo@example.com` / `atleta`

- [ ] Vai a `/athlete/profile` → badge "Scaduto" rosso (abbonamento scaduto da 10 giorni)

> 🔐 Poi: `davide.martini@example.com` / `atleta`

- [ ] Vai a `/athlete/profile` → messaggio "Nessun abbonamento attivo"

---

## R14 — Sessioni PT nella Dashboard Atleta

> 🔐 Login: `atleta@atleta.atleta` / `atleta`

- [ ] Dashboard `/athlete/dashboard` → verifica card "Prossime sessioni PT"
- [ ] Devono comparire le prenotazioni PT con status `confirmed` o `pending` con data futura
- [ ] Ogni riga mostra: data, orario, nome trainer
- [ ] Se nessuna PT futura → card mostra messaggio vuoto, non errore

---

## R15 — BookingList e CommunicationCampaign

> 🔐 Login: `admin@admin.admin` / `admin`

- [ ] `/backoffice/bookings` → verifica lista prenotazioni PT
- [ ] Conferma una prenotazione `pending` → status diventa `confirmed`
- [ ] Cancella una prenotazione → status diventa `cancelled`
- [ ] Ripristina una prenotazione cancellata → status torna a `pending`

> 🔐 Poi: `trainer@trainer.trainer` / `trainer`

- [ ] Tenta conferma di prenotazione non sua → deve ottenere 403

> 🔐 Poi: `receptionist@receptionist.receptionist` / `receptionist`

- [ ] Tenta conferma prenotazione → deve ottenere 403

> 🔐 Login: `admin@admin.admin` / `admin`

- [ ] `/backoffice/communications/campaign` → compila: oggetto, corpo messaggio, filtro atleti
- [ ] Invia campagna → verifica che il job venga accodato (nessun errore UI)
- [ ] Tenta invio con campo "corpo" vuoto → verifica errore di validazione

---

## R16 — Tab Sessioni PT nel Profilo Atleta

> 🔐 Login: `atleta@atleta.atleta` / `atleta`

- [ ] Profilo atleta `/athlete/profile` → tab "Sessioni PT"
- [ ] Sezione "Prossime" mostra prenotazioni future confirmed/pending in ordine cronologico
- [ ] Sezione "Storico" mostra ultime 10 sessioni completed/cancelled/no_show in ordine inverso
- [ ] Ogni riga mostra: data, orario, trainer, badge status

---

## R17 — Tab Misurazioni nel Profilo Atleta

> 🔐 Login: `atleta@atleta.atleta` / `atleta`

- [ ] Profilo atleta → tab "Misurazioni"
- [ ] Mostra ultime 5 misurazioni con: data, peso (kg), BF% se disponibile, vita, petto
- [ ] Link "Vedi tutte" porta a pagina completa misurazioni

> 🔐 Poi: `giovanni.ferrari@example.com` / `atleta`  *(nessuna misurazione nel seed)*

- [ ] Profilo → tab "Misurazioni" → messaggio "Nessuna misurazione registrata"

---

## R18 — Tab Record nel Profilo Atleta

> 🔐 Login: `atleta@atleta.atleta` / `atleta`

- [ ] Profilo atleta → tab "Record"
- [ ] Mostra ultimi 5 PR e1RM con: esercizio, valore (kg), data
- [ ] I record devono includere Panca Piana, Stacco, Squat (dati dal seed R09R31DemoSeeder)
- [ ] Link "Vedi tutti" porta a pagina completa PR

> 🔐 Poi: `giovanni.ferrari@example.com` / `atleta`  *(nessun PR nel seed)*

- [ ] Profilo → tab "Record" → messaggio vuoto appropriato

---

## R19 — Tab Sessioni nel Profilo Atleta

> 🔐 Login: `atleta@atleta.atleta` / `atleta`

- [ ] Profilo atleta → tab "Sessioni"
- [ ] Mostra ultime 5 sessioni completate/saltate: nome sessione, data, durata, badge status
- [ ] Badge "Completata" verde, "Saltata" grigio
- [ ] Link "Vedi storico" porta a pagina storico sessioni

---

## R20 — Tab Messaggi nel Profilo Atleta

> 🔐 Login: `atleta@atleta.atleta` / `atleta`

- [ ] Profilo atleta → tab "Messaggi"
- [ ] Mostra ultimi 5 messaggi (mittente, anteprima testo, data)
- [ ] Messaggi non letti da Trainer 2 (Elena Russo) hanno badge "Non letto"
- [ ] Contatore messaggi non letti visibile nell'header della tab
- [ ] Link "Vai ai messaggi" porta a `/athlete/messages`

> 🔐 Poi: `davide.martini@example.com` / `atleta`  *(nessun messaggio nel seed)*

- [ ] Profilo → tab "Messaggi" → messaggio "Nessun messaggio"

---

## R21 — Tab Corsi nel Profilo Atleta

> ⚙️ Prerequisito: feature flag `group_classes` attivo (default dopo seed)

> 🔐 Login: `atleta@atleta.atleta` / `atleta`

- [ ] Profilo atleta → tab "Corsi" visibile
- [ ] Sezione "Prossimi" mostra corsi prenotati futuri con badge Confermato/Lista d'attesa
- [ ] Sezione "Storico" mostra corsi passati con data e nome corso
- [ ] Atleta in lista d'attesa → badge "Lista d'attesa" giallo

> 🔐 Poi: `admin@admin.admin` / `admin`

- [ ] Backoffice → Admin → Feature Flags → disattiva `group_classes`

> 🔐 Poi: `atleta@atleta.atleta` / `atleta`

- [ ] Profilo → tab "Corsi" deve essere scomparso

---

## R22 — Pannello Scadenze Backoffice

> 🔐 Login: `admin@admin.admin` / `admin`

- [ ] `/backoffice/members/expiry` → verifica due tabelle: "Certificati in scadenza" e "Abbonamenti in scadenza"
- [ ] Tabella certificati: mostra Alessia Colombo (cert scaduto) e Marco Ricci (cert in scadenza a 20 giorni)
- [ ] Tabella abbonamenti: mostra Federica Esposito (abbonamento scade a 15 giorni)
- [ ] Filtro "Finestra giorni" (default 30 per cert, 7 per abbonamenti) → cambia numero → verifica aggiornamento lista
- [ ] Filtro ricerca → cerca "Ferrari" → filtra risultati
- [ ] Voce "Scadenze" nella sidebar backoffice visibile e funzionante

> 🔐 Poi: `receptionist@receptionist.receptionist` / `receptionist`

- [ ] `/backoffice/members/expiry` → deve essere accessibile (receptionist autorizzato)

> 🔐 Poi: `trainer@trainer.trainer` / `trainer`

- [ ] `/backoffice/members/expiry` → deve restituire 403

---

## R23 — Widget Scadenze nella Dashboard Backoffice

> 🔐 Login: `admin@admin.admin` / `admin`

- [ ] Dashboard `/backoffice/dashboard` → verifica card "Scadenze imminenti"
- [ ] Card mostra: contatore certificati in scadenza (30gg) e contatore abbonamenti in scadenza (7gg)
- [ ] Link nella card porta a `/backoffice/members/expiry`

> 🔐 Poi: `receptionist@receptionist.receptionist` / `receptionist`

- [ ] Dashboard → stessa card visibile

---

## R24 — Check-in Rapido

> 🔐 Login: `receptionist@receptionist.receptionist` / `receptionist`

- [ ] Sidebar → voce "Check-in" → `/backoffice/checkin`
- [ ] Cerca "Atleta" nel campo → deve comparire "Atleta Test" nella lista risultati
- [ ] Seleziona il tesserato → verifica: nome, abbonamento attivo, cert. medico valido, badge OK
- [ ] Premi "Registra ingresso" → conferma check-in → compare nell'elenco accessi odierni in fondo alla pagina

> 🔐 Stessa sessione (receptionist), cerca: `Alessia`

- [ ] Cerca Alessia Colombo → deve mostrare AVVISO certificato medico scaduto
- [ ] Check-in non bloccato se l'abbonamento è valido (avviso ≠ blocco)
- [ ] Se anche l'abbonamento è scaduto → bottone "Registra ingresso" disabilitato o errore

> 🔐 Poi: `trainer@trainer.trainer` / `trainer`

- [ ] `/backoffice/checkin` → deve restituire 403

---

## R25 — Rinnovo Abbonamento Rapido

> 🔐 Login: `admin@admin.admin` / `admin`

- [ ] `/backoffice/subscriptions` → individua un abbonamento scaduto/in scadenza
- [ ] Fai clic su "Rinnova" (icona freccia) → redirect a form con `member_id` e `plan_id` pre-popolati
- [ ] Verifica che `started_at` sia pre-impostata alla scadenza del precedente abbonamento
- [ ] Verifica che `expires_at` sia calcolata automaticamente in base alla durata del piano
- [ ] Salva → nuovo abbonamento creato → verifica in lista

> 🔐 Poi: `receptionist@receptionist.receptionist` / `receptionist`

- [ ] Stessa procedura → il receptionist può rinnovare (autorizzato)

---

## R26 — Tab Accessi nel Profilo Atleta

> 🔐 Login: `atleta@atleta.atleta` / `atleta`

- [ ] Profilo atleta → tab "Accessi"
- [ ] Mostra ultimi 5 ingressi: data, ora, nome piano, badge "Entrata"
- [ ] Dati dal seed DemoSeeder (10 accessi negli ultimi 7 giorni per Atleta Test e Giovanni Ferrari)

> 🔐 Poi: `davide.martini@example.com` / `atleta`  *(nessun accesso nel seed)*

- [ ] Profilo → tab "Accessi" → messaggio "Nessun accesso registrato"

---

## R27 — Sospensione Abbonamento

> 🔐 Login: `admin@admin.admin` / `admin`

- [ ] `/backoffice/subscriptions` → filtro "Sospesi" → mostra abbonamento sospeso dal seed R09R31DemoSeeder
- [ ] Individua un abbonamento attivo → bottone pausa (`fa-pause`) → finestra `wire:confirm` → conferma
- [ ] Status diventa `suspended`, icona cambia in `fa-play`
- [ ] Premi `fa-play` su abbonamento sospeso → riattiva → status torna `active`

> 🔐 Poi: `trainer@trainer.trainer` / `trainer`

- [ ] Lista abbonamenti → bottone pausa/play NON visibile

> 🔐 Poi: `receptionist@receptionist.receptionist` / `receptionist`

- [ ] Lista abbonamenti → bottone pausa/play NON visibile

---

## R28 — Note Interne Tesserato

> 🔐 Login: `admin@admin.admin` / `admin`

- [ ] `/backoffice/members` → Giovanni Ferrari → icona `fa-sticky-note` nella colonna azioni
- [ ] Hover sull'icona → tooltip mostra "Note presenti"
- [ ] Marco Ricci → stessa icona (note dal seed R09R31DemoSeeder)
- [ ] Atleta Test → NESSUNA icona note
- [ ] Fai clic su "Modifica" per Giovanni Ferrari → campo "Note" pre-popolato con testo del seed
- [ ] Modifica nota → Salva → verifica persistenza e icona ancora presente in lista

---

## R29 — Export CSV Abbonamenti

> 🔐 Login: `admin@admin.admin` / `admin`

- [ ] `/backoffice/subscriptions` → bottone "Esporta CSV" → download `abbonamenti-YYYY-MM-DD.csv`
- [ ] Apri con Excel/LibreOffice → colonne: Cognome, Nome, Email, Piano, Inizio, Scadenza, Stato
- [ ] Verifica BOM UTF-8 (caratteri accentati corretti in Excel) e separatore `;`
- [ ] Applica filtro "Attivi" → esporta → CSV contiene solo attivi
- [ ] Applica filtro "Sospesi" → esporta → CSV contiene solo sospesi

> 🔐 Poi: `trainer@trainer.trainer` / `trainer`

- [ ] GET `/backoffice/subscriptions/export` → deve restituire 403

> 🔐 Poi: `receptionist@receptionist.receptionist` / `receptionist`

- [ ] GET `/backoffice/subscriptions/export` → deve restituire 403

---

## R30 — Export CSV Tesserati

> 🔐 Login: `admin@admin.admin` / `admin`

- [ ] `/backoffice/members` → bottone "Esporta CSV" → download `tesserati-YYYY-MM-DD.csv`
- [ ] Colonne: Cognome, Nome, Email, Telefono, Abbonamento, Scadenza abb., Cert. medico, Attivo · BOM UTF-8 · `;`
- [ ] Filtro ricerca "Ferrari" → esporta → solo Giovanni Ferrari nel CSV
- [ ] Filtro cert "Scaduto" → esporta → Alessia Colombo nel CSV

> 🔐 Poi: `trainer@trainer.trainer` / `trainer`

- [ ] GET `/backoffice/members/export` → deve restituire 403

> 🔐 Poi: `receptionist@receptionist.receptionist` / `receptionist`

- [ ] GET `/backoffice/members/export` → deve restituire 403

---

## R31 — Statistiche PT nella Dashboard Gestore

> 🔐 Login: `admin@admin.admin` / `admin`

- [ ] `/backoffice/reports/manager` → scorrere in fondo alla pagina
- [ ] Tabella "Sessioni PT completate per trainer" visibile
- [ ] Luca Bianchi: 2 sessioni completate · Elena Russo: 1 sessione (dal seed BookingDemoSeeder)
- [ ] Cambia selettore "Dal / Al" a un mese senza sessioni → tabella mostra "Nessuna sessione PT nel periodo"
- [ ] Sessioni con status `confirmed`, `pending`, `cancelled` → escluse dal conteggio

> 🔐 Poi: `trainer@trainer.trainer` / `trainer`

- [ ] `/backoffice/reports/manager` → deve restituire 403

> 🔐 Poi: `receptionist@receptionist.receptionist` / `receptionist`

- [ ] `/backoffice/reports/manager` → deve restituire 403

---

## FIX02 — Idempotenza seeder e overlap PT+corso atleta (2026-08-27)

### FIX02-A — OpeningHoursSeeder idempotente (F-01 / F-04)

> 🔐 Terminale

- [ ] Esegui `php artisan migrate:fresh --seed` → nessun errore
- [ ] Esegui di nuovo `php artisan db:seed --class=OpeningHoursSeeder` → deve completare senza errori (nessun duplicato, nessuna `UniqueConstraintViolationException`)
- [ ] DB → tabella `opening_hours` → righe invariate tra la prima e seconda esecuzione

### FIX02-B — Overlap PT + corso collettivo (F-02)

> Prerequisito: `FunctionalTestSeeder` eseguito (crea occorrenza L: Trainer overlap `now+5` 14:00 con PtBooking trainer1)

> 🔐 Login: `atleta@atleta.atleta` / `atleta`

- [ ] Vai a `/athlete/booking` → individua l'occorrenza nello stesso slot di una sessione PT confermata
- [ ] Tenta prenotazione → deve mostrare errore: "Hai già una sessione PT confermata in questo orario."
- [ ] Prenotazione NON creata (verifica assenza in `class_bookings`)

> 🔐 Login: `admin@admin.admin` / `admin` (test lato backoffice)

- [ ] Individua occorrenza con slot libero (nessuna PT sovrapposta per l'atleta)
- [ ] Iscrivi atleta → deve procedere senza errori

---

## Test trasversali — Ruoli e permessi

| Route | Login da usare per test 403 | Gestore | Trainer | Receptionist | Atleta |
|---|---|---|---|---|---|
| `/backoffice/members/expiry` | `trainer@trainer.trainer` | ✅ | ❌ 403 | ✅ | ❌ |
| `/backoffice/checkin` | `trainer@trainer.trainer` | ✅ | ❌ 403 | ✅ | ❌ |
| `/backoffice/subscriptions/export` | `trainer@trainer.trainer` | ✅ | ❌ 403 | ❌ 403 | ❌ |
| `/backoffice/members/export` | `trainer@trainer.trainer` | ✅ | ❌ 403 | ❌ 403 | ❌ |
| `/backoffice/reports/manager` | `trainer@trainer.trainer` | ✅ | ❌ 403 | ❌ 403 | ❌ |
| `/backoffice/group-classes/catalog` | `atleta@atleta.atleta` | ✅ | ✅ | ✅ | ❌ |
| `/athlete/notifications` | `admin@admin.admin` | ❌ | ❌ | ❌ | ✅ |
| `/athlete/profile` | `admin@admin.admin` | ❌ | ❌ | ❌ | ✅ |
| `/athlete/booking` | `admin@admin.admin` | ❌ | ❌ | ❌ | ✅ |

---

## Test trasversali — Feature flag `group_classes`

> 🔐 Login: `atleta@atleta.atleta` / `atleta`  *(flag attivo per default dopo seed)*

- [ ] Tab "Corsi" visibile in `/athlete/profile`
- [ ] `/athlete/booking` accessibile
- [ ] Dashboard atleta → card corsi visibile

> 🔐 Poi: `admin@admin.admin` / `admin`

- [ ] Backoffice → Admin → Feature Flags → disattiva `group_classes`

> 🔐 Poi: `atleta@atleta.atleta` / `atleta`

- [ ] Tab "Corsi" scomparsa dal profilo
- [ ] Riattiva il flag come gestore → tab torna visibile

---

## Test trasversali — Paginazione e performance

> 🔐 Login: `admin@admin.admin` / `admin`

- [ ] `/backoffice/members` con 6+ tesserati → nessun N+1 query (Laravel Debugbar in dev)
- [ ] `/backoffice/subscriptions` → filtri cambiano risultati istantaneamente (Livewire live)
- [ ] `/backoffice/checkin` ricerca → risultati appaiono con < 500ms

> 🔐 Login: `atleta@atleta.atleta` / `atleta`

- [ ] Centro notifiche → caricamento < 1s con 10+ notifiche

---

## Checklist pre-demo

- [ ] `php artisan migrate:fresh --seed` completato senza errori
- [ ] `php artisan db:seed --class=FunctionalTestSeeder` completato (scenari CLS/CHK/EXP/REG)
- [ ] `php artisan serve` attivo su porta 8000
- [ ] Feature flags verificati: `financial_reports` ON, `periodization_engine` ON, `group_classes` ON
- [ ] Almeno 1 abbonamento sospeso in DB (da R09R31DemoSeeder)
- [ ] Almeno 2 tesserati con note: Giovanni Ferrari, Marco Ricci
- [ ] `atleta@atleta.atleta` ha PR, messaggi, notifiche, accessi, sessioni PT e corsi prenotati
- [ ] `carlo.accessi@functional-test.demo` esiste con `accesses_remaining=0` (TC-CHK-004)
- [ ] Occorrenza Yoga Full (`now+3`) con 3 confirmed + Federica in waitlist (TC-CLS-009/012)
- [ ] Occorrenza L (`now+5` 14:00) con PtBooking trainer1 sovrapposta (REG-003 / FIX02-B)
- [ ] Sessioni PT `completed` nel mese corrente per R31 (BookingDemoSeeder)
- [ ] Queue worker attivo: `php artisan queue:work redis --queue=default`
