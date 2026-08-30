# Report allenamento

## A cosa serve

Il report allenamento (`/backoffice/reports/training`) offre una panoramica
dell'attivita' di allenamento di tutti gli atleti (o dei propri, per il
trainer) in un intervallo di date. Permette di vedere quante sessioni ha
completato ciascun atleta, quante ne ha saltate e di aprire un drilldown
con il dettaglio esercizi.

## Chi la vede

Gestore e trainer. Il trainer vede solo gli atleti dei propri mesocicli; il
gestore vede tutti.

## Come usare il report

1. Vai a Training → **Report allenamento**.
2. Imposta l'intervallo di date (default: mese corrente).
3. Filtra per stato mesociclo se necessario (tutti, attivi, completati).
4. La tabella mostra per ogni atleta: sessioni completate, sessioni saltate,
   percentuale completamento e altre metriche aggregate.
5. Clicca su una riga atleta per aprire il **drilldown**: dettaglio sessioni
   nel periodo con data, nome sessione e stato.
6. Clicca di nuovo (o sul pulsante chiudi) per tornare alla lista.

## Filtri disponibili

- **Data da / a**: intervallo temporale analizzato.
- **Stato mesociclo**: filtra gli atleti per stato del loro mesociclo
  (`all`, `active`, `completed` ecc.).

## Errori comuni

**Un atleta non appare nel report**: verifica che abbia un mesociclo assegnato
nel periodo selezionato. Gli atleti senza mesociclo nel range date non
figurano nella tabella.

**Il drilldown ritorna 403**: il trainer sta cercando di aprire il drilldown
di un atleta non assegnato ai propri mesocicli. Questo non dovrebbe
accadere normalmente perche' la riga non dovrebbe apparire nella lista, ma
puo' capitare se i dati cambiano mentre la pagina e' aperta.
