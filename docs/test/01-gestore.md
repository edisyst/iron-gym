# Test funzionali — Gestore

**Credenziali:** admin@admin.admin / admin  
**URL base:** http://iron-gym.test/backoffice

---

## 1. Auth

- [ ] Login con credenziali corrette → redirect a `/backoffice/dashboard`
- [ ] Login con password errata → messaggio di errore visibile
- [ ] Logout tramite menu AdminLTE → redirect a `/login`
- [ ] Accesso diretto a `/backoffice/dashboard` senza login → redirect a `/login`

---

## 2. Dashboard KPI

URL: `/backoffice/dashboard`

- [ ] Pagina si carica senza errori
- [ ] Blocchi KPI visibili (abbonamenti attivi, ingressi oggi, ecc.)
- [ ] Filtro periodo (mese/trimestre/anno) cambia i valori mostrati
- [ ] Nessun errore 500 al cambio filtro

---

## 3. Membri

URL: `/backoffice/members`

- [ ] Lista membri si carica con dati del seeder
- [ ] Ricerca per nome filtra la lista in tempo reale
- [ ] Paginazione funziona (se ci sono più di 15 record)
- [ ] Click su **Nuovo membro** apre il form
- [ ] Crea nuovo membro con tutti i campi obbligatori → salvataggio e redirect alla lista
- [ ] Crea membro senza nome → messaggio di validazione visibile
- [ ] Click su membro esistente apre il form in modifica
- [ ] Modifica campo e salva → dati aggiornati in lista
- [ ] Certificato medico: data scadenza impostabile
- [ ] Badge "scaduto" visibile per certificati scaduti

---

## 4. Abbonamenti

URL: `/backoffice/subscriptions`

- [ ] Lista abbonamenti si carica
- [ ] Filtro per stato (attivo/scaduto) funziona
- [ ] **Nuovo abbonamento**: seleziona membro e piano → salva senza errori
- [ ] Data scadenza calcolata automaticamente dal piano scelto
- [ ] Abbonamento appare in lista con stato corretto

---

## 5. Piani abbonamento

URL: `/backoffice/subscription-plans`

- [ ] Lista piani si carica (Mensile, Trimestrale dal seeder)
- [ ] Crea nuovo piano con nome, prezzo, durata → salva
- [ ] Modifica piano esistente → aggiornato

---

## 6. Accessi

URL: `/backoffice/access-logs`

- [ ] Lista accessi si carica con i record del seeder (10 accessi demo)
- [ ] Filtro per data funziona
- [ ] Registra nuovo accesso manuale → appare in lista

---

## 7. Esercizi

URL: `/backoffice/exercises`

- [ ] Lista esercizi si carica (83 esercizi dal seeder)
- [ ] Ricerca per nome filtra in tempo reale
- [ ] Filtro per muscolo funziona
- [ ] Filtro per pattern motore funziona
- [ ] Click su esercizio → pagina dettaglio
- [ ] Dettaglio mostra muscoli con ruolo e percentuale contribuzione
- [ ] **Nuovo esercizio**: form si apre
- [ ] Crea esercizio con pattern compound → salva senza errori
- [ ] Crea esercizio senza selezionare né compound né joint → errore di validazione
- [ ] Crea esercizio con entrambi compound e joint → errore di validazione (XOR)

---

## 8. Template schede

URL: `/backoffice/templates`

- [ ] Lista template si carica
- [ ] Filtro per obiettivo funziona
- [ ] Filtro attivi/archiviati funziona
- [ ] **Nuovo template**: crea con nome, obiettivo, settimane, giorni → redirect al builder
- [ ] **Apri builder** su template esistente → carica builder
- [ ] **Duplica** template → apre builder della copia con nome "Copia di …"

### Builder

URL: `/backoffice/templates/{id}/builder`

- [ ] Builder si carica con tab settimane
- [ ] Click su tab "Settimana 2" → cambia contenuto (sessioni diverse)
- [ ] **Aggiungi sessione** → nuova card sessione appare
- [ ] Rinomina sessione (click sul nome e modifica) → nome aggiornato
- [ ] Ricerca esercizio (min. 2 caratteri) → risultati compaiono
- [ ] Aggiungi esercizio dalla ricerca → appare nella sessione
- [ ] Modifica serie, reps, RIR, riposo → valori salvati
- [ ] Drag & drop riordina esercizi nella sessione
- [ ] Checkbox "Raggruppa con successivo" → imposta superset
- [ ] Elimina esercizio → scompare con conferma
- [ ] Elimina sessione → scompare con conferma
- [ ] **Copia settimana**: seleziona settimana target → bottone "Copia" si attiva → click copia sessioni
- [ ] Sessioni copiate appaiono nella settimana target

---

## 9. Mesocicli

URL: `/backoffice/mesocycles`

- [ ] Lista mesocicli si carica
- [ ] Filtro per stato funziona
- [ ] **Assegna mesociclo** (`/backoffice/mesocycles/assign`):
  - [ ] Step 1: select atleta si carica con utenti ruolo `atleta`
  - [ ] Select template mostra template attivi
  - [ ] Filtro obiettivo filtra i template
  - [ ] Selezione template mostra preview sessioni
  - [ ] Click "Avanti" senza selezionare atleta → errore di validazione
  - [ ] Click "Avanti" con atleta e template → Step 2
  - [ ] Step 2: nome pre-compilato, campi modificabili
  - [ ] Click "Conferma e assegna" → redirect a lista con messaggio di successo
  - [ ] Mesociclo creato appare in lista con stato "active"

---

## 10. Atleti (backoffice)

URL: `/backoffice/athletes/{id}/analytics`

- [ ] Pagina analytics atleta si carica
- [ ] Volume landmarks visibili (se configurati)
- [ ] Misurazioni corporee visibili (se presenti)

URL: `/backoffice/athletes/{id}/sessions`

- [ ] Storico sessioni atleta visibile
- [ ] Score readiness visibile per sessioni con check (R07)
- [ ] Modulazione applicata indicata (es. "-5% accepted") (R07)
- [ ] Badge "sost. da [originale]" per esercizi sostituiti (R06)

---

## 11. Plate inventory (R02)

URL: `/backoffice/plate-inventory`

- [ ] Pagina si carica senza errori
- [ ] Lista dischi con peso, quantità paia, colore, stato attivo/inattivo
- [ ] **Aggiungi disco**: inserisci peso_kg, quantità, colore → salva
- [ ] Modifica disco esistente → aggiornato
- [ ] Toggle attivo/inattivo → disco escluso/incluso dal plate calculator
- [ ] Elimina disco → rimosso dalla lista

---

## 13. Prenotazioni PT

URL: `/backoffice/pt-bookings`

- [ ] Lista prenotazioni si carica
- [ ] Filtro per stato funziona

---

## 14. Corsi

URL: `/backoffice/group-classes`

- [ ] Lista corsi si carica
- [ ] Crea nuovo corso → salva

---

## 15. Messaggi

URL: `/backoffice/athletes/{id}/messages`

- [ ] Messaggistica con atleta si carica
- [ ] Invio messaggio → appare in lista

---

## Note generali

> Inserire qui osservazioni trasversali (es. lentezza, errori JS in console, testi errati)
