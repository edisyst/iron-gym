# Test funzionali — Receptionist

**Credenziali:** receptionist@receptionist.receptionist / receptionist  
**URL base:** http://iron-gym.test/backoffice

---

## 1. Auth

- [ ] Login → redirect a `/backoffice/dashboard`
- [ ] Logout → redirect a `/login`
- [ ] Accesso a `/backoffice/templates` (area trainer/gestore) → permesso o 403?

> Annotare quali voci del menu sono visibili vs nascoste rispetto agli altri ruoli.

---

## 2. Dashboard

- [ ] Dashboard si carica senza errori
- [ ] KPI visibili (o area limitata?)

---

## 3. Membri

URL: `/backoffice/members`

- [ ] Lista membri si carica
- [ ] Ricerca per nome funziona
- [ ] Crea nuovo membro → salva
- [ ] Modifica membro esistente → salva
- [ ] Badge scadenza certificato medico visibile

---

## 4. Abbonamenti

URL: `/backoffice/subscriptions`

- [ ] Lista abbonamenti si carica
- [ ] **Nuovo abbonamento**:
  - [ ] Select membro popolata
  - [ ] Select piano popolata (Mensile, Trimestrale)
  - [ ] Selezione piano → data scadenza calcolata automaticamente
  - [ ] Salva → abbonamento in lista con stato corretto
- [ ] Filtro per stato (attivo/scaduto/tutti) funziona

---

## 5. Accessi

URL: `/backoffice/access-logs`

- [ ] Lista accessi si carica con storico
- [ ] Filtro per data funziona
- [ ] Filtro per membro funziona
- [ ] Registra accesso manuale → appare in lista con timestamp
- [ ] Accesso registrato aggiorna contatore ingressi dell'abbonamento (se a ingressi)

---

## 6. Prenotazioni PT

URL: `/backoffice/pt-bookings`

- [ ] Lista prenotazioni si carica
- [ ] Filtro per stato (pending/confirmed/cancelled) funziona
- [ ] Conferma prenotazione pending → stato diventa confirmed
- [ ] Annulla prenotazione → stato diventa cancelled

---

## 7. Corsi collettivi

URL: `/backoffice/group-classes`

- [ ] Lista corsi si carica
- [ ] Iscrizioni a corso visibili
- [ ] Gestione waitlist (se corso pieno)

---

## Note generali

> Inserire qui differenze di permessi rispetto agli altri ruoli, voci di menu mancanti, errori 403 inattesi.
