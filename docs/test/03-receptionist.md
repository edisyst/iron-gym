# Test funzionali — Receptionist

**Credenziali:** receptionist@receptionist.receptionist / receptionist  
**URL base:** http://iron-gym.test/backoffice

Il receptionist copre le operazioni front-desk: anagrafica, abbonamenti, accessi, prenotazioni.
Non ha accesso alle aree training (esercizi, template, mesocicli, analytics atleta) né a
comunicazioni, feature flags o inventario dischi.

---

## 1. Auth

- [ ] Login → redirect a `/backoffice/dashboard`
- [ ] Logout → redirect a `/login`
- [ ] `/backoffice/templates` → **403** (TemplateList.mount() abort_unless gestore|trainer)
- [ ] `/backoffice/exercises` → **403** (ExerciseList.mount() abort_unless gestore|trainer)
- [ ] `/backoffice/mesocycles` → **403** (MesocycleList.mount() abort_unless gestore|trainer)

---

## 2. Dashboard

- [ ] Dashboard si carica senza errori

---

## 3. Membri

URL: `/backoffice/members`

- [ ] Lista membri si carica
- [ ] Ricerca per nome funziona
- [ ] Paginazione funziona
- [ ] **Crea nuovo membro** → form aperto → salva → membro creato (receptionist può creare)
- [ ] **Apri membro esistente in modifica** → form aperto
- [ ] **Salva modifica** a membro esistente → **403** (MemberForm.save() abort_unless gestore|trainer per update)
- [ ] Badge scadenza certificato medico visibile
- [ ] Banner avviso certificato scaduto visibile nella home atleta (controllo receptionist al check-in)

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
- [ ] Tentativo check-in con certificato medico scaduto → blocco con messaggio di avviso
- [ ] Accesso registrato aggiorna contatore ingressi dell'abbonamento (se a ingressi)

---

## 6. Prenotazioni PT

URL: `/backoffice/bookings`

- [ ] Lista prenotazioni si carica
- [ ] Filtro per stato (pending/confirmed/cancelled) funziona
- [ ] Conferma prenotazione pending → stato diventa confirmed
- [ ] Annulla prenotazione → stato diventa cancelled

---

## 7. Corsi collettivi

URL: `/backoffice/group-classes`

> **Nota:** la voce di menu "Corsi collettivi" è visibile solo se feature flag `group_classes` = ON
> (gate `view-group-classes`). Con flag OFF la voce è nascosta nella sidebar ma la route è
> tecnicamente accessibile navigando direttamente.

- [ ] Lista corsi si carica (se flag ON)
- [ ] Iscrizioni a corso visibili
- [ ] Gestione waitlist (se corso pieno)
- [ ] Rimozione partecipante da corso → operazione consentita per il receptionist (front-desk)

---

## 8. Orari di apertura

URL: `/backoffice/settings/opening-hours`

- [ ] Pagina si carica (receptionist può modificare gli orari)
- [ ] Modifica orario → salva

---

## 9. 403 attesi (aree non accessibili al receptionist)

- [ ] `/backoffice/exercises` → 403
- [ ] `/backoffice/exercises/{slug}` → 403
- [ ] `/backoffice/templates` → 403
- [ ] `/backoffice/mesocycles` → 403
- [ ] `/backoffice/athletes/{id}/analytics` → 403
- [ ] `/backoffice/athletes/{id}/profile` → 403
- [ ] `/backoffice/calendar/availability` → 403
- [ ] `/backoffice/communications/campaign` → 403
- [ ] `/backoffice/reports/training` → 403
- [ ] `/backoffice/reports/manager` → 403
- [ ] `/backoffice/reports/financial` → 403
- [ ] `/backoffice/admin/feature-flags` → 403
- [ ] `/backoffice/admin/plate-inventory` → 403

---

## Note generali

> Inserire qui differenze di permessi rispetto agli altri ruoli, voci di menu mancanti, errori 403 inattesi.
