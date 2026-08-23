# Test funzionali — Trainer

**Credenziali:** trainer@trainer.trainer / trainer  
**URL base:** http://iron-gym.test/backoffice

Il trainer ha accesso alle stesse aree di gestionale (membri, abbonamenti, accessi) ma non
agli strumenti admin (inventario dischi, feature flags, report finanziario). Vede solo gli
atleti a lui assegnati per analytics e profilo (ownership check nei componenti).

---

## 1. Auth

- [ ] Login → redirect a `/backoffice/dashboard`
- [ ] Logout → redirect a `/login`
- [ ] `/backoffice/members` accessibile (gestore|trainer|receptionist): OK, nessun 403

---

## 2. Dashboard

- [ ] Dashboard si carica senza errori

---

## 3. Template schede

URL: `/backoffice/templates`

- [ ] Lista template visibile
- [ ] Crea nuovo template → builder
- [ ] Builder: tutte le funzioni accessibili (aggiungi sessione, esercizi, copia settimana, duplica)
- [ ] Modifica template esistente nel builder → salva

---

## 4. Assegna mesociclo

URL: `/backoffice/mesocycles/assign`

- [ ] Pagina accessibile
- [ ] Select atleta popolata
- [ ] Flusso completo step 1 → step 2 → conferma → redirect lista
- [ ] Mesociclo creato con status "active"

---

## 5. Lista mesocicli

URL: `/backoffice/mesocycles`

- [ ] Lista visibile con mesocicli assegnati
- [ ] Dettaglio mesociclo accessibile (`/backoffice/mesocycles/{id}`)

---

## 6. Atleti

> **Nota:** non esiste route `/backoffice/athletes` (lista atleti). Gli atleti si raggiungono
> tramite i link nel dettaglio mesociclo o nelle voci di menu. Il trainer vede solo gli atleti
> a lui assegnati (ownership check nei componenti — 403 se tenta accesso ad atleta di un altro trainer).

URL: `/backoffice/athletes/{athleteId}/analytics`

- [ ] Pagina analytics atleta assegnato si carica
- [ ] Volume landmarks dell'atleta visibili/modificabili
- [ ] Accesso ad atleta NON assegnato → 403

URL: `/backoffice/athletes/{athleteId}/profile` (storico sessioni embedded)

- [ ] Storico sessioni atleta visibile
- [ ] Score readiness visibile per sessioni con check (R07)
- [ ] Badge "sost. da [originale]" per esercizi sostituiti (R06)

---

## 7. Messaggistica con atleti

URL: `/backoffice/athletes/{athleteId}/messages`

- [ ] Thread messaggi si carica
- [ ] Invio messaggio funziona
- [ ] Messaggio appare lato atleta

---

## 8. Disponibilità PT

URL: `/backoffice/calendar/availability`

- [ ] Configurazione disponibilità settimanale accessibile
- [ ] Salvataggio disponibilità funziona

---

## 9. Prenotazioni PT e calendario

URL: `/backoffice/calendar`

- [ ] Calendario si carica
- [ ] Slot prenotati visibili

URL: `/backoffice/bookings`

- [ ] Lista prenotazioni si carica
- [ ] Filtro per stato funziona

---

## 10. Report allenamento

URL: `/backoffice/reports/training`

- [ ] Pagina accessibile al trainer (non è riservata al solo gestore)
- [ ] Dati allenamento atleti visibili

---

## 11. Campagne comunicazione

URL: `/backoffice/communications/campaign`

- [ ] Pagina accessibile al trainer
- [ ] Invio campagna funziona

---

## 12. 403 attesi (aree riservate al gestore)

- [ ] `/backoffice/reports/manager` → 403
- [ ] `/backoffice/reports/financial` → 403
- [ ] `/backoffice/admin/feature-flags` → 403
- [ ] `/backoffice/admin/feedback` → 403
- [ ] `/backoffice/admin/plate-inventory` → 403

---

## Note generali

> Inserire qui differenze di permessi rispetto al gestore, voci di menu mancanti, errori 403 inattesi.
