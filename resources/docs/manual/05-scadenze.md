# Scadenze

## A cosa serve

Il pannello scadenze (`/backoffice/members/expiry`) mostra in un'unica vista
tutti i certificati medici e gli abbonamenti in procinto di scadere. L'obiettivo
e' consentire a gestore e receptionist di intervenire in anticipo: contattare
i tesserati, raccogliere i rinnovi dei certificati e proporre il rinnovo
dell'abbonamento prima che scadano.

## Chi la vede

Il pannello e' accessibile a gestore e receptionist. Il trainer non ha accesso.

## Due tabelle

La pagina contiene due tabelle indipendenti:

**Certificati medici in scadenza**: mostra i tesserati con `medical_cert_expiry`
valorizzato e non nullo, la cui scadenza cade entro i prossimi `N` giorni
(default 30). Le righe sono ordinate per data di scadenza crescente: i piu'
urgenti appaiono per primi. Per ogni riga sono visibili cognome, nome, email e
data di scadenza del certificato.

**Abbonamenti in scadenza**: mostra gli abbonamenti attivi con scadenza entro i
prossimi `N` giorni (default 7). Per ogni riga sono visibili tesserato, piano,
data di scadenza. La colonna azioni include il pulsante Rinnova per avviare
subito il rinnovo rapido.

## Finestra temporale

Entrambe le tabelle hanno un campo numerico per modificare la finestra in giorni.
Modificando il valore (ad esempio da 7 a 14) la tabella si aggiorna
immediatamente senza ricaricare la pagina. Il valore non viene salvato: ogni
apertura riparte dai default (30 giorni per i certificati, 7 per gli
abbonamenti).

## Ricerca

Il campo di ricerca filtra entrambe le tabelle contemporaneamente per cognome,
nome o email. Utile per verificare la situazione di un singolo tesserato senza
sfogliare l'elenco completo.

## Widget nella dashboard

Se ci sono certificati in scadenza entro 30 giorni oppure abbonamenti in
scadenza entro 7 giorni, la dashboard backoffice mostra un riquadro di allerta
con i contatori. Il link nel riquadro porta direttamente al pannello scadenze.
Il riquadro scompare quando entrambi i valori sono a zero.

## Flusso operativo tipico

1. Apri il pannello scadenze ogni mattina (o verifica il widget in dashboard).
2. Scorri la tabella certificati: per ogni tesserato in scadenza imminente,
   contattalo e chiedigli di portare il nuovo certificato alla prossima visita.
3. Scorri la tabella abbonamenti: per i tesserati in scadenza, proponi il
   rinnovo e usa il pulsante Rinnova per avviare il modulo precompilato.
4. Dopo aver aggiornato il certificato medico di un tesserato (nella scheda
   anagrafica), la riga scompare automaticamente dalla tabella appena la nuova
   data supera la soglia.

## Errori comuni

**Un tesserato con certificato scaduto non compare**: la tabella mostra solo i
tesserati con `medical_cert_expiry` valorizzato. I tesserati senza data
(campo vuoto) non compaiono in questa tabella. Per trovarli usa il filtro
"Scaduto o mancante" nella lista tesserati.

**La tabella abbonamenti e' vuota ma so che ci sono abbonamenti in scadenza**:
verifica che lo stato dell'abbonamento sia `active`. Gli abbonamenti sospesi
o scaduti non compaiono in questa tabella. Allarga anche la finestra temporale
se la scadenza e' tra piu' di 7 giorni.
