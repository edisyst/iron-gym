# Comunicazione e campagne

## A cosa serve

La sezione comunicazione (`/backoffice/communications/campaign`) permette di
inviare messaggi di massa a gruppi di tesserati via email o SMS. Si possono
usare template preconfigurati oppure comporre il messaggio al momento. Il
sistema usa una coda asincrona: l'invio avviene in background senza bloccare
il browser.

## Chi la vede

Solo il gestore ha accesso alla pagina campagne. Il receptionist e il trainer
non hanno accesso.

## Messaggistica interna trainer-atleta

La messaggistica one-to-one tra trainer e atleta avviene aprendo il profilo
dell'atleta in backoffice (Tesserati → profilo atleta) e selezionando il tab
**Messaggi**. La route e' `/backoffice/athletes/{id}/messages`, accessibile a
gestore e trainer. Questo modulo e' gated dal flag `messaging`
(Impostazioni → Funzioni): con il flag OFF il tab non appare.

## Flusso operativo: inviare una campagna

1. Vai a Comunicazione → **Campagna**.
2. Seleziona il **filtro destinatari**:
   - **Tutti**: tutti i tesserati attivi.
   - **Abbonamento attivo**: solo chi ha un abbonamento in corso.
   - **Abbonamento scaduto**: chi non ha abbonamento attivo.
   - (altri filtri disponibili nel componente)
3. Clicca **Vedi destinatari** per controllare l'elenco prima di inviare.
4. Seleziona il **canale**: email o SMS.
5. Seleziona un **template** dal menu a tendina (il form si precompila con
   oggetto e corpo del template) oppure scrivi oggetto e corpo manualmente.
6. Clicca **Invia campagna**. Il sistema dispatcha un job asincrono
   (`SendCampaignMessages`) che invia i messaggi in background.
7. La pagina mostra un messaggio di conferma. I log di invio sono registrati
   in `CommunicationLog`.

## Template di comunicazione

I template sono predefiniti in database. Non esiste una UI di gestione
template nel backoffice attuale: per aggiungere o modificare template occorre
farlo direttamente in database o via seeder.

## Errori comuni

**Il corpo e' vuoto: errore di validazione**: il campo corpo e' obbligatorio.
Se stai usando un template, verifica che il template abbia il campo `body`
compilato.

**L'invio non sembra avvenire**: il job e' in coda. Verifica che il worker
della coda sia in esecuzione (`php artisan queue:work redis`). In sviluppo,
usa `php artisan queue:work` nel terminale.

**Il flag messaging e' OFF e i trainer non vedono i messaggi**: attiva il
flag `messaging` in Impostazioni → Funzioni. La campagna di massa non e'
influenzata da questo flag (e' separata dalla messaggistica one-to-one).
