# Abbonamenti

## A cosa serve

La sezione abbonamenti (`/backoffice/subscriptions`) gestisce i contratti tra
la palestra e i tesserati. Ogni abbonamento e' legato a un piano (con durata,
prezzo e numero di ingressi) e a un tesserato. Lo stato dell'abbonamento
determina se il tesserato puo' accedere alla struttura e, nell'app atleta,
se puo' prenotare sessioni PT o corsi collettivi.

## Chi la vede

La lista abbonamenti e' accessibile a gestore e receptionist. Il trainer non
ha accesso. Le operazioni di sospensione, riattivazione ed esportazione CSV
sono riservate al solo gestore.

## Flusso operativo: creare un nuovo abbonamento

1. Vai a Abbonamenti e clicca **Nuovo abbonamento**.
2. Seleziona il tesserato dal menu a tendina.
3. Seleziona il piano abbonamento (i piani sono configurati separatamente dalla
   palestra).
4. Inserisci la data di inizio. La data di scadenza viene calcolata
   automaticamente dalla durata del piano.
5. Salva. L'abbonamento appare con stato **Attivo**.

## Rinnovo rapido

Nella lista abbonamenti, ogni riga mostra un pulsante **Rinnova**. Cliccarlo
apre il modulo di creazione abbonamento con tesserato e piano gia' precompilati
dal precedente abbonamento. La data di inizio e' impostata al giorno corrente e
la scadenza viene calcolata automaticamente. Verifica i dati e salva per creare
il rinnovo.

## Filtri

La lista supporta i seguenti filtri:
- **Tutti**: mostra tutti gli abbonamenti.
- **Attivi**: solo abbonamenti con stato `active`.
- **Scaduti**: solo abbonamenti con stato `expired`.
- **In scadenza**: abbonamenti attivi che scadono entro 30 giorni.
- **Sospesi**: solo abbonamenti con stato `suspended`.

## Sospensione e riattivazione (solo gestore)

Il gestore puo' sospendere un abbonamento attivo cliccando il pulsante pausa
nella riga. L'abbonamento passa a stato `suspended` e il tesserato non puo'
accedere alla struttura. Per riattivarlo, clicca il pulsante play. Le
operazioni richiedono conferma esplicita.

La sospensione non modifica la data di scadenza: il periodo sospeso viene
perso a meno che il gestore non aggiorni manualmente la scadenza al momento
della riattivazione.

## Esportazione CSV (solo gestore)

Il pulsante **Esporta CSV** scarica l'elenco degli abbonamenti rispettando il
filtro attivo al momento dell'export. Il file e' in formato UTF-8 con separatore
punto e virgola, compatibile con Excel.

## Errori comuni

**Il tesserato non riesce a prenotare**: verifica che l'abbonamento abbia stato
`active` e che la data di scadenza non sia passata. Un abbonamento `expired`
blocca sia il check-in sia le prenotazioni dall'app atleta.

**Il contatore ingressi e' a zero ma l'abbonamento e' attivo**: alcune tipologie
di piano hanno un numero massimo di ingressi (`accesses_remaining`). Quando
questo campo raggiunge zero il check-in viene bloccato anche se l'abbonamento
non e' scaduto. In questo caso occorre creare un nuovo abbonamento o aumentare
manualmente il contatore.

**La scadenza calcolata non corrisponde alle aspettative**: la data di scadenza
dipende dalla durata configurata nel piano. Se il piano ha durata "1 mese" e la
palestra usa mesi calendariali, verifica la configurazione del piano con il
gestore.
