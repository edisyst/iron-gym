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
- [ ] Nav desktop visibile (≥1024px): Oggi, Storico, Volume, Record, Esercizi, Prenota, Messaggi, Profilo
- [ ] Bottom nav visibile (mobile): Oggi, Storico, Volume, Record, Profilo

---

## 3. Dashboard — senza mesociclo

*Usare: atleta@atleta.atleta*

- [ ] Dashboard si carica senza errori
- [ ] Messaggio "nessun mesociclo attivo" visibile (non errore 500)

---

## 4. Check readiness pre-sessione (R07)

*Prerequisito: sessione in stato `planned`.*

- [ ] Click su sessione planned → modale readiness appare (non si entra direttamente in sessione)
- [ ] Modale ha 4 campi: Qualità sonno, Stress, Dolori muscolari, Stato articolare
- [ ] Ogni campo ha 4 bottoni (0-3) cliccabili
- [ ] Campo nota opzionale visibile
- [ ] **Salta check** → entra in sessione senza modulazione
- [ ] **Conferma** con score alto (≥9): entra direttamente in sessione senza proposta
- [ ] **Conferma** con score medio (5-8): proposta -5% sui carichi → bottoni "Accetta" e "Rifiuta"
- [ ] **Conferma** con score basso (<5): proposta -10% sui carichi → bottoni "Accetta" e "Rifiuta"
- [ ] **Accetta modulazione**: planned_weight_kg aggiornati sui set non completati, sessione avviata
- [ ] **Rifiuta modulazione**: sessione avviata con carichi originali
- [ ] Nota readiness appare in trainer_notes della sessione (visibile in backoffice storico atleta)

---

## 5. Sessione di allenamento (R01, R06)

*Prerequisito: utente con mesociclo attivo e sessione in stato planned o in_progress.*

URL: `/athlete/session/{id}`

- [ ] Click su sessione dalla dashboard → pagina sessione si carica
- [ ] Nome sessione e lista esercizi visibili
- [ ] Per ogni esercizio: nome, numero serie, reps pianificate, RIR visibili
- [ ] Previous performance visibile sotto ogni set ("prec: Xkg × Y @ RIR Z")

### Quick-log e completamento set

- [ ] **Quick-log** (bottone freccia/⚡): copia planned→actual, marca set completato con un tap
- [ ] **Completamento manuale**: inserisci peso e reps → "Completo" → set marcato
- [ ] Set già completato non resetta `completed_at` se cliccato di nuovo
- [ ] Set completato visivamente distinto (colore/icona)
- [ ] Badge ⏳ su set pending (operazioni offline in coda)

### Warm-up generator

- [ ] Bottone "Genera warm-up" su ogni esercizio
- [ ] Click genera 2-3 set is_warmup al 50/70/85% del planned_weight (arrotondati a 2.5 kg)
- [ ] Sotto 40 kg: solo set al 50%
- [ ] Click ripetuto: idempotente (non aggiunge duplicati)
- [ ] Singolo set warmup eliminabile con bottone X
- [ ] Set working non eliminabile tramite delete warmup → errore 404

### Rest timer

- [ ] Dopo completamento set: timer rest parte automaticamente
- [ ] Barra fissa bottom con countdown e bottone "Salta"
- [ ] Allo scadere: vibrazione dispositivo + notifica browser (se permesso)
- [ ] Per esercizi in superset: usa `intra_cluster_rest_sec`

### Sostituzione esercizio (R06)

- [ ] Bottone "Sostituisci" visibile per ogni esercizio
- [ ] Bottone disabilitato se ci sono set working completati
- [ ] Click → bottom sheet con max 5 candidati compatibili
- [ ] Candidati mostrano nome, muscoli primari, equipment
- [ ] Selezione candidato → esercizio sostituito, set e prescrizione invariati
- [ ] Badge "sost. da [nome originale]" visibile sull'esercizio sostituito
- [ ] Bottone "Chiudi" annulla senza modificare

### Completamento sessione

- [ ] Completati tutti i set working → CTA "Concludi sessione" attivo
- [ ] **Concludi sessione** → form feedback → riepilogo sessione
- [ ] **Salta sessione** → sessione marcata skipped, redirect dashboard

---

## 6. Feedback post-sessione

URL: `/athlete/session/{id}/feedback`

- [ ] Form feedback si carica dopo completamento sessione
- [ ] Campi visibili: pump, sforzo percepito, dolori articolari, performance, ore sonno, stress
- [ ] Scala 0-3 funziona (click su ogni valore)
- [ ] Campo note opzionale
- [ ] **Salva** → redirect a riepilogo sessione (`/athlete/session/{id}/recap`)
- [ ] **Salta feedback** → redirect a riepilogo sessione

---

## 7. Riepilogo sessione (R08)

URL: `/athlete/session/{id}/recap`

- [ ] Pagina si carica senza errori per sessione completata
- [ ] Card riepilogo visibile con: nome sessione, data
- [ ] Durata in minuti mostrata
- [ ] Tonnellaggio in kg (o tonnellate se >1000 kg)
- [ ] Contatore set completati / prescritti
- [ ] Sezione PR: badge per ogni record ottenuto in sessione (assente se zero PR)
- [ ] Sezione top 3 muscoli allenati con barre proporzionali
- [ ] Bottone "Condividi" → export PNG card via Web Share API (o download diretto se non supportata)
- [ ] Bottone "Chiudi" → redirect a dashboard
- [ ] Accesso alla pagina di una sessione di un altro atleta → 403

---

## 8. Storico allenamenti

URL: `/athlete/history`

- [ ] Pagina si carica senza errori
- [ ] Sessioni completate visibili con data
- [ ] Sessioni saltate visibili con indicazione
- [ ] Click su sessione storica → dettaglio con set loggati e feedback
- [ ] Icona "Condividi" per sessioni completate → link a `/session/{id}/recap`
- [ ] Badge "sost. da [originale]" visibile per esercizi sostituiti (R06)
- [ ] Score readiness e modulazione visibili (R07)

---

## 9. Volume settimanale (R04)

URL: `/athlete/volume`

- [ ] Pagina si carica senza errori
- [ ] Body map SVG fronte e retro visibile (25 muscoli)
- [ ] Muscoli colorati per intensità allenamento (0=grigio → 5=arancio scuro)
- [ ] Selettore settimana mesociclo funziona
- [ ] Barre orizzontali per ogni muscolo con marker MEV, banda MAV, marker MRV
- [ ] Tap su muscolo nella body map → scroll alla barra corrispondente
- [ ] Voce "Volume" nella nav

---

## 10. Record personali (R05)

URL: `/athlete/records`

- [ ] Pagina si carica senza errori
- [ ] Lista PR e1RM per esercizio visibile
- [ ] Data e valore stimato 1RM mostrati
- [ ] Toast PR appare durante sessione al completamento di un set record (4s auto-dismiss)
- [ ] PR rilevato anche per operazioni sincronizzate offline

---

## 11. Plate calculator (R02)

*Accessibile dalla sessione (modale).*

- [ ] Modale plate calculator accessibile in sessione
- [ ] Selettore peso barra (10/15/20 kg)
- [ ] Inserisci peso target → calcolo dischi per lato
- [ ] Stack grafico dischi mostrato con colori e pesi
- [ ] Se combinazione esatta: `delta_kg = 0`
- [ ] Se non esatta: combinazione per difetto con delta mostrato

---

## 12. Misurazioni corporee

URL: `/athlete/measurements`

- [ ] Pagina si carica senza errori
- [ ] **Aggiungi misurazione**: inserisci peso, BF%, circonferenze → salva
- [ ] Nuova misurazione appare in lista/grafico

---

## 13. Prenotazioni PT

URL: `/athlete/bookings`

- [ ] Pagina si carica senza errori
- [ ] Lista prenotazioni esistenti visibile
- [ ] **Nuova prenotazione**: seleziona trainer e slot disponibile
- [ ] Conferma prenotazione → appare in lista con stato pending/confirmed
- [ ] Annulla prenotazione → stato aggiornato

---

## 14. Messaggi

URL: `/athlete/messages`

- [ ] Pagina si carica senza errori
- [ ] Thread messaggi con trainer visibili
- [ ] Invio nuovo messaggio funziona
- [ ] Badge notifica su icona Messaggi nella nav (se ci sono non letti)

---

## 15. Profilo

URL: `/profile`

- [ ] Pagina profilo si carica
- [ ] Modifica nome/email → salva
- [ ] Modifica password → salva

---

## 16. Offline (R03)

*Simulare connessione assente con DevTools → Network → Offline.*

- [ ] In sessione: quick-log e completeSet funzionano offline (badge ⏳ appare)
- [ ] Ripristino connessione → operazioni si sincronizzano automaticamente
- [ ] Pagina sessione navigabile offline (service worker fallback)
- [ ] Badge ⏳ scompaiono dopo sync riuscita

---

## Note generali

> Inserire qui anomalie visive (layout rotto su mobile), errori JS, comportamenti inattesi.
