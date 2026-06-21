# Test funzionali — Atleta

**Credenziali con mesociclo attivo:** alessia.colombo@example.com / atleta  
**Credenziali senza mesociclo:** atleta@atleta.atleta / atleta  
**URL base:** http://iron-gym.test/athlete

---

## 1. Auth

- [ ] Login con alessia.colombo → redirect a `/athlete`
- [ ] Login con atleta@atleta.atleta → redirect a `/athlete`
- [ ] Login con credenziali errate → errore visibile
- [ ] Accesso diretto a `/athlete` senza login → redirect a `/login`
- [ ] Accesso diretto a `/backoffice/dashboard` come atleta → 403 o redirect
- [ ] **Esci** (top bar) → logout e redirect a `/login`
- [ ] Dopo logout, accesso a `/athlete` → redirect a `/login`

---

## 2. Dashboard — con mesociclo attivo

*Usare: alessia.colombo@example.com*

URL: `/athlete`

- [ ] Top bar visibile: "Iron Gym" | nome atleta | "Esci"
- [ ] Dashboard si carica senza errori
- [ ] Nome del mesociclo attivo visibile
- [ ] Settimana corrente evidenziata
- [ ] Lista sessioni della settimana corrente visibile
- [ ] Status sessioni mostrato con icona/colore (planned / in_progress / completed / skipped)
- [ ] Prossima sessione evidenziata o con CTA
- [ ] Bottom nav visibile: Oggi, Storico, Progressi, Prenota, Messaggi, Profilo

---

## 3. Dashboard — senza mesociclo

*Usare: atleta@atleta.atleta*

- [ ] Dashboard si carica senza errori
- [ ] Messaggio "nessun mesociclo attivo" visibile (non errore 500)

---

## 4. Sessione di allenamento

*Prerequisito: utente con mesociclo attivo e sessione in stato planned o in_progress.*

URL: `/athlete/session/{id}`

- [ ] Click su sessione dalla dashboard → pagina sessione si carica
- [ ] Nome sessione e lista esercizi visibili
- [ ] Per ogni esercizio: nome, numero serie, reps pianificate visibili
- [ ] Campo input peso e reps per ogni set
- [ ] **Completa set**: inserisci peso e reps → click "Completo" o equivalente → set marcato
- [ ] Set completato visivamente distinto dagli altri (colore/icona)
- [ ] Completati tutti i set → CTA "Concludi sessione" diventa attivo
- [ ] **Concludi sessione** → redirect al form feedback
- [ ] **Salta sessione** → sessione marcata skipped, redirect dashboard

---

## 5. Feedback post-sessione

URL: `/athlete/session/{id}/feedback` o modale inline

- [ ] Form feedback si carica dopo completamento sessione
- [ ] Campi visibili: pump, sforzo percepito, dolori articolari, performance, ore sonno, stress
- [ ] Scala 0-3 funziona (click su ogni valore)
- [ ] Campo note opzionale
- [ ] **Salva** → redirect a dashboard
- [ ] Dopo salvataggio: dashboard mostra la sessione successiva come prossima
- [ ] **Salta feedback** → redirect a dashboard senza creare record

---

## 6. Storico allenamenti

URL: `/athlete/history`

- [ ] Pagina si carica senza errori
- [ ] Sessioni completate visibili con data
- [ ] Sessioni saltate visibili con indicazione
- [ ] Click su sessione storica → dettaglio con set loggati

---

## 7. Progressi

URL: `/athlete/progress`

- [ ] Pagina si carica senza errori
- [ ] Sezione misurazioni corporee visibile
- [ ] **Aggiungi misurazione**: inserisci peso, BF%, circonferenze → salva
- [ ] Nuova misurazione appare in lista/grafico
- [ ] Sezione foto progressi visibile
- [ ] Upload foto funziona (se implementato)

---

## 8. Prenotazioni PT

URL: `/athlete/bookings`

- [ ] Pagina si carica senza errori
- [ ] Lista prenotazioni esistenti visibile
- [ ] **Nuova prenotazione**: seleziona trainer e slot disponibile
- [ ] Conferma prenotazione → appare in lista con stato pending/confirmed
- [ ] Annulla prenotazione → stato aggiornato

---

## 9. Messaggi

URL: `/athlete/messages`

- [ ] Pagina si carica senza errori
- [ ] Thread messaggi con trainer visibili
- [ ] Invio nuovo messaggio funziona
- [ ] Badge notifica su icona Messaggi nella bottom nav (se ci sono non letti)

---

## 10. Profilo

URL: `/profile`

- [ ] Pagina profilo si carica
- [ ] Modifica nome/email → salva
- [ ] Modifica password → salva

---

## Note generali

> Inserire qui anomalie visive (layout rotto su mobile), errori JS, comportamenti inattesi.
