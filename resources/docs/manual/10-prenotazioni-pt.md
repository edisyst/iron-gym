# Prenotazioni PT

## A cosa serve

La sezione Prenotazioni PT (`/backoffice/calendar/bookings`) raccoglie tutte
le sessioni di personal training prenotate dagli atleti o create dallo staff.
Il trainer gestisce le richieste in arrivo (conferma o annullamento); il
gestore ha visibilita' su tutto lo staff.

## Chi la vede

Gestore e trainer. Il receptionist non ha accesso diretto. Il trainer vede
solo le prenotazioni assegnate a lui; il gestore vede tutte.

## Stati di una prenotazione

| Stato | Significato |
|---|---|
| `pending` | Richiesta inviata dall'atleta, in attesa di conferma |
| `confirmed` | Confermata dal trainer o dal gestore |
| `cancelled` | Annullata (con motivo) |
| `completed` | Sessione svoltasi |
| `no_show` | L'atleta non si e' presentato |

## Flusso operativo: gestire le prenotazioni

1. Apri la lista prenotazioni dalla voce **Prenotazioni PT** nel menu.
2. Filtra per data, trainer o stato se necessario.
3. Le prenotazioni in stato `pending` richiedono azione: clicca **Conferma**
   per accettarle. La conferma non invia notifiche automatiche.
4. Per annullare una prenotazione, clicca **Annulla**: si apre un pannello
   che richiede un motivo (minimo 5 caratteri). L'annullamento cambia lo
   stato in `cancelled` e registra il motivo.
5. Una prenotazione annullata puo' essere ripristinata in `pending` con il
   pulsante **Ripristina** (visibile solo sulle prenotazioni cancellate).

## Filtri disponibili

- **Data**: mostra le prenotazioni per un giorno specifico.
- **Trainer**: filtra per trainer (il trainer loggato vede solo le proprie
  anche senza filtro).
- **Stato**: filtra per `pending`, `confirmed`, `cancelled`, `completed`,
  `no_show`.
- **Ricerca**: per cognome o nome del tesserato.

## Report sessioni PT completate

Nel report finanziario (`/backoffice/reports/manager`, solo gestore con flag
`financial_reports` attivo) e' presente una tabella con le sessioni PT
completate per trainer nel periodo selezionato, utile per la rendicontazione.

## Errori comuni

**Non riesco ad annullare: errore "Motivo troppo breve"**: il motivo deve
avere almeno 5 caratteri. Inserisci una descrizione sintetica ma non vuota.

**La prenotazione e' in `completed` e non e' modificabile**: le prenotazioni
completate non possono essere annullate o ripristinate. Contatta il gestore
per eventuali correzioni manuali.

**L'atleta non riesce a prenotare dall'app**: verifica che il flag
`pt_bookings` sia attivo in Impostazioni → Funzioni e che il trainer abbia
slot di disponibilita' configurati per il giorno richiesto.
