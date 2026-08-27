# Piano di Test Funzionali — R09+ (Iron Gym)

> Versione: 1.0 — generato da assessment `docs/reviews/r09-plus-test-assessment.md`
> Fonte di verità: codice sorgente. Divergenze rispetto ai documenti: vedi assessment sezione 4.

---

## 0. Prerequisiti

### 0.1 Stato del database demo

Eseguire i seeder nell'ordine corretto su un DB **già inizializzato** (mai `migrate:fresh --seed` su pilota):

```bash
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=ExerciseSeeder
php artisan db:seed --class=ExerciseDescriptionSeeder
php artisan db:seed --class=PlateInventorySeeder
php artisan db:seed --class=DumbbellInventorySeeder
# ATTENZIONE: OpeningHoursSeeder usa truncate(), NON rieseguire su DB con dati reali
php artisan db:seed --class=CommunicationTemplateSeeder
php artisan db:seed --class=DemoSeeder
php artisan db:seed --class=DemoTemplatesSeeder
php artisan db:seed --class=TrainingHistorySeeder
php artisan db:seed --class=ActiveMesocycleSeeder
php artisan db:seed --class=ProgressDemoSeeder
php artisan db:seed --class=GroupClassSeeder
php artisan db:seed --class=BookingDemoSeeder
php artisan db:seed --class=R09R31DemoSeeder
php artisan db:seed --class=PilotSeeder
# Seeder per scenari avanzati (Fase 2 — da creare):
# php artisan db:seed --class=FunctionalTestSeeder
```

### 0.2 Feature flag richiesti

| Flag | Stato richiesto | Come verificare |
|---|---|---|
| `group_classes` | **ON** | Backoffice → Admin → Feature Flags |
| `periodization_engine` | ON | Attivato da PilotSeeder |
| `financial_reports` | ON | Attivato da PilotSeeder |
| `push_notifications` | qualsiasi | Non impatta i test di questo piano |

Per attivare `group_classes` via UI: `admin@admin.admin` → `/backoffice/admin/feature-flags` → toggle "Corsi collettivi".

### 0.3 Account demo

| Ruolo | Email | Password | Note |
|---|---|---|---|
| Gestore | `admin@admin.admin` | `admin` | Accesso completo backoffice |
| Trainer 1 | `trainer@trainer.trainer` | `trainer` | Luca Bianchi |
| Trainer 2 | `trainer2@trainer.trainer` | `trainer` | Elena Russo |
| Receptionist | `receptionist@receptionist.receptionist` | `receptionist` | Sara Verdi |
| Atleta demo | `atleta@atleta.atleta` | `atleta` | Abb. attivo, cert valido, PR/messaggi/notifiche demo |
| Giovanni Ferrari | `giovanni.ferrari@example.com` | `atleta` | Abb. attivo, cert valido, ha note interne |
| Alessia Colombo | `alessia.colombo@example.com` | `atleta` | Abb. scaduto, cert scaduto |
| Marco Ricci | `marco.ricci@example.com` | `atleta` | Abb. attivo, cert in scadenza 20gg |
| Federica Esposito | `federica.esposito@example.com` | `atleta` | Abb. attivo, cert valido |
| Davide Martini | `davide.martini@example.com` | `atleta` | Abb. attivo, cert assente (null) |

### 0.4 URL di partenza per area

| Area | URL |
|---|---|
| Backoffice dashboard | `/backoffice/dashboard` |
| Corsi collettivi (occorrenze) | `/backoffice/group-classes` |
| Palinsesto | `/backoffice/group-classes/schedules` |
| Catalogo corsi | `/backoffice/group-classes/catalog` |
| Prenotazioni PT + corsi (atleta) | `/athlete/bookings` |
| Notifiche atleta | `/athlete/notifications` |
| Profilo atleta | `/athlete/profile` |
| Pannello scadenze | `/backoffice/members/expiry` |
| Check-in rapido | `/backoffice/checkin` |
| Abbonamenti | `/backoffice/subscriptions` |
| Tesserati | `/backoffice/members` |
| Dashboard gestore | `/backoffice/reports/manager` |
| Feature flags | `/backoffice/admin/feature-flags` |
| Orari apertura | `/backoffice/settings/opening-hours` |

### 0.5 Comandi manuali utili

```bash
# Genera occorrenze corsi dai palinsesti attivi (idempotente, orizzonte 28 giorni)
php artisan classes:generate-occurrences

# Invia promemoria corsi per le occorrenze del giorno seguente
php artisan classes:send-reminders --sync

# Tinker per creare occorrenza con orario immediato (finestra <30 min)
php artisan tinker
# >>> $gc = App\Models\GroupClass::first();
# >>> App\Models\ClassOccurrence::create([
#       'group_class_id' => $gc->id,
#       'class_schedule_id' => null,
#       'trainer_id' => App\Models\User::role('trainer')->first()->id,
#       'date' => now()->toDateString(),
#       'start_time' => now()->addMinutes(20)->format('H:i:s'),
#       'end_time' => now()->addMinutes(80)->format('H:i:s'),
#       'capacity' => 10,
#       'status' => 'planned',
#     ]);
```

---

## 1. CLS — Corsi Collettivi

### TC-CLS-001 — Iscrizione confermata (happy path)

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | Occorrenza futura entro 7 giorni dal BookingDemoSeeder (es. Yoga, data now()+2); atleta con abb. attivo e cert valido |
| Feature flag | group_classes: ON |

**Step:**
1. Vai a `/athlete/bookings`
2. Clicca il tab "Corsi collettivi"
3. Individua l'occorrenza Yoga con data nei prossimi 7 giorni
4. Clicca "Iscriviti"

**Risultato atteso:** Messaggio flash verde "Iscrizione confermata!". L'occorrenza appare nella sezione "Le mie iscrizioni" con badge "Confermato".

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CLS-002 — Enroll con abbonamento scaduto

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `alessia.colombo@example.com` / `atleta` |
| Precondizioni | Alessia ha abb. scaduto e cert scaduto; occorrenza futura esistente |
| Feature flag | group_classes: ON |

**Step:**
1. Vai a `/athlete/bookings` → tab "Corsi collettivi"
2. Tenta di iscriverti a qualsiasi occorrenza futura disponibile

**Risultato atteso:** Messaggio flash rosso "Nessun abbonamento attivo." L'iscrizione non viene creata.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CLS-003 — Enroll con certificato medico scaduto

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `alessia.colombo@example.com` / `atleta` |
| Precondizioni | Se Alessia ha anche abb. valido da creare manualmente, oppure usare un atleta con solo cert scaduto |
| Feature flag | group_classes: ON |

**Nota:** Con i dati demo attuali, Alessia ha sia abb. scaduto sia cert scaduto. Il blocco su abb. viene prima. Per testare il blocco specifico del cert, creare manualmente un atleta con abb. attivo e cert scaduto, oppure verificare il messaggio "Certificato medico scaduto o assente." se il codice lo raggiunge.

**Step:**
1. Vai a `/athlete/bookings` → tab "Corsi collettivi"
2. Tenta di iscriverti

**Risultato atteso:** Messaggio flash rosso. Iscrizione non creata.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CLS-004 — Enroll con certificato medico assente

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `davide.martini@example.com` / `atleta` |
| Precondizioni | Davide ha abb. attivo e cert `null`; occorrenza futura entro finestra |
| Feature flag | group_classes: ON |

**Step:**
1. Vai a `/athlete/bookings` → tab "Corsi collettivi"
2. Tenta di iscriverti a un corso con data entro 7 giorni

**Risultato atteso:** Messaggio flash rosso "Certificato medico scaduto o assente." L'iscrizione non viene creata.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CLS-005 — Finestra prenotazione non ancora aperta (>7 giorni)

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | Occorrenza futura con data now()+8 o più (BookingDemoSeeder crea occorrenze a +8..+11 giorni) |
| Feature flag | group_classes: ON |

**Come forzare la condizione:** Il BookingDemoSeeder crea occorrenze con date `now()->addDays(8)` e oltre. Queste occorrenze sono visibili nella lista ma la prenotazione deve essere bloccata.

**Step:**
1. Vai a `/athlete/bookings` → tab "Corsi collettivi"
2. Individua un corso con data > 7 giorni da oggi
3. Tenta di iscriverti

**Risultato atteso:** Messaggio flash arancio/rosso "Le prenotazioni aprono il GG/MM/YYYY." (data = occorrenza - 7 giorni). Iscrizione non creata.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CLS-006 — Finestra prenotazione chiusa (<30 min dall'inizio)

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | Occorrenza con data=oggi e start_time=now()+20min **[DA CREARE MANUALMENTE via Tinker — vedi sezione 0.5]** |
| Feature flag | group_classes: ON |

**Come forzare la condizione:** Eseguire il comando Tinker in sezione 0.5 per creare un'occorrenza con inizio tra 20 minuti.

**Step:**
1. Crea l'occorrenza "a breve" via Tinker (sezione 0.5)
2. Vai a `/athlete/bookings` → tab "Corsi collettivi"
3. Ricarica la pagina — l'occorrenza appare con data odierna
4. Tenta di iscriverti

**Risultato atteso:** Messaggio flash rosso "Prenotazioni chiuse (entro 30 min dall'inizio)." Iscrizione non creata.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CLS-007 — Overlap atleta corso-corso

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | Atleta già iscritto (confermato) a un corso. Esiste un secondo corso nello stesso giorno con orario sovrapposto |
| Feature flag | group_classes: ON |

**Come forzare la condizione:** Dopo aver eseguito TC-CLS-001 (iscrizione confermata), cercare un corso con stessa data e orario che si sovrappone.

**Step:**
1. Assicurarsi che l'atleta sia già confermato in un corso (es. Yoga ore 18:00-19:00)
2. Vai a `/athlete/bookings` → tab "Corsi collettivi"
3. Tenta di iscriverti a un secondo corso con orario sovrapposto lo stesso giorno

**Risultato atteso:** Messaggio flash rosso "Hai già un corso confermato in questo orario." Seconda iscrizione non creata.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CLS-008 — Enroll già iscritto (duplicato)

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | Atleta già iscritto (confermato o waitlisted) a un corso |
| Feature flag | group_classes: ON |

**Step:**
1. Dopo aver eseguito TC-CLS-001, premi di nuovo "Iscriviti" per la stessa occorrenza

**Risultato atteso:** Messaggio flash rosso "Il membro è già iscritto o in lista d'attesa per questo corso." Nessuna seconda iscrizione.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CLS-009 — Corso al completo: iscrizione in waitlist

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `federica.esposito@example.com` / `atleta` |
| Precondizioni | Occorrenza al completo (capacity=N, N iscritti confermati) **[DA CREARE - Fase 2: occorrenza "Yoga Full" da FunctionalTestSeeder, capacity=3, 3 confirmed]** |
| Feature flag | group_classes: ON |

**Step:**
1. Accedi come Federica
2. Vai a `/athlete/bookings` → tab "Corsi collettivi"
3. Individua il corso "Yoga Full" segnato come al completo
4. Clicca "Iscriviti"

**Risultato atteso:** Messaggio flash "Sei in lista d'attesa (posizione 1)." L'iscrizione appare con badge "Lista d'attesa" in "Le mie iscrizioni".

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CLS-010 — Cancellazione entro free_cancel_hours (3h)

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | Atleta iscritto (confermato) a un corso con inizio tra >3 ore |
| Feature flag | group_classes: ON |

**Step:**
1. Assicurarsi di avere un'iscrizione confermata a un corso con inizio tra più di 3 ore
2. Vai a `/athlete/bookings` → tab "Corsi collettivi" → sezione "Le mie iscrizioni"
3. Clicca "Cancella iscrizione"

**Risultato atteso:** Messaggio flash verde "Iscrizione annullata." L'iscrizione scompare dalla lista "Le mie iscrizioni".

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CLS-011 — Cancellazione fuori dalla finestra (>3h trascorse)

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | Atleta iscritto a un corso con inizio tra meno di 3 ore. Creare manualmente via Tinker (vedi 0.5) un'occorrenza tra 1 ora e iscriversi prima |
| Feature flag | group_classes: ON |

**Come forzare la condizione:** Creare occorrenza con `start_time = now()+60min` via Tinker. Iscriversi (entro finestra apertura). Poi tentare di cancellare.

**Step:**
1. Creare occorrenza tra 60 minuti via Tinker
2. Iscriversi come atleta (la prenotazione è aperta, >30 min)
3. Tentare di cancellare l'iscrizione

**Risultato atteso:** Messaggio flash rosso "Cancellazione non disponibile (entro 3 ore dall'inizio)." Iscrizione rimane attiva.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CLS-012 — Promozione dalla waitlist dopo cancellazione

| Campo | Valore |
|---|---|
| Persona | gestore (per rimuovere) / atleta in waitlist |
| Account | Gestore: `admin@admin.admin`; atleta in waitlist: `federica.esposito@example.com` |
| Precondizioni | Corso con almeno 1 confermato e 1 in waitlist **[DA CREARE - Fase 2: FunctionalTestSeeder con occorrenza al completo + Federica in waitlist]** |
| Feature flag | group_classes: ON |

**Step:**
1. Accedi come gestore
2. Vai a `/backoffice/group-classes`, apri il dettaglio del corso al completo
3. Clicca "Rimuovi" su uno dei partecipanti confermati
4. Disconnettiti e accedi come `federica.esposito@example.com`
5. Vai a `/athlete/notifications`

**Risultato atteso:** Step 3: messaggio flash "Partecipante rimosso."; il confermato rimosso non appare più. Step 5: Federica ha una notifica di tipo "waitlist_promoted" con testo di promozione. In `/athlete/bookings` la sua iscrizione mostra badge "Confermato" (non più "Lista d'attesa").

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CLS-013 — Cancellazione corso con partecipanti: notifica

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | Occorrenza con almeno 1 prenotazione confirmed; `atleta@atleta.atleta` iscritto |
| Feature flag | group_classes: ON |

**Step:**
1. Accedi come gestore
2. Vai a `/backoffice/group-classes`
3. Individua un corso con iscrizioni confirmed
4. Clicca "Elimina" / "Cancella" su quell'occorrenza
5. Disconnettiti e accedi come `atleta@atleta.atleta`
6. Vai a `/athlete/notifications`

**Risultato atteso:** Step 4: messaggio flash "Corso cancellato — partecipanti notificati." L'occorrenza passa a stato `cancelled` nella lista. Step 6: l'atleta ha una notifica di tipo `class_cancelled` con il nome del corso.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CLS-014 — Cancellazione corso senza partecipanti: eliminazione diretta

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | Occorrenza senza prenotazioni confirmed |
| Feature flag | group_classes: ON |

**Step:**
1. Vai a `/backoffice/group-classes`
2. Individua un corso con 0 prenotazioni confirmed (o crea un corso nuovo)
3. Clicca "Elimina"

**Risultato atteso:** Messaggio flash "Corso eliminato." L'occorrenza scompare dalla lista (non passa a stato `cancelled`, viene fisicamente eliminata).

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CLS-015 — Attendance: completeOccurrence

| Campo | Valore |
|---|---|
| Persona | gestore o trainer |
| Account | `trainer@trainer.trainer` / `trainer` |
| Precondizioni | Occorrenza in stato `planned` con almeno 1 iscrizione confirmed |
| Feature flag | group_classes: ON |

**Step:**
1. Vai a `/backoffice/group-classes`
2. Apri il dettaglio di un'occorrenza planned
3. Clicca "Completa corso"

**Risultato atteso:** Messaggio flash "Corso completato. Presenze registrate per gli iscritti confermati." Occorrenza passa a stato `completed`. Tutti i confermati mostrano `attended_at` valorizzato.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CLS-016 — Attendance: markAttended e markNoShow manuale

| Campo | Valore |
|---|---|
| Persona | receptionist |
| Account | `receptionist@receptionist.receptionist` / `receptionist` |
| Precondizioni | Occorrenza planned con iscrizioni confirmed |
| Feature flag | group_classes: ON |

**Step:**
1. Vai a `/backoffice/group-classes`
2. Apri dettaglio di un'occorrenza planned con partecipanti
3. Clicca "Presente" su un partecipante
4. Clicca "Assente" su un altro partecipante

**Risultato atteso:** Step 3: il partecipante ottiene `attended_at` valorizzato e badge verde. Step 4: il partecipante ottiene status `no_show` e `attended_at` null.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CLS-017 — completeOccurrence su corso non planned

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | Occorrenza in stato `completed` |
| Feature flag | group_classes: ON |

**Step:**
1. Trova un'occorrenza già completed
2. Tenta di eseguire "Completa corso" di nuovo (se il pulsante è visibile)

**Risultato atteso:** Messaggio flash rosso "Solo i corsi pianificati possono essere completati." Lo stato non cambia.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CLS-018 — Permesso negato: atleta non accede a /backoffice/group-classes

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | — |
| Feature flag | qualsiasi |

**Step:**
1. Accedi come atleta
2. Naviga direttamente a `/backoffice/group-classes`

**Risultato atteso:** Redirect a login o errore 403. L'atleta non vede la pagina di gestione backoffice.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CLS-019 — Permesso negato: receptionist non cancella corso

| Campo | Valore |
|---|---|
| Persona | receptionist |
| Account | `receptionist@receptionist.receptionist` / `receptionist` |
| Precondizioni | Occorrenza planned visibile |
| Feature flag | group_classes: ON |

**Step:**
1. Accedi come receptionist
2. Vai a `/backoffice/group-classes`
3. Tenta di cliccare "Elimina" su un corso (se il pulsante è visibile)

**Risultato atteso:** Errore 403 o il pulsante "Elimina" non è visibile per il receptionist. La `deleteClass` ha guard `gestore|trainer`.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CLS-020 — Feature flag OFF: tab corsi non visibile in /athlete/bookings

| Campo | Valore |
|---|---|
| Persona | atleta + gestore |
| Account | Prima: `admin@admin.admin`; poi: `atleta@atleta.atleta` |
| Precondizioni | group_classes attualmente ON |
| Feature flag | group_classes: da ON a OFF |

**Step:**
1. Accedi come gestore
2. Vai a `/backoffice/admin/feature-flags`
3. Disattiva il flag "Corsi collettivi" (group_classes)
4. Disconnettiti e accedi come `atleta@atleta.atleta`
5. Vai a `/athlete/bookings`
6. Verifica tab corsi
7. Accedi direttamente a `/athlete/bookings` e premi "Iscriviti" su un corso (se visibile)

**Risultato atteso:** Step 5-6: il tab "Corsi collettivi" non è visibile. Step 7: se si tenta `enrollClass` direttamente, risposta 403.

**Nota:** Riattivare il flag al termine del test.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

## 2. SCH — Palinsesto

### TC-SCH-001 — Creazione palinsesto (happy path)

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | Almeno un GroupClass definito nel catalogo |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/group-classes/schedules`
2. Clicca "Aggiungi palinsesto"
3. Seleziona: corso Yoga, giorno Lunedì, ora 09:00, trainer Luca Bianchi, valid_from oggi
4. Clicca "Salva"

**Risultato atteso:** Messaggio flash di successo. Il nuovo palinsesto appare nella lista con giorno Lunedì, ora 09:00.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-SCH-002 — Modifica palinsesto

| Campo | Valore |
|---|---|
| Persona | trainer |
| Account | `trainer@trainer.trainer` / `trainer` |
| Precondizioni | Palinsesto esistente |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/group-classes/schedules`
2. Clicca "Modifica" su un palinsesto esistente
3. Cambia l'orario a 10:00
4. Clicca "Salva"

**Risultato atteso:** Messaggio flash di successo. Il palinsesto aggiornato mostra il nuovo orario.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-SCH-003 — Disattivazione palinsesto

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | Palinsesto attivo |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/group-classes/schedules`
2. Clicca "Modifica" su un palinsesto attivo
3. Deseleziona "Attivo"
4. Salva
5. Lancia `php artisan classes:generate-occurrences`

**Risultato atteso:** Il palinsesto è segnato come inattivo. Il comando non genera nuove occorrenze per quel palinsesto.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-SCH-004 — Validazione: form palinsesto con dati mancanti

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | — |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/group-classes/schedules`
2. Clicca "Aggiungi palinsesto"
3. Lascia il campo "Corso" vuoto
4. Clicca "Salva"

**Risultato atteso:** Errori di validazione inline (campo corso obbligatorio). Il record non viene creato.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-SCH-005 — Permesso negato: receptionist non crea palinsesto

| Campo | Valore |
|---|---|
| Persona | receptionist |
| Account | `receptionist@receptionist.receptionist` / `receptionist` |
| Precondizioni | — |
| Feature flag | — |

**Step:**
1. Accedi come receptionist
2. Vai a `/backoffice/group-classes/schedules`
3. Se il pulsante "Aggiungi" è visibile, clicca "Salva" con dati validi

**Risultato atteso:** Errore 403 o il pulsante non è visibile. Il receptionist non può creare/modificare palinsesti.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

## 3. CAT — Catalogo Corsi

### TC-CAT-001 — Creazione definizione corso (happy path)

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | — |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/group-classes/catalog`
2. Clicca "Nuovo corso"
3. Inserisci: Nome "Test Pilates", Durata 45 min, Capacità 12, Sala "Studio A", colore #E85D04
4. Salva

**Risultato atteso:** Messaggio flash "Corso creato." Il corso "Test Pilates" appare nella lista con slug `test-pilates`.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CAT-002 — Modifica e toggle attivo/inattivo

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | Corso esistente attivo |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/group-classes/catalog`
2. Clicca "Modifica" su un corso attivo; cambia la descrizione; salva
3. Clicca il toggle "Attivo/Inattivo"

**Risultato atteso:** Step 2: messaggio "Corso aggiornato." con nuova descrizione. Step 3: il corso appare come inattivo (badge diverso o riga in grigio).

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CAT-003 — Eliminazione corso senza occorrenze future

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | Corso "Test Pilates" creato in TC-CAT-001 senza occorrenze |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/group-classes/catalog`
2. Clicca "Elimina" sul corso "Test Pilates"

**Risultato atteso:** Messaggio flash "Corso eliminato." Il corso scompare dalla lista.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CAT-004 — Eliminazione corso con occorrenze future: blocco

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | Corso con almeno 1 occorrenza futura planned |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/group-classes/catalog`
2. Clicca "Elimina" su un corso con occorrenze future

**Risultato atteso:** Messaggio flash rosso "Impossibile eliminare: esistono occorrenze future pianificate." Il corso rimane nella lista.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CAT-005 — Validazione: nome obbligatorio

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | — |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/group-classes/catalog`
2. Clicca "Nuovo corso", lascia il nome vuoto, salva

**Risultato atteso:** Errore validazione "Il nome del corso è obbligatorio."

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CAT-006 — Permesso negato: trainer non accede al catalogo in scrittura

| Campo | Valore |
|---|---|
| Persona | trainer |
| Account | `trainer@trainer.trainer` / `trainer` |
| Precondizioni | — |
| Feature flag | — |

**Step:**
1. Accedi come trainer
2. Vai a `/backoffice/group-classes/catalog`
3. Tenta di creare un corso (se il pulsante è visibile)

**Risultato atteso:** Errore 403 al salvataggio. La `save()` di GroupClassCatalog ha guard `role:gestore`.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

## 4. GEN — Generazione Occorrenze

### TC-GEN-001 — Generazione occorrenze da palinsesto attivo

| Campo | Valore |
|---|---|
| Persona | — (operazione da terminale) |
| Account | — |
| Precondizioni | Almeno un ClassSchedule attivo con valid_from <= oggi e weekday valorizzato |
| Feature flag | — |

**Step:**
1. Da terminale: `php artisan classes:generate-occurrences`
2. Vai a `/backoffice/group-classes`
3. Filtra per stato "Pianificati"

**Risultato atteso:** Il comando termina senza errori. Nella lista occorrenze appaiono nuovi record per le prossime 28 giornate coperte dal palinsesto.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-GEN-002 — Idempotenza: seconda esecuzione non duplica

| Campo | Valore |
|---|---|
| Persona | — |
| Account | — |
| Precondizioni | Occorrenze già generate da TC-GEN-001 |
| Feature flag | — |

**Step:**
1. Da terminale: conta le occorrenze attuali
   ```bash
   php artisan tinker --execute="echo App\Models\ClassOccurrence::count();"
   ```
2. Lancia di nuovo `php artisan classes:generate-occurrences`
3. Riconta le occorrenze

**Risultato atteso:** Il conteggio è identico prima e dopo la seconda esecuzione. Nessun duplicato creato (vincolo UNIQUE su `class_schedule_id, date`).

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-GEN-003 — Palinsesto inattivo: nessuna occorrenza generata

| Campo | Valore |
|---|---|
| Persona | — |
| Account | — |
| Precondizioni | ClassSchedule con `is_active=false` |
| Feature flag | — |

**Step:**
1. Crea un palinsesto e poi disattivarlo (TC-SCH-003)
2. Lancia `php artisan classes:generate-occurrences`
3. Verifica che non esistano nuove occorrenze per quel palinsesto

**Risultato atteso:** Nessuna nuova occorrenza creata per il palinsesto inattivo.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

## 5. NOT — Notifiche Atleta

### TC-NOT-001 — Centro notifiche: lista e badge

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | `R09R31DemoSeeder` ha inserito notifiche demo per questo atleta |
| Feature flag | — |

**Step:**
1. Accedi come atleta
2. Vai a `/athlete` (dashboard)
3. Osserva la sidebar: verifica il badge con numero notifiche non lette
4. Vai a `/athlete/notifications`

**Risultato atteso:** Step 3: il badge mostra un numero > 0 se ci sono notifiche non lette. Step 4: la pagina lista le notifiche con testo, data, badge "Non letta" per quelle non ancora lette.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-NOT-002 — Segna come letta (singola)

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | Almeno 1 notifica non letta |
| Feature flag | — |

**Step:**
1. Vai a `/athlete/notifications`
2. Clicca "Segna come letta" su una notifica non letta

**Risultato atteso:** Il badge della notifica cambia (sparisce "Non letta"). Il badge in sidebar si decrementa di 1.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-NOT-003 — Segna tutte come lette

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | Almeno 2 notifiche non lette |
| Feature flag | — |

**Step:**
1. Vai a `/athlete/notifications`
2. Clicca "Segna tutte come lette"

**Risultato atteso:** Tutte le notifiche perdono il badge "Non letta". Il badge in sidebar mostra 0.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-NOT-004 — Elimina notifica

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | Almeno 1 notifica |
| Feature flag | — |

**Step:**
1. Vai a `/athlete/notifications`
2. Clicca "Elimina" su una notifica

**Risultato atteso:** La notifica scompare dalla lista. Il conteggio totale si riduce di 1.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-NOT-005 — Promemoria corso: esecuzione comando

| Campo | Valore |
|---|---|
| Persona | — (operazione da terminale) |
| Account | — |
| Precondizioni | Almeno 1 occorrenza con data = domani, con almeno 1 iscrizione confirmed |
| Feature flag | group_classes: ON |

**Come verificare la precondizione:** `php artisan tinker --execute="echo App\Models\ClassOccurrence::whereDate('date', now()->addDay())->count();"`

**Step:**
1. Assicurarsi che esista almeno 1 occorrenza con data=domani e booking confirmed (se non esiste, crearne una via Tinker)
2. Da terminale: `php artisan classes:send-reminders --sync`
3. Accedi come l'atleta iscritto a quell'occorrenza
4. Vai a `/athlete/notifications`

**Risultato atteso:** Step 2: comando termina senza errori, output indica quante notifiche inviate. Step 4: l'atleta ha una notifica di tipo `class_reminder` con il nome del corso di domani.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-NOT-006 — Permesso negato: backoffice non accede al centro notifiche atleta

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | — |
| Feature flag | — |

**Step:**
1. Accedi come gestore
2. Naviga a `/athlete/notifications`

**Risultato atteso:** Redirect a login o errore 403. La route `/athlete/*` richiede `role:atleta`.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

## 6. PRF — Profilo Atleta

### TC-PRF-001 — Tab abbonamento: visualizzazione dati

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | Atleta con abb. attivo |
| Feature flag | — |

**Step:**
1. Vai a `/athlete/profile`
2. Clicca tab "Abbonamento"

**Risultato atteso:** Piano, date inizio/scadenza e badge stato (es. "Attivo") visibili. I dati corrispondono all'abbonamento dell'atleta.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-PRF-002 — Tab abbonamento: abb. scaduto mostra badge corretto

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `alessia.colombo@example.com` / `atleta` |
| Precondizioni | Alessia ha abb. con expires nel passato |
| Feature flag | — |

**Step:**
1. Vai a `/athlete/profile` → tab "Abbonamento"

**Risultato atteso:** Badge "Scaduto" (non "Attivo"). Piano e date visibili.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-PRF-003 — Tab PT: prenotazioni prossime e storico

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | `R09R31DemoSeeder` o `FIX01` ha creato PT pending/confirmed per questo atleta |
| Feature flag | — |

**Step:**
1. Vai a `/athlete/profile` → tab "Sessioni PT"

**Risultato atteso:** Sezione "Prossime" mostra PT con stato pending o confirmed e date future. Sezione "Storico" mostra le ultime 10 PT completate/cancellate.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-PRF-004 — Tab misurazioni: ultime 5

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | `TrainingHistorySeeder` o `ProgressDemoSeeder` ha inserito misurazioni per l'atleta |
| Feature flag | — |

**Step:**
1. Vai a `/athlete/profile` → tab "Misurazioni"

**Risultato atteso:** Tabella con massimo 5 righe (peso, BF%, vita, petto). Link "Vedi tutte" punta a `/athlete/measurements`.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-PRF-005 — Tab record: ultimi 5 e1RM

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | PR e1RM presenti dal seeder demo |
| Feature flag | — |

**Step:**
1. Vai a `/athlete/profile` → tab "Record"

**Risultato atteso:** Tabella con massimo 5 record e1RM (esercizio, valore, data). Link "Vedi tutti" punta a `/athlete/records`.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-PRF-006 — Tab sessioni: ultime 5

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | Sessioni completed/skipped presenti dal seeder |
| Feature flag | — |

**Step:**
1. Vai a `/athlete/profile` → tab "Sessioni"

**Risultato atteso:** Tabella con massimo 5 sessioni (nome, data, durata, badge completed/skipped). Link "Storico completo" punta a `/athlete/history`.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-PRF-007 — Tab messaggi: ultimi 5

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | Messaggi trainer-atleta presenti dal seeder |
| Feature flag | — |

**Step:**
1. Vai a `/athlete/profile` → tab "Messaggi"

**Risultato atteso:** Tabella con massimo 5 messaggi (contatto, anteprima, data, badge non letti se presenti). Link "Vai ai messaggi" punta a `/athlete/messages`.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-PRF-008 — Tab corsi: iscrizioni future e storico (flag ON)

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | Atleta iscritto a un corso futuro (dopo TC-CLS-001) |
| Feature flag | group_classes: ON |

**Step:**
1. Vai a `/athlete/profile` → tab "Corsi"

**Risultato atteso:** Sezione "Prossimi corsi" mostra il corso con data e badge "Confermato". Sezione "Storico" mostra corsi passati.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-PRF-009 — Tab corsi: non visibile con flag OFF

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | group_classes: OFF (disattivare temporaneamente) |
| Feature flag | group_classes: OFF |

**Step:**
1. Disattiva il flag group_classes (TC-CLS-020 step 1-3)
2. Accedi come atleta
3. Vai a `/athlete/profile`

**Risultato atteso:** Il tab "Corsi" non appare nella lista tab del profilo.

**Nota:** Riattivare il flag al termine.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-PRF-010 — Tab accessi: ultimi 5

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | AccessLog presenti dal seeder o da check-in effettuati |
| Feature flag | — |

**Step:**
1. Vai a `/athlete/profile` → tab "Accessi"

**Risultato atteso:** Tabella con massimo 5 accessi (data, ora, piano). Ogni riga mostra badge "Entrata".

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

## 7. EXP — Scadenze Backoffice

### TC-EXP-001 — Pannello scadenze: certificati in scadenza

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | Marco Ricci ha cert in scadenza a 20gg; Alessia Colombo ha cert scaduto |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/members/expiry`
2. Lascia il filtro "Certificati: prossimi 30 giorni" invariato

**Risultato atteso:** Marco Ricci e Alessia Colombo appaiono nella tabella "Certificati medici in scadenza". Le date di scadenza sono corrette.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-EXP-002 — Pannello scadenze: abbonamenti in scadenza

| Campo | Valore |
|---|---|
| Persona | receptionist |
| Account | `receptionist@receptionist.receptionist` / `receptionist` |
| Precondizioni | Seeder ha creato abbonamenti in scadenza entro 7 giorni |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/members/expiry`
2. Osserva la sezione "Abbonamenti in scadenza"

**Risultato atteso:** I tesserati con abbonamento in scadenza nei prossimi 7 giorni appaiono nella tabella con data scadenza ordinata crescente.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-EXP-003 — Filtro live per nome

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | Più tesserati nella lista scadenze |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/members/expiry`
2. Digita "Ricci" nel campo di ricerca

**Risultato atteso:** Solo i record con "Ricci" nel nome rimangono visibili in entrambe le tabelle. Gli altri spariscono in tempo reale senza ricaricare la pagina.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-EXP-004 — Modifica finestra temporale

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | — |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/members/expiry`
2. Cambia la finestra certificati a "60 giorni"

**Risultato atteso:** La lista si aggiorna mostrando più tesserati (quelli con cert in scadenza entro 60 giorni, non solo 30).

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-EXP-005 — Widget scadenze nella dashboard backoffice

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | Marco Ricci (cert 20gg), almeno 1 abb. in scadenza 7gg |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/dashboard`

**Risultato atteso:** Appare il widget "Scadenze imminenti" con i contatori: N certificati in scadenza entro 30gg e M abbonamenti in scadenza entro 7gg. I link portano a `/backoffice/members/expiry`.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-EXP-006 — Permesso negato: atleta non accede a /backoffice/members/expiry

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | — |
| Feature flag | — |

**Step:**
1. Accedi come atleta
2. Naviga a `/backoffice/members/expiry`

**Risultato atteso:** Redirect a login o errore 403.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

## 8. CHK — Check-in Rapido

### TC-CHK-001 — Check-in happy path

| Campo | Valore |
|---|---|
| Persona | receptionist |
| Account | `receptionist@receptionist.receptionist` / `receptionist` |
| Precondizioni | `giovanni.ferrari@example.com` con abb. attivo, cert valido |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/checkin`
2. Digita "Ferrari" nel campo di ricerca
3. Clicca su Giovanni Ferrari
4. Clicca "Registra accesso"

**Risultato atteso:** Messaggio "Accesso registrato per Giovanni Ferrari." Il record appare nella cronologia accessi odierni con ora e nome piano.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CHK-002 — Check-in bloccato: certificato scaduto

| Campo | Valore |
|---|---|
| Persona | receptionist |
| Account | `receptionist@receptionist.receptionist` / `receptionist` |
| Precondizioni | Alessia Colombo con cert scaduto |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/checkin`
2. Cerca "Colombo"
3. Seleziona Alessia Colombo
4. Clicca "Registra accesso"

**Risultato atteso:** Messaggio errore "Certificato medico scaduto o mancante." Nessun AccessLog creato.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CHK-003 — Check-in bloccato: nessun abbonamento attivo

| Campo | Valore |
|---|---|
| Persona | receptionist |
| Account | `receptionist@receptionist.receptionist` / `receptionist` |
| Precondizioni | Tesserato con cert valido ma senza abbonamento attivo. Con i dati demo, creare manualmente o usare un tesserato senza abb. |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/checkin`
2. Seleziona un tesserato con cert valido ma senza abb. attivo
3. Clicca "Registra accesso"

**Risultato atteso:** Messaggio errore "Nessun abbonamento attivo." Nessun AccessLog creato.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CHK-004 — Check-in bloccato: accessi esauriti

| Campo | Valore |
|---|---|
| Persona | receptionist |
| Account | `receptionist@receptionist.receptionist` / `receptionist` |
| Precondizioni | Tesserato con abb. a ingressi contati e `accesses_remaining=0` **[DA CREARE - Fase 2: "Carlo Accessi" da FunctionalTestSeeder con max_accesses=10, accesses_remaining=0]** |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/checkin`
2. Cerca e seleziona "Carlo Accessi"
3. Clicca "Registra accesso"

**Risultato atteso:** Messaggio errore "Accessi esauriti." Nessun AccessLog creato.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CHK-005 — Ricerca live: minimo 2 caratteri

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | — |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/checkin`
2. Digita "F" (1 carattere)
3. Digita "Fe" (2 caratteri)

**Risultato atteso:** Step 2: nessun risultato (ricerca richiede min. 2 caratteri). Step 3: appaiono i tesserati il cui nome/cognome/email contiene "Fe".

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-CHK-006 — Permesso negato: trainer non accede a check-in

| Campo | Valore |
|---|---|
| Persona | trainer |
| Account | `trainer@trainer.trainer` / `trainer` |
| Precondizioni | — |
| Feature flag | — |

**Step:**
1. Accedi come trainer
2. Naviga a `/backoffice/checkin`

**Risultato atteso:** Errore 403 o redirect. La route richiede `role:gestore|receptionist`.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

## 9. SUB — Operazioni Abbonamenti

### TC-SUB-001 — Rinnovo rapido: bottone e pre-popolamento form

| Campo | Valore |
|---|---|
| Persona | receptionist |
| Account | `receptionist@receptionist.receptionist` / `receptionist` |
| Precondizioni | Atleta con abbonamento attivo o scaduto nella lista |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/subscriptions`
2. Individua la riga di `atleta@atleta.atleta`
3. Clicca il bottone "Rinnova"

**Risultato atteso:** Redirect a `/backoffice/subscriptions/create` con `member_id` e `plan_id` già selezionati. Il campo `expires_at` mostra la data calcolata automaticamente (starts_at + durata piano).

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-SUB-002 — Sospensione abbonamento

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | Abbonamento con status=active |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/subscriptions`
2. Individua un abbonamento attivo
3. Clicca il bottone "Sospendi" (icona fa-pause)
4. Conferma il `wire:confirm`

**Risultato atteso:** L'abbonamento passa a status `suspended`. Il badge nella lista diventa "Sospeso".

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-SUB-003 — Riattivazione abbonamento sospeso

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | Abbonamento in stato suspended (dopo TC-SUB-002) |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/subscriptions`
2. Usa il filtro "Sospesi"
3. Clicca il bottone "Riattiva" (icona fa-play)

**Risultato atteso:** L'abbonamento torna a status `active`. Scompare dal filtro "Sospesi".

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-SUB-004 — Permesso negato: receptionist non sospende

| Campo | Valore |
|---|---|
| Persona | receptionist |
| Account | `receptionist@receptionist.receptionist` / `receptionist` |
| Precondizioni | Abbonamento attivo visibile |
| Feature flag | — |

**Step:**
1. Accedi come receptionist
2. Vai a `/backoffice/subscriptions`
3. Se il bottone "Sospendi" è visibile, clicca

**Risultato atteso:** Errore 403. Il guard è `role:gestore` sulla `suspend()`.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-SUB-005 — Export CSV abbonamenti: download file

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | Almeno 5 abbonamenti nel DB |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/subscriptions`
2. Applica il filtro "Attivi"
3. Clicca il bottone "Esporta CSV"

**Risultato atteso:** Il browser avvia il download di un file `.csv` con nome `abbonamenti-YYYY-MM-DD.csv`. Il file ha BOM UTF-8, intestazioni in italiano, separatore `;`, e contiene solo gli abbonamenti attivi.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-SUB-006 — Permesso negato: receptionist non esporta abbonamenti

| Campo | Valore |
|---|---|
| Persona | receptionist |
| Account | `receptionist@receptionist.receptionist` / `receptionist` |
| Precondizioni | — |
| Feature flag | — |

**Step:**
1. Accedi come receptionist
2. Naviga a `/backoffice/subscriptions/export`

**Risultato atteso:** Errore 403. La route richiede `role:gestore`.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-SUB-007 — Filtro "Sospesi" nella lista abbonamenti

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | Almeno 1 abbonamento sospeso (dopo TC-SUB-002) |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/subscriptions`
2. Seleziona filtro "Sospesi" dal dropdown

**Risultato atteso:** Nella lista appaiono solo gli abbonamenti con status `suspended`.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

## 10. MBR — Gestione Tesserati

### TC-MBR-001 — Icona nota interna in MemberList

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | Giovanni Ferrari e Marco Ricci hanno note interne dal seeder |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/members`

**Risultato atteso:** Giovanni Ferrari e Marco Ricci hanno l'icona `fa-sticky-note` accanto al nome. L'icona mostra un tooltip con il testo della nota al passaggio del mouse. Atleti senza note non mostrano l'icona.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-MBR-002 — Export CSV tesserati: download file

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | Almeno 5 tesserati nel DB |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/members`
2. Lascia la ricerca vuota
3. Clicca "Esporta CSV"

**Risultato atteso:** Il browser avvia il download di `tesserati-YYYY-MM-DD.csv` con BOM UTF-8, separatore `;`, colonne: Cognome, Nome, Email, Telefono, Abbonamento, Scadenza abb., Cert. medico, Attivo.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-MBR-003 — Export CSV tesserati: rispetta filtro ricerca

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | — |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/members`
2. Filtra per "Ferrari"
3. Clicca "Esporta CSV"

**Risultato atteso:** Il CSV scaricato contiene solo le righe che corrispondono al filtro "Ferrari", non tutti i tesserati.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-MBR-004 — Export CSV tesserati: rispetta filtro cert

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | Tesserati con cert mancante/scaduto/in scadenza |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/members`
2. Seleziona filtro certificato "Scaduto"
3. Clicca "Esporta CSV"

**Risultato atteso:** Il CSV scaricato contiene solo i tesserati con cert scaduto.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-MBR-005 — Permesso negato: receptionist non esporta tesserati

| Campo | Valore |
|---|---|
| Persona | receptionist |
| Account | `receptionist@receptionist.receptionist` / `receptionist` |
| Precondizioni | — |
| Feature flag | — |

**Step:**
1. Accedi come receptionist
2. Naviga a `/backoffice/members/export`

**Risultato atteso:** Errore 403.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

## 11. DBC — Dashboard e Analytics

### TC-DBC-001 — PT completate per trainer in ManagerDashboard

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | `R09R31DemoSeeder` ha creato PT completate per Luca Bianchi nel mese corrente |
| Feature flag | financial_reports: ON |

**Step:**
1. Vai a `/backoffice/reports/manager`
2. Imposta il periodo al mese corrente (default)
3. Osserva la sezione "Sessioni PT per trainer"

**Risultato atteso:** Tabella con colonne "Trainer" e "Sessioni completate". Luca Bianchi appare con il numero corretto di PT completate nel periodo.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-DBC-002 — Filtro periodo su PT stats

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | PT completate in mesi diversi |
| Feature flag | financial_reports: ON |

**Step:**
1. Vai a `/backoffice/reports/manager`
2. Cambia la data di inizio a 3 mesi fa
3. Osserva la tabella PT per trainer

**Risultato atteso:** I conteggi si aggiornano riflettendo il nuovo periodo. I trainer con PT nel periodo esteso mostrano numeri più alti.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-DBC-003 — Dashboard atleta: card corsi prossimi

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | Atleta iscritto a corso futuro (dopo TC-CLS-001); flag group_classes ON |
| Feature flag | group_classes: ON |

**Step:**
1. Vai a `/athlete/` (dashboard)

**Risultato atteso:** Widget o card "Prossimi corsi" mostra l'iscrizione al corso con data e nome. Se nessuna iscrizione attiva, il widget non appare o mostra messaggio vuoto.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-DBC-004 — Dashboard atleta: PT pending badge

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | `FIX01` ha creato PT in stato pending per `atleta@atleta.atleta` |
| Feature flag | — |

**Step:**
1. Vai a `/athlete/`

**Risultato atteso:** La sezione "Prossime sessioni PT" nella dashboard mostra le PT con badge "In attesa" per quelle in stato pending.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-DBC-005 — Permesso negato: trainer non accede a ManagerDashboard

| Campo | Valore |
|---|---|
| Persona | trainer |
| Account | `trainer@trainer.trainer` / `trainer` |
| Precondizioni | — |
| Feature flag | — |

**Step:**
1. Accedi come trainer
2. Naviga a `/backoffice/reports/manager`

**Risultato atteso:** Errore 403. La route richiede `role:gestore`.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

## 12. FLG — Feature Flag

### TC-FLG-001 — Toggle group_classes via UI: effetto su atleta

| Campo | Valore |
|---|---|
| Persona | gestore + atleta |
| Account | Gestore: `admin@admin.admin`; Atleta: `atleta@atleta.atleta` |
| Precondizioni | group_classes: ON |
| Feature flag | group_classes: ON inizialmente |

**Step:**
1. Accedi come gestore
2. Vai a `/backoffice/admin/feature-flags`
3. Disattiva "Corsi collettivi" (group_classes)
4. Disconnettiti e accedi come atleta
5. Vai a `/athlete/bookings`
6. Osserva il tab "Corsi collettivi"
7. Vai a `/athlete/profile` e osserva i tab
8. Riaccedi come gestore e riattiva il flag

**Risultato atteso:** Step 5-6: il tab "Corsi collettivi" non appare in `/athlete/bookings`. Step 7: il tab "Corsi" non appare nel profilo atleta. Step 8: dopo riattivazione, entrambi tornano visibili.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-FLG-002 — Permesso negato: trainer non accede a feature-flags

| Campo | Valore |
|---|---|
| Persona | trainer |
| Account | `trainer@trainer.trainer` / `trainer` |
| Precondizioni | — |
| Feature flag | — |

**Step:**
1. Accedi come trainer
2. Naviga a `/backoffice/admin/feature-flags`

**Risultato atteso:** Errore 403. La route richiede `role:gestore`.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-FLG-003 — Flag group_classes OFF: route /athlete/bookings tab corsi ritorna 403

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | group_classes: OFF |
| Feature flag | group_classes: OFF |

**Step:**
1. Con group_classes OFF, accedi come atleta
2. Vai a `/athlete/bookings`
3. Clicca il tab "Corsi collettivi" (se visibile)
4. Se non visibile, verifica che la chiamata Livewire `enrollClass` ritorni 403

**Risultato atteso:** Il tab non appare. Se si tenta l'azione `enrollClass` direttamente (es. via devtools Livewire), la risposta è 403 (`abort_unless(Feature::active('group_classes'), 403)`).

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

## 13. OPH — Orari di Apertura

### TC-OPH-001 — Visualizzazione orari ricorrenti

| Campo | Valore |
|---|---|
| Persona | trainer |
| Account | `trainer@trainer.trainer` / `trainer` |
| Precondizioni | Orari inseriti da OpeningHoursSeeder (solo su DB inizializzato da zero) |
| Feature flag | — |

**Step:**
1. Accedi come trainer
2. Vai a `/backoffice/settings/opening-hours`

**Risultato atteso:** Tabella con gli slot ricorrenti settimanali (giorno, ora inizio, ora fine). Tabella con eccezioni/festività. Nessun pulsante di modifica visibile (trainer ha solo lettura).

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-OPH-002 — Aggiunta slot ricorrente

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | — |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/settings/opening-hours`
2. Clicca "Aggiungi slot"
3. Seleziona: Mercoledì, 08:00-13:00
4. Salva

**Risultato atteso:** Messaggio flash "Slot aggiunto." Il nuovo slot appare nella tabella degli orari ricorrenti alla riga Mercoledì.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-OPH-003 — Modifica slot ricorrente inline

| Campo | Valore |
|---|---|
| Persona | receptionist |
| Account | `receptionist@receptionist.receptionist` / `receptionist` |
| Precondizioni | Slot ricorrente esistente |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/settings/opening-hours`
2. Clicca "Modifica" su uno slot ricorrente
3. Cambia l'orario di fine a 14:00
4. Salva

**Risultato atteso:** Messaggio flash "Slot aggiornato." Il nuovo orario appare nella tabella.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-OPH-004 — Eliminazione slot ricorrente

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | Slot ricorrente esistente |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/settings/opening-hours`
2. Clicca "Elimina" su uno slot

**Risultato atteso:** Messaggio flash "Slot eliminato." Il record scompare dalla tabella.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-OPH-005 — Aggiunta eccezione (giorno chiuso)

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | — |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/settings/opening-hours`
2. Clicca "Aggiungi eccezione"
3. Inserisci: data domani, Chiuso (deseleziona "Aperto"), Note "Giorno festivo test"
4. Salva

**Risultato atteso:** Messaggio flash "Eccezione aggiunta." La riga appare nella tabella eccezioni con la data e la nota.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-OPH-006 — Aggiunta eccezione: validazione end_time > start_time

| Campo | Valore |
|---|---|
| Persona | gestore |
| Account | `admin@admin.admin` / `admin` |
| Precondizioni | — |
| Feature flag | — |

**Step:**
1. Vai a `/backoffice/settings/opening-hours`
2. Aggiungi eccezione con is_open=true, ora inizio 14:00, ora fine 09:00

**Risultato atteso:** Errore di validazione "after:start" sull'ora di fine. Il record non viene creato.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

### TC-OPH-007 — Permesso negato: atleta non accede agli orari

| Campo | Valore |
|---|---|
| Persona | atleta |
| Account | `atleta@atleta.atleta` / `atleta` |
| Precondizioni | — |
| Feature flag | — |

**Step:**
1. Accedi come atleta
2. Naviga a `/backoffice/settings/opening-hours`

**Risultato atteso:** Redirect a login o errore 403.

**Esito:** ☐ Pass ☐ Fail ☐ Skip — Note: ___

---

## 14. REG — Checklist Regressione

> Verifiche rapide sulle aree adiacenti. Eseguire dopo le modifiche ai dati demo.

| ID | Area | Cosa verificare | Account | Esito |
|---|---|---|---|---|
| REG-001 | PT Booking | Creazione prenotazione PT funziona ancora | `atleta@atleta.atleta` | ☐ |
| REG-002 | PT Booking | Cancellazione prenotazione PT | `atleta@atleta.atleta` | ☐ |
| REG-003 | PT Booking | Conflitto trainer: PT trainer con corso nello stesso slot — vedi **F-02** (gap da verificare) | `atleta@atleta.atleta` | ☐ |
| REG-004 | Dashboard atleta | Caricamento `/athlete/` senza errori | `atleta@atleta.atleta` | ☐ |
| REG-005 | Dashboard atleta | Sezione PT pending visibile con badge | `atleta@atleta.atleta` | ☐ |
| REG-006 | Messaggistica | Invio messaggio trainer → atleta | `trainer@trainer.trainer` | ☐ |
| REG-007 | Messaggistica | Atleta riceve e vede messaggio in `/athlete/messages` | `atleta@atleta.atleta` | ☐ |
| REG-008 | Profilo atleta | Tab info: modifica nome e email | `atleta@atleta.atleta` | ☐ |
| REG-009 | Mesociclo | Lista mesocicli atleta visibile (filtrata per trainer) | `trainer@trainer.trainer` | ☐ |
| REG-010 | Mesociclo | Trainer non vede mesocicli di altri trainer | `trainer2@trainer.trainer` | ☐ |
| REG-011 | Check-in | Happy path check-in dopo sospensione/riattivazione abb. | receptionist | ☐ |
| REG-012 | Abbonamenti | Filtro "Attivi" nella lista abbonamenti | `admin@admin.admin` | ☐ |
| REG-013 | Tesserati | Ricerca live per cognome in MemberList | `admin@admin.admin` | ☐ |
| REG-014 | Scadenze | Widget scadenze corretto dopo rinnovo abbonamento | `admin@admin.admin` | ☐ |
| REG-015 | Feature flags | Lista flag visibile solo al gestore | `admin@admin.admin` | ☐ |
| REG-016 | Notifiche | Badge sidebar aggiornato dopo markAllRead | `atleta@atleta.atleta` | ☐ |
| REG-017 | Export | Export tesserati con filtro cert "Mancante" produce CSV corretto | `admin@admin.admin` | ☐ |
| REG-018 | Sidebar backoffice | Sottomenu Corsi ha 3 voci: Occorrenze, Palinsesto, Catalogo | `admin@admin.admin` | ☐ |
| REG-019 | Corsi collettivi | Lista occorrenze si filtra per stato (planned/completed/cancelled) | `admin@admin.admin` | ☐ |
| REG-020 | Orari apertura | Atleta e trainer vedono la pagina in sola lettura | `trainer@trainer.trainer` | ☐ |

---

## Riepilogo casi di test

| Area | Codice | N. casi |
|---|---|---|
| CLS — Corsi Collettivi | TC-CLS-001..020 | 20 |
| SCH — Palinsesto | TC-SCH-001..005 | 5 |
| CAT — Catalogo Corsi | TC-CAT-001..006 | 6 |
| GEN — Generazione Occorrenze | TC-GEN-001..003 | 3 |
| NOT — Notifiche Atleta | TC-NOT-001..006 | 6 |
| PRF — Profilo Atleta | TC-PRF-001..010 | 10 |
| EXP — Scadenze Backoffice | TC-EXP-001..006 | 6 |
| CHK — Check-in Rapido | TC-CHK-001..006 | 6 |
| SUB — Operazioni Abbonamenti | TC-SUB-001..007 | 7 |
| MBR — Gestione Tesserati | TC-MBR-001..005 | 5 |
| DBC — Dashboard e Analytics | TC-DBC-001..005 | 5 |
| FLG — Feature Flag | TC-FLG-001..003 | 3 |
| OPH — Orari Apertura | TC-OPH-001..007 | 7 |
| REG — Regressione | REG-001..020 | 20 |
| **TOTALE** | | **109** |

---

## Casi con dati da creare (Fase 2)

I seguenti test richiedono dati marcati `[DA CREARE - Fase 2]` e non sono eseguibili fino al completamento del `FunctionalTestSeeder`:

| TC | Scenario |
|---|---|
| TC-CLS-009 | Corso al completo (capacity=3, 3 confirmed) — "Yoga Full" |
| TC-CLS-012 | Atleta in waitlist dopo corso pieno (Federica in waitlist su "Yoga Full") |
| TC-CHK-004 | Tesserato con accesses_remaining=0 ("Carlo Accessi") |

Il test TC-CLS-006 e TC-CLS-011 richiedono creazione manuale via Tinker (non seedabili per natura time-relative).

Il finding **F-02** (overlap PT+corso atleta non controllato in `ClassBookingService::enroll`) è verificabile con REG-003 ma potrebbe passare erroneamente: annotare il risultato come "gap funzionale noto" se la prenotazione doppia riesce.
