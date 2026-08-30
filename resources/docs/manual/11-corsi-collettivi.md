# Corsi collettivi

## A cosa serve

Il modulo corsi collettivi gestisce le lezioni di gruppo della palestra:
yoga, spinning, zumba e qualsiasi altra attivita' con capienza limitata.
La struttura e' a tre livelli: **definizione del corso** (catalogo,
`GroupClass`), **palinsesto** (`ClassSchedule`, ricorrenza settimanale) e
**occorrenza** (`ClassOccurrence`, istanza datata). Gli atleti prenotano
le occorrenze singole, non il palinsesto.

Questo modulo e' attivo solo se il flag `group_classes` e' ON in
Impostazioni → Funzioni.

## Chi la vede

- **Catalogo corsi**: solo gestore (crea, modifica, disattiva definizioni).
- **Palinsesto**: gestore e trainer (gestione ricorrenze settimanali).
- **Occorrenze**: gestore, trainer e receptionist (gestione istanze giornaliere,
  check-in presenze, annullamenti).

## Flusso operativo: creare un nuovo corso

1. Vai a Corsi → **Catalogo** e clicca **Nuovo corso**.
2. Compila: nome, slug (identificatore URL), descrizione, durata in minuti,
   capienza default, sala e colore calendario. Salva.
3. Vai a **Palinsesto** e aggiungi una ricorrenza per il corso appena creato:
   seleziona giorno della settimana, orario, trainer (opzionale), date di
   validita'. Salva.
4. Esegui il command `classes:generate-occurrences` (schedulato
   automaticamente) oppure attendi la generazione automatica giornaliera.
   Le occorrenze future appaiono nella lista **Occorrenze**.

## Gestione occorrenze

Dalla lista occorrenze:
- **Filtra** per data, corso, stato (`planned`, `cancelled`, `completed`).
- **Annulla** un'occorrenza: inserisci il motivo; tutti i partecipanti
  confermati ricevono una notifica e la prenotazione viene annullata.
- **Completa** un'occorrenza: marca la sessione come svoltasi. Da quel momento
  e' possibile registrare le presenze.
- **Presenze**: per ogni partecipante iscritto, segna `attended` (presente)
  o `no_show` (assente). Il receptionist puo' eseguire il check-in diretto
  dalla lista partecipanti.

## Lista d'attesa

Quando un'occorrenza e' al limite di capienza, le prenotazioni successive
vanno in lista d'attesa (`waitlisted`). Se un partecipante confermato
cancella, il primo in lista d'attesa viene promosso automaticamente a
`confirmed`.

## Finestre di prenotazione e cancellazione

I parametri `booking_opens_days` e `booking_closes_minutes` definiscono
quando gli atleti possono prenotare (X giorni prima, fino a Y minuti prima
dell'inizio). Il parametro `free_cancel_hours` definisce la finestra di
cancellazione gratuita. Questi valori sono configurabili nel componente
`Athlete\Booking`.

## Errori comuni

**Il modulo non appare nella sidebar**: il flag `group_classes` e' OFF. Attivalo
in Impostazioni → Funzioni (solo gestore).

**Le occorrenze non si generano**: verifica che lo scheduler sia in esecuzione
(`php artisan schedule:work` in sviluppo). Il command
`classes:generate-occurrences` crea occorrenze idempotentemente: eseguirlo
piu' volte non crea duplicati.

**L'atleta e' nella lista d'attesa ma l'occorrenza ha posti liberi**: un
posto libero fisico non equivale a `available_spots > 0` se la capienza
dell'occorrenza e' stata ridotta manualmente dopo la creazione.
