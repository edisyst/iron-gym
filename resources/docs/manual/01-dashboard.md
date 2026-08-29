# Dashboard

## A cosa serve

La dashboard e' la prima pagina che appare dopo il login al backoffice. Mostra
in forma compatta i numeri operativi piu' importanti: tesserati attivi, accessi
odierni, segnalazioni certificati medici e abbonamenti in scadenza. L'obiettivo
e' dare al gestore e allo staff una visione rapida della situazione senza
entrare nelle singole sezioni.

## Chi la vede

La dashboard e' accessibile a tutti i ruoli del backoffice: gestore, trainer e
receptionist. Alcuni contatori sono visibili solo a chi ha il permesso
`view-access-logs` (gestore e receptionist): il trainer vede la propria area
operativa ma non i dati di accesso generali.

## Contatori principali

- **Tesserati attivi**: numero di tesserati con il campo `is_active` a true.
- **Abbonamenti in scadenza**: abbonamenti attivi con scadenza entro 30 giorni.
- **Accessi oggi** (gestore e receptionist): ingressi registrati nella giornata corrente.
- **Problemi certificati**: tesserati attivi senza certificato medico o con
  certificato in scadenza entro 30 giorni.

## Widget scadenze imminenti

Se ci sono certificati medici in scadenza entro 30 giorni oppure abbonamenti
in scadenza entro 7 giorni, la dashboard mostra un widget di allerta con i
relativi contatori e un collegamento diretto al pannello scadenze. Quando
entrambi i valori sono a zero il widget non appare.

## Flusso operativo tipico

1. Accedi al backoffice: la dashboard e' la pagina di atterraggio predefinita.
2. Controlla i contatori: un numero elevato di problemi certificati richiede
   attenzione prima di ammettere ingressi.
3. Se compare il widget scadenze, segui il link al pannello per vedere i
   nomi specifici e agire (contatta il tesserato, aggiorna il certificato,
   rinnova l'abbonamento).
4. Il contatore accessi odierni ti conferma che i check-in stanno avvenendo
   regolarmente.

## Errori comuni

**Il contatore tesserati attivi sembra troppo alto**: verifica che i tesserati
non piu' frequentanti abbiano il flag `is_active` disattivato (nella scheda
tesserato, sezione modifica). Il contatore include tutti i tesserati attivi
indipendentemente dall'abbonamento.

**Il widget scadenze non compare pur sapendo che ci sono scadenze**: controlla
che la finestra temporale nel pannello scadenze coincida con i valori attesi
(default 30 giorni per certificati, 7 giorni per abbonamenti). Il widget usa
le stesse soglie fisse.
