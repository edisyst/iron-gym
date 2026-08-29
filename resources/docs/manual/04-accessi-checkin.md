# Accessi e check-in

## A cosa serve

Il check-in rapido (`/backoffice/checkin`) permette di registrare l'ingresso di
un tesserato in palestra in pochi secondi. La pagina verifica automaticamente
le condizioni di accesso (certificato medico, abbonamento attivo, ingressi
residui) e registra l'accesso nel log. La cronologia degli accessi odierni e'
visibile nella parte inferiore della stessa pagina.

## Chi la vede

La pagina check-in e' accessibile a gestore, receptionist e trainer. Tutti i
ruoli possono registrare ingressi.

## Flusso operativo: registrare un ingresso

1. Vai a Check-in dal menu laterale.
2. Inizia a digitare il nome, cognome o email del tesserato nel campo di ricerca
   (minimo 2 caratteri).
3. Compare un elenco dei tesserati attivi corrispondenti. Clicca il nome
   desiderato.
4. Il campo viene compilato con il cognome e nome del tesserato selezionato.
5. Clicca **Registra accesso**.
6. Se l'accesso e' valido, compare un messaggio verde di conferma con il nome
   del tesserato. Il campo si azzera e sei pronto per il prossimo ingresso.
7. Se ci sono problemi, compare un messaggio di errore rosso. Non viene
   registrato nessun accesso.

## Controlli eseguiti al check-in

Prima di registrare l'accesso il sistema verifica in sequenza:

1. **Certificato medico valido**: la data di scadenza deve essere futura. Se il
   certificato e' scaduto o mancante, l'accesso viene bloccato con il messaggio
   "Certificato medico scaduto o mancante."

2. **Abbonamento attivo**: deve esistere almeno un abbonamento con stato
   `active`. Se non esiste, il messaggio e' "Nessun abbonamento attivo."

3. **Ingressi residui**: se il piano ha un numero massimo di ingressi e il
   contatore e' a zero, l'accesso viene bloccato con il messaggio "Accessi
   esauriti."

Se tutti i controlli passano, il sistema incrementa il contatore `accesses_used`
e decrementa `accesses_remaining` (solo per piani a ingressi), poi crea il
record nel log.

## Cronologia accessi odierni

La parte inferiore della pagina mostra gli ultimi 10 ingressi registrati oggi,
in ordine dal piu' recente. Per ogni accesso sono visibili: ora, nominativo,
piano abbonamento e l'utente staff che ha eseguito il check-in.

## Storico completo accessi

Il log completo degli accessi di tutti i tesserati e' disponibile in Accessi
dal menu laterale. E' accessibile a gestore e receptionist. Da qui e' possibile
cercare gli accessi per tesserato e filtrare per data.

## Errori comuni

**La ricerca non trova il tesserato**: verifica che il tesserato abbia il campo
`is_active` attivo nella scheda anagrafica. La ricerca esclude i tesserati
disattivati. In alternativa verifica l'ortografia o prova con l'email.

**Il messaggio di errore scompare prima che sia leggibile**: i messaggi rimangono
visibili finche' non si inizia una nuova ricerca. Non digitare nel campo prima
di aver letto il messaggio.

**Il contatore ingressi non si aggiorna subito nel profilo atleta**: il profilo
atleta legge i dati in tempo reale. Se l'atleta e' gia' aperto su un dispositivo,
aggiorna la pagina.
