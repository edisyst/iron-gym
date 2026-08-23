# DOC01 — Fase 5A: Findings docs/test/

Data: 2026-08-23

## Matrice permessi reale (sintesi)

Ricostruita da: `routes/backoffice.php`, `routes/athlete.php`, `AppServiceProvider` (Gate),
componenti Livewire (abort_unless in mount), `config/adminlte.php` (can:).

### Backoffice

| Route | Gestore | Trainer | Receptionist |
|---|---|---|---|
| /backoffice/dashboard | ✓ | ✓ | ✓ |
| /backoffice/search | ✓ | ✓ | ✓ |
| /backoffice/members | ✓ | ✓ | ✓ (crea OK; update → 403) |
| /backoffice/subscriptions | ✓ | ✓ | ✓ |
| /backoffice/access-logs | ✓ | ✓ | ✓ |
| /backoffice/settings/opening-hours | ✓ edit | view-only | ✓ edit |
| /backoffice/exercises, /exercises/{slug} | ✓ | ✓ | **403** (component) |
| /backoffice/exercises/create, /edit | ✓ | ✓ | **403** (route) |
| /backoffice/templates | ✓ | ✓ | **403** (component) |
| /backoffice/templates/create, /builder | ✓ | ✓ | **403** (route) |
| /backoffice/mesocycles | ✓ | ✓ | **403** (component) |
| /backoffice/mesocycles/assign, /{id} | ✓ | ✓ | **403** (route) |
| /backoffice/athletes/{id}/* | ✓ | ✓ * | **403** (route) |
| /backoffice/calendar | ✓ | ✓ | ✓ |
| /backoffice/calendar/availability | ✓ | ✓ | **403** (route+component) |
| /backoffice/bookings | ✓ | ✓ | ✓ |
| /backoffice/group-classes | ✓ ¹ | ✓ ¹ | ✓ ¹ |
| /backoffice/communications/campaign | ✓ | ✓ | **403** (route) |
| /backoffice/reports/training | ✓ | ✓ | **403** (route+gate) |
| /backoffice/reports/manager | ✓ | **403** | **403** |
| /backoffice/reports/financial | ✓ ² | **403** | **403** |
| /backoffice/admin/* | ✓ | **403** | **403** |

*Trainer: ownership check — solo atleti assegnati a quel trainer  
¹Visibile in menu solo se feature flag `group_classes` = ON (gate `view-group-classes`)  
²Route sempre accessibile a gestore; feature flag `financial_reports` controlla contenuto

**URL inesistenti referenziati nei test doc:**
- `/backoffice/subscription-plans` — non esiste; nessun CRUD UI per SubscriptionPlan
- `/backoffice/pt-bookings` — sbagliato; route reale: `/backoffice/bookings`
- `/backoffice/plate-inventory` — sbagliato; route reale: `/backoffice/admin/plate-inventory`
- `/backoffice/trainer-availability` — sbagliato; route reale: `/backoffice/calendar/availability`

### Area atleta

Tutte le route `/athlete/*` esclusive per ruolo `atleta`.
Feedback post-sessione: **embedded** in WorkoutSession (`$showFeedback=true`) — nessun URL separato.
Profilo atleta: `/athlete/profile` (non `/profile`).

Nav desktop: Home | Allenamento (+ Esercizi sub) | Progressi (+ Record sub) | Profilo (+ Prenota, Messaggi sub) | Esci  
Bottom nav: **4 tab** — Home | Allenamento | Progressi | Profilo

---

## Findings per documento

### docs/test/README.md

| # | Severità | Finding |
|---|---|---|
| R1 | BASSO | Link a file corretti; credenziali corrette; nessuna modifica necessaria |

**Esito: OK.** Nessuna correzione richiesta.

---

### docs/test/01-gestore.md

| # | Severità | Finding |
|---|---|---|
| G1 | MEDIO | **Sezione 5 "Piani abbonamento" — route `/backoffice/subscription-plans` inesistente.** Nessun CRUD UI per SubscriptionPlan; i piani sono creati via `pilot:init` o direttamente in DB. Intera sezione 5 da sostituire con nota. |
| G2 | BASSO | **Sezione 11 URL sbagliato:** `/backoffice/plate-inventory` → `/backoffice/admin/plate-inventory` |
| G3 | BASSO | **Sezione 13 URL sbagliato:** `/backoffice/pt-bookings` → `/backoffice/bookings` |
| G4 | BASSO | **Sezione 14 "Corsi"** non menziona gate `view-group-classes` / feature flag `group_classes`. Con flag OFF la voce non appare nel menu. |
| G5 | BASSO | **Gap numerazione:** salta sezione 12 (da 11 va a 13). |
| G6 | BASSO | **Sezioni mancanti per il gestore:** reports/manager, reports/financial (con flag), reports/training, admin/feature-flags, admin/feedback, settings/opening-hours, communications/campaign. Nessuna copertura test. |

---

### docs/test/02-trainer.md

| # | Severità | Finding |
|---|---|---|
| T1 | MEDIO | **Sezione 6 "Lista atleti"** — afferma "Lista atleti accessibile" ma non esiste route `/backoffice/athletes` (lista). Atleti si raggiungono via membri/abbonamenti o dal mesociclo. Test case fuorviante. |
| T2 | BASSO | **Sezione 8 URL sbagliato:** `/backoffice/trainer-availability` → `/backoffice/calendar/availability` |
| T3 | BASSO | **Sezione 1** pone domanda aperta su `/backoffice/members`: il trainer ha pieno accesso (gestore|trainer|receptionist). Dichiarare comportamento atteso: accesso OK. |
| T4 | BASSO | **Sezioni mancanti:** report allenamento (/backoffice/reports/training), campagne (/backoffice/communications/campaign), calendario e prenotazioni PT. Nessuna copertura. |
| T5 | BASSO | **403 espliciti mancanti:** trainer ottiene 403 su reports/manager, reports/financial, admin/*. Non documentato. |

---

### docs/test/03-receptionist.md

| # | Severità | Finding |
|---|---|---|
| RC1 | MEDIO | **Sezione 3 "Modifica membro esistente → salva"** — implica funzionante. Realtà: receptionist apre il form di modifica ma `MemberForm.save()` → 403 per update (abort_unless gestore\|trainer). La voce deve dichiarare il comportamento corretto: apertura form OK, salvataggio → 403. |
| RC2 | BASSO | **Sezione 1** pone domanda aperta su `/backoffice/templates`: risposta è 403 (TemplateList component abort_unless). Dichiarare comportamento atteso. |
| RC3 | BASSO | **Sezione 6 URL sbagliato:** `/backoffice/pt-bookings` → `/backoffice/bookings` |
| RC4 | BASSO | **Sezione 7 "Corsi collettivi"** — manca nota gate `view-group-classes` / feature flag `group_classes`. Con flag OFF la voce di menu è nascosta. |
| RC5 | BASSO | **Sezioni mancanti:** settings/opening-hours (receptionist può modificare), access-logs, calendar, accesso negato esplicito a exercises/templates/mesocycles/analytics/communications. |

---

### docs/test/04-atleta.md

| # | Severità | Finding |
|---|---|---|
| A1 | MEDIO | **Sezione 6 URL `/athlete/session/{id}/feedback` inesistente.** Feedback embedded in WorkoutSession; l'URL rimane `/athlete/session/{id}` anche con il form feedback aperto. Redirect a `/recap` avviene solo dopo save/skip. |
| A2 | BASSO | **Sezione 15 URL `/profile`** — sbagliato; deve essere `/athlete/profile`. |
| A3 | BASSO | **Sezione 2 nav desktop/mobile — nomi e conteggio sbagliati.**  Bottom nav: doc lista 5 voci ("Oggi, Storico, Volume, Record, Profilo") → reale: 4 tab (Home, Allenamento, Progressi, Profilo). Desktop nav: label doc ("Oggi, Storico, Volume, Record...") → reale: "Home, Allenamento, Esercizi, Progressi, Record, Profilo, Prenota, Messaggi". |

---

## Piano interventi Fase 5B

Priorità: prima i finding MEDIO (G1, T1, RC1, A1), poi BASSO.

| File | Interventi |
|---|---|
| `01-gestore.md` | Rimuovi sezione 5 (subscription-plans); fix URL sezioni 11, 13; aggiungi nota flag sezione 14; aggiungi sezioni mancanti (reports, admin, communications, opening-hours) |
| `02-trainer.md` | Riscrivi sezione 6 (nessuna lista atleti route); fix URL sezione 8; chiarisci sezione 1; aggiungi sezioni mancanti |
| `03-receptionist.md` | Fix sezione 3 (modifica membro → 403 su save); chiarisci sezione 1 (403 esercizi); fix URL sezione 6; aggiungi nota flag sezione 7; aggiungi sezioni mancanti |
| `04-atleta.md` | Fix sezione 6 (rimuovi URL inesistente); fix sezione 15 URL; fix sezione 2 nav labels+conteggio |
| `README.md` | Nessuna modifica necessaria |

Un commit per file (4 commit totali).
