# Progressione e volume landmarks

## A cosa serve

Il motore di progressione (flag `periodization_engine`) automatizza l'aumento
del volume settimanale nel mesociclo in base ai feedback dell'atleta e ai
volume landmarks personalizzati. I volume landmarks sono i valori di
riferimento per muscolo che definiscono il range ottimale di lavoro: MEV
(Minimum Effective Volume), MAV (Maximum Adaptive Volume — range min/max) e
MRV (Maximum Recoverable Volume).

## Come raggiungerla

Questa sezione non ha una voce di menu autonoma. Si accede in due modi:

- **Progressione e volume** (per mesociclo): Training → Mesocicli → clicca sul
  nome del mesociclo → pagina dettaglio.
- **Volume landmarks** (per atleta): Training → Mesocicli → clicca sul profilo
  dell'atleta → link "Volume landmarks" nel pannello laterale del profilo.

## Chi la vede

La pagina di dettaglio mesociclo e il gestore volume landmarks sono accessibili
a gestore e trainer. Il trainer accede solo ai propri mesocicli.

## Volume landmarks

I landmark di default sono definiti in `config/volume_landmarks.php`. Per
personalizzarli su un atleta specifico:

1. Apri il profilo atleta in backoffice.
2. Clicca il link **Volume landmarks** nella sidebar del profilo.
3. La pagina mostra i valori per muscolo raggruppati per gruppo muscolare.
   I valori sono precompilati con i default di configurazione se non sono
   stati mai personalizzati.
4. Modifica MEV, MAV min, MAV max e MRV per ogni muscolo, poi salva.
5. Il pulsante **Ripristina default** rimuove tutte le personalizzazioni
   e ripristina i valori da configurazione.

## Progressione automatica

Nel dettaglio mesociclo:

1. Seleziona la settimana corrente (la pagina propone automaticamente quella
   con piu' sessioni completate).
2. Controlla la tabella volume: colonne hard set vs. MEV/MAV/MRV per ogni
   muscolo, con indicatore di stato (sotto MEV, nel range MAV, sopra MAV).
3. Clicca **Applica progressione** per avanzare alla settimana successiva.
   Il servizio legge i feedback dell'atleta e decide quante serie aggiungere
   muscolo per muscolo (da MEV verso MRV).
4. Il risultato mostra le serie aggiunte per muscolo e l'azione eseguita.

Il flag `periodization_engine` deve essere attivo (Impostazioni → Funzioni).
Con il flag spento il pulsante non e' visibile e il metodo ritorna 403.

## Deload

Il sistema valuta quattro trigger di deload (volume, feedback, settimane
consecutive, segnale manuale). Se uno o piu' trigger sono attivi, la pagina
dettaglio mostra una segnalazione con i trigger attivati.

Per forzare un deload sulla settimana successiva, clicca **Forza deload**.
La settimana successiva viene marcata `is_deload = true` e la progressione
viene applicata con carichi ridotti.

## Errori comuni

**Il pulsante "Applica progressione" non e' visibile**: il flag
`periodization_engine` e' disattivato. Attivalo in Impostazioni → Funzioni
(solo gestore).

**La tabella volume e' vuota**: la settimana selezionata non ha sessioni
completate. Seleziona una settimana con almeno una sessione con dati registrati.

**"Nessuna settimana successiva da marcare come deload"**: il mesociclo e'
all'ultima settimana. Non e' possibile forzare un deload oltre la struttura
pianificata.
