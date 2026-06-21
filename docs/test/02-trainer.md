# Test funzionali — Trainer

**Credenziali:** trainer@trainer.trainer / trainer  
**URL base:** http://iron-gym.test/backoffice

---

## 1. Auth

- [ ] Login → redirect a `/backoffice/dashboard`
- [ ] Logout → redirect a `/login`
- [ ] Accesso a `/backoffice/members` (area gestore/receptionist) → permesso o 403?

> Annotare quali voci del menu sono visibili vs nascoste rispetto al gestore.

---

## 2. Dashboard

- [ ] Dashboard si carica senza errori
- [ ] KPI visibili (o area limitata rispetto al gestore?)

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
- [ ] Dettaglio mesociclo accessibile

---

## 6. Atleti

- [ ] Lista atleti accessibile
- [ ] Pagina analytics atleta (`/backoffice/athletes/{id}/analytics`) si carica
- [ ] Volume landmarks dell'atleta visibili/modificabili

---

## 7. Messaggistica con atleti

URL: `/backoffice/athletes/{id}/messages`

- [ ] Thread messaggi si carica
- [ ] Invio messaggio funziona
- [ ] Messaggio appare lato atleta

---

## 8. Disponibilità PT

URL: `/backoffice/trainer-availability` (se esiste nel menu)

- [ ] Configurazione disponibilità settimanale accessibile
- [ ] Salvataggio disponibilità funziona

---

## Note generali

> Inserire qui differenze di permessi rispetto al gestore, voci di menu mancanti, errori 403 inattesi.
