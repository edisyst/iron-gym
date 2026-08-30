# Schede template e mesocicli

## A cosa serve

I template di scheda (`/backoffice/templates`) sono le strutture di allenamento
riutilizzabili a livello di palestra. Un template definisce il numero di
settimane, le sessioni per settimana, gli esercizi e i parametri pianificati
(serie, ripetizioni, RIR, recupero). Da un template si generano i mesocicli
(`/backoffice/mesocycles`), ossia le istanze concrete assegnate a singoli
atleti. Dopo l'istanziazione il mesociclo e' indipendente dal template: le
modifiche al template non si propagano ai mesocicli gia' assegnati.

## Chi la vede

Template e mesocicli sono accessibili a gestore e trainer. Il receptionist non
ha accesso. Il trainer vede nella lista mesocicli solo quelli di cui e'
responsabile; il gestore vede tutti.

## Flusso operativo: creare un template

1. Vai a Template di scheda e clicca **Nuovo template**.
2. Assegna nome, obiettivo (ipertrofia, forza, definizione ecc.),
   numero di settimane e sessioni per settimana.
3. Entra nel **Builder**: seleziona la settimana e la sessione, poi aggiungi
   gli esercizi nell'ordine desiderato. Per ogni esercizio imposta serie
   pianificate, ripetizioni, RIR, recupero e tecnica (normal, superset ecc.).
4. Ripeti per tutte le settimane e sessioni.
5. Segna il template come **Attivo** quando e' pronto per essere assegnato.

## Duplicare un template

Dalla lista template, il pulsante duplica crea una copia con nome "Copia di
{nome originale}" e `is_active = false`. Apre automaticamente il builder
della copia. Utile per creare varianti senza partire da zero.

## Flusso operativo: assegnare un mesociclo

1. Vai a Mesocicli e clicca **Assegna mesociclo**.
2. Seleziona il template, l'atleta e la data di inizio.
3. Il sistema genera tutte le settimane (`MicrocycleWeek`) e le sessioni
   (`TrainingSession`) pianificate. L'atleta vede immediatamente il programma
   nell'app.
4. Il mesociclo nasce con stato `draft`. Passa ad `active` quando l'atleta
   inizia la prima sessione.

## Stati del mesociclo

| Stato | Significato |
|---|---|
| `draft` | Assegnato ma non ancora iniziato |
| `active` | In corso |
| `completed` | Tutte le sessioni completate |
| `aborted` | Interrotto manualmente |

## Filtri lista mesocicli

La lista supporta filtro per nome, stato, trainer e atleta. Il trainer vede
solo i propri mesocicli anche senza applicare filtri.

## Errori comuni

**Il template non appare nella selezione durante l'assegnazione**: verifica che
il template abbia `is_active = true`. I template inattivi non sono proposti
nell'elenco di assegnazione.

**L'atleta non vede il programma nell'app**: il mesociclo deve avere stato
`draft` o `active`. Verifica anche che l'atleta abbia un account collegato
al tesserato (sezione Tesserati).

**Modifiche al template non si riflettono sul mesociclo**: comportamento
atteso. Il mesociclo e' un'istanza snapshotted. Per aggiornare la scheda di
un atleta occorre assegnare un nuovo mesociclo.
