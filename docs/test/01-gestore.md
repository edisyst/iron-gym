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

> **Nota:** Non esiste una pagina di gestione piani abbonamento nel backoffice.
> I piani (Mensile, Trimestrale, ecc.) sono creati via `php artisan pilot:init`
> o direttamente tramite seeder/DB. Modifiche ai piani richiedono accesso diretto al DB.

---

## 5. Accessi

URL: `/backoffice/access-logs`

- [ ] Lista accessi si carica con i record del seeder (10 accessi demo)
- [ ] Filtro per data funziona
- [ ] Registra nuovo accesso manuale → appare in lista

---

## 6. Esercizi

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

## 7. Template schede

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

## 8. Mesocicli

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

## 9. Atleti (backoffice)

URL: `/backoffice/athletes/{athleteId}/analytics`

- [ ] Pagina analytics atleta si carica
- [ ] Volume landmarks visibili (se configurati)
- [ ] Misurazioni corporee visibili (se presenti)

URL: `/backoffice/athletes/{athleteId}/profile` (storico sessioni embedded in AthleteProfile)

- [ ] Storico sessioni atleta visibile
- [ ] Score readiness visibile per sessioni con check (R07)
- [ ] Modulazione applicata indicata (es. "-5% accepted") (R07)
- [ ] Badge "sost. da [originale]" per esercizi sostituiti (R06)

---

## 10. Inventario dischi (R02)

URL: `/backoffice/admin/plate-inventory`

- [ ] Pagina si carica senza errori
- [ ] Lista dischi con peso, quantità paia, colore, stato attivo/inattivo
- [ ] **Aggiungi disco**: inserisci peso_kg, quantità, colore → salva
- [ ] Modifica disco esistente → aggiornato
- [ ] Toggle attivo/inattivo → disco escluso/incluso dal plate calculator
- [ ] Elimina disco → rimosso dalla lista

---

## 11. Report allenamento

URL: `/backoffice/reports/training`

- [ ] Pagina si carica senza errori
- [ ] Dati per atleta visibili (sessioni, volume, progressione)
- [ ] Filtri funzionano (per atleta, periodo)

---

## 12. Report manager e finanziario

URL: `/backoffice/reports/manager`

- [ ] Dashboard manager si carica con KPI aggregati
- [ ] Grafici e metriche visibili

URL: `/backoffice/reports/financial`

> **Nota:** accessibile solo se feature flag `financial_reports` = ON (attivabile da Admin → Feature Flags).

- [ ] Con flag ON: pagina si carica con dati fatturato/abbonamenti
- [ ] Con flag OFF: pagina vuota o messaggio feature non attiva

---

## 13. Prenotazioni PT

URL: `/backoffice/bookings`

- [ ] Lista prenotazioni si carica
- [ ] Filtro per stato funziona

---

## 14. Corsi collettivi

URL: `/backoffice/group-classes`

> **Nota:** visibile nel menu solo se feature flag `group_classes` = ON (gate `view-group-classes`).
> Con flag OFF la voce di menu è nascosta; la route rimane tecnicamente accessibile navigando direttamente.

- [ ] Lista corsi si carica
- [ ] Crea nuovo corso → salva

---

## 15. Calendario e disponibilità

URL: `/backoffice/calendar`

- [ ] Calendario prenotazioni PT si carica
- [ ] Slot visibili per i trainer

URL: `/backoffice/calendar/availability`

- [ ] Configurazione disponibilità settimanale trainer accessibile
- [ ] Salvataggio disponibilità funziona

---

## 16. Campagne comunicazione

URL: `/backoffice/communications/campaign`

- [ ] Pagina si carica senza errori
- [ ] Crea campagna → seleziona destinatari, messaggio → invia
- [ ] Log invii visibile

---

## 17. Orari di apertura

URL: `/backoffice/settings/opening-hours`

- [ ] Pagina si carica senza errori
- [ ] Orari per giorno visibili
- [ ] Modifica orario → salva

---

## 18. Feature flags e feedback

URL: `/backoffice/admin/feature-flags`

- [ ] Pagina si carica (solo gestore)
- [ ] Toggle flag ON/OFF funziona
- [ ] Cambiamento flag riflesso immediatamente nell'UI (es. gruppo classi nel menu)

URL: `/backoffice/admin/feedback`

- [ ] Lista feedback in-app utenti visibile

---

## 19. Messaggi

URL: `/backoffice/athletes/{id}/messages`

- [ ] Messaggistica con atleta si carica
- [ ] Invio messaggio → appare in lista

---

## Note generali

> Inserire qui osservazioni trasversali (es. lentezza, errori JS in console, testi errati)
