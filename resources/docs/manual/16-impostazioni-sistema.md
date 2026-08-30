# Impostazioni di sistema

## A cosa serve

La sezione Impostazioni (`/backoffice/settings`) e' il pannello di controllo
centrale del backoffice. Contiene due tab:

- **Funzioni**: gestione dei feature flag (attivazione/disattivazione moduli
  e funzionalita').
- **Manuale**: questo manuale operativo.

## Chi la vede

Solo il gestore. Receptionist e trainer non hanno accesso.

## Tab Funzioni — feature flags

I flag sono organizzati in tre gruppi:

**Moduli** — funzionalita' che la palestra puo' abilitare o disabilitare per
tutti gli utenti:

| Flag | Default | Effetto |
|---|---|---|
| Corsi collettivi | OFF | Attiva il modulo prenotazioni corsi (atleta + backoffice) |
| Messaggistica | ON | Attiva thread messaggi trainer-atleta |
| Prenotazioni PT | ON | Attiva prenotazione PT lato atleta |

**Sessione atleta** — funzioni nel flusso di allenamento:

| Flag | Default | Effetto |
|---|---|---|
| Check pre-sessione | ON | 4 domande prima di avviare la sessione |
| Sostituzione esercizio | ON | L'atleta puo' sostituire esercizi durante la sessione |
| Riepilogo sessione | ON | Schermata recap post-sessione |
| Record personali | ON | Rilevamento e visualizzazione PR |
| Volume settimanale | ON | Dashboard volume per muscolo nell'app atleta |
| Calcolatore dischi | ON | Suggerisce combinazione dischi (placeholder futuro) |

**Sistema** — flag operativi e infrastrutturali:

| Flag | Default | Effetto |
|---|---|---|
| Report finanziari | ON | Dashboard KPI e report economici (solo gestore) |
| Motore di periodizzazione | ON | Progressione automatica set nei mesocicli |
| Notifiche push | OFF | Push web per PWA (richiede VAPID configurato) |
| Invii verso l'esterno | ON | Kill switch globale email/push schedulati |
| Feedback in-app | OFF | Widget feedback utenti nel layout |

## Come modificare un flag

1. Apri Impostazioni → **Funzioni**.
2. Individua il flag da modificare nella tabella del gruppo corretto.
3. Clicca il toggle ON o OFF.
4. Conferma nel dialogo di conferma che appare.
5. Il cambio ha effetto immediato per tutti gli utenti della palestra.

Ogni modifica toglie la voce dalla cache Pennant (`Feature::purge`) e aggiorna
la tabella `settings` nel database. Non e' necessario riavviare l'applicazione.

## Flag e sezioni del manuale

Le sezioni del manuale relative a funzionalita' gated mostrano un badge
**ON** o **OFF** accanto al titolo nella sidebar, riflettendo lo stato
corrente del flag. Le sezioni con badge OFF descrivono funzionalita'
disabilitate: il loro contenuto e' visibile per pianificare l'attivazione.

## Avvertenze

**Disattivare "Invii verso l'esterno"** blocca email transazionali (promemoria
corsi, cancellazioni) e push schedulati. Usalo solo per manutenzione o in
ambiente di test per evitare invii accidentali.

**Disattivare "Prenotazioni PT"** con `pt_bookings = OFF` e
`group_classes = OFF` simultaneamente rende la pagina prenotazioni
inaccessibile agli atleti (403). Almeno uno dei due moduli deve essere
attivo perche' la pagina esista.

**Il flag "Record personali" OFF** non cancella i PR gia' rilevati in
database: il rilevamento continua a girare in background, solo il toast
e la pagina storico vengono nascosti all'atleta.
