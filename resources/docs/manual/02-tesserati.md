# Tesserati e anagrafica

## A cosa serve

La sezione tesserati (`/backoffice/members`) raccoglie l'anagrafica di tutti i
soci della palestra. Per ogni tesserato si gestiscono i dati personali, lo
stato di attivita', il certificato medico, le note interne e l'eventuale
account per l'app atleta. E' il punto di partenza per qualsiasi operazione
che riguardi un singolo socio.

## Chi la vede

La lista tesserati e' accessibile a gestore e receptionist. Il trainer non ha
accesso diretto a questa sezione. La creazione e la modifica seguono le stesse
restrizioni. L'eliminazione di un tesserato e l'esportazione CSV sono
riservate al solo gestore.

## Flusso operativo: registrare un nuovo tesserato

1. Vai a Tesserati e clicca **Nuovo tesserato**.
2. Compila i campi obbligatori: Cognome, Nome, Email, Scadenza certificato
   medico.
3. Se il tesserato deve accedere all'app atleta, spunta **Crea account accesso
   app** e inserisci una password di almeno 8 caratteri. Il sistema crea
   automaticamente un account utente con ruolo `atleta` e lo collega al
   tesserato.
4. Salva. Il tesserato compare subito nella lista.

Dopo la registrazione occorre creare un abbonamento separatamente (sezione
Abbonamenti) per abilitare il check-in.

## Flusso operativo: modificare un tesserato

1. Cerca il tesserato con il campo di ricerca (filtra su nome, cognome, email).
2. Clicca l'icona matita nella riga corrispondente.
3. Modifica i campi e salva. La scadenza del certificato medico e' il campo
   piu' frequentemente da aggiornare: inserisci la nuova data ogni volta che
   il tesserato porta il rinnovo.

## Note interne

Il campo **Note** nel modulo tesserato e' riservato allo staff: non e' visibile
all'atleta nell'app. Se un tesserato ha note compilate, nella lista compare
un'icona a forma di foglietto giallo sulla riga. Passa il puntatore sull'icona
per vedere l'anteprima. Usa questo campo per segnalare esigenze particolari,
esoneri medici o qualsiasi informazione operativa.

## Filtro certificati medici

La lista supporta un filtro rapido sullo stato del certificato medico:
- **Tutti**: mostra l'intera lista.
- **Scaduto o mancante**: mostra i tesserati senza data di scadenza oppure con
  certificato gia' scaduto. Questi tesserati non possono essere ammessi in
  struttura.
- **In scadenza (30 giorni)**: mostra i tesserati il cui certificato scade
  entro un mese. Utile per contattarli in anticipo.

## Esportazione CSV

Il gestore puo' scaricare la lista tesserati in formato CSV cliccando il
pulsante **Esporta CSV** nella lista. Il file rispetta il filtro di ricerca
e il filtro certificati attivi al momento dell'export. Le colonne esportate
sono: Cognome, Nome, Email, Telefono, Abbonamento, Scadenza abbonamento,
Certificato medico, Attivo.

## Errori comuni

**Impossibile salvare: email gia' in uso**: ogni email deve essere univoca nel
sistema. Se un tesserato ha gia' un account o e' stato registrato in precedenza
con la stessa email, comparira' un errore di validazione. Verifica prima con
la ricerca.

**L'atleta non riesce ad accedere all'app**: controlla che il flag `is_active`
sia attivo e che l'account sia stato creato (campo email compilato nella scheda
e password impostata). Se l'account non esiste, modifica il tesserato e usa
la funzione di creazione account.

**La data del certificato non viene accettata**: il formato atteso e' gg/mm/aaaa
(selezionabile dal date-picker). Non inserire date nel passato se vuoi evitare
che il tesserato risulti subito bloccato.
