# Calendario e disponibilita' trainer

## A cosa serve

La sezione calendario gestisce la disponibilita' settimanale dei trainer e
le prenotazioni PT. Il trainer configura i propri slot orari ricorrenti e le
eccezioni puntuali; la disponibilita' e' poi usata dal sistema per verificare
se uno slot e' libero quando un atleta prenota una sessione PT.

## Chi la vede

**Disponibilita' trainer** (`/backoffice/calendar/availability`): accessibile
al trainer loggato. Ogni trainer gestisce solo la propria disponibilita'.
**Calendario** (`/backoffice/calendar`): panoramica settimanale accessibile
a gestore e trainer.

## Gestione disponibilita' settimanale ricorrente

Il trainer accede alla pagina dalla voce **Disponibilita'** nel menu laterale.

1. Clicca **Aggiungi slot** per aprire il form.
2. Seleziona il giorno della settimana (lunedi' = 0, domenica = 6).
3. Imposta orario di inizio e fine (formato HH:MM). L'orario di fine deve
   essere successivo all'inizio.
4. Salva. Lo slot appare nella tabella degli slot ricorrenti.
5. Per rimuovere uno slot, clicca l'icona elimina nella riga corrispondente.

Gli slot ricorrenti si ripetono ogni settimana finche' non vengono eliminati
o sovrascritti da un'eccezione puntuale.

## Eccezioni puntuali (override)

Per modificare la disponibilita' in una data specifica (ferie, sostituzione,
apertura straordinaria):

1. Clicca **Aggiungi eccezione**.
2. Seleziona la data specifica (deve essere uguale o successiva a oggi).
3. Imposta orario di inizio e fine.
4. Seleziona se il trainer e' **disponibile** o **non disponibile** in quella
   fascia (toggle `is_available`).
5. Aggiungi note opzionali (es. "sostituisce Martina") e salva.

Un override con `is_available = false` blocca lo slot corrispondente anche
se esiste uno slot ricorrente per quel giorno.

## Prenotazioni PT backoffice

Le prenotazioni create dagli atleti o dallo staff si trovano in
**Prenotazioni PT** (`/backoffice/calendar/bookings`), accessibile a gestore
e trainer. Vedi la sezione "Prenotazioni PT" del manuale per i dettagli.

## Errori comuni

**Gli slot non appaiono nella lista dopo il salvataggio**: ricarica la pagina
se Livewire non aggiorna la lista. Il dato e' gia' salvato nel database.

**Un atleta dice che non trova slot disponibili**: verifica che il trainer
abbia slot ricorrenti configurati per quel giorno della settimana e che non
esistano override `is_available = false` per le date cercate.
