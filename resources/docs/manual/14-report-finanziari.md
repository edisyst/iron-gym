# Report finanziari

## A cosa serve

Il report finanziario (`/backoffice/reports/manager`) fornisce al gestore
una visione economica dell'attivita' della palestra: ricavi per periodo,
ricavi per piano abbonamento, tasso di occupazione PT dei trainer e sessioni
PT completate per trainer. I dati vengono calcolati tramite `KpiService` e
sono in cache Redis con TTL 1 ora.

Questo modulo e' attivo solo se il flag `financial_reports` e' ON in
Impostazioni → Funzioni.

## Chi la vede

Solo il gestore. Il componente ritorna 403 se il flag e' disattivato o
l'utente non ha il ruolo `gestore`.

## Contenuto del report

- **Ricavi per periodo** (ultimi 12 mesi): grafico a barre mensile dei ricavi
  da abbonamenti.
- **Ricavi per piano**: tabella con numero di abbonamenti venduti e ricavo
  totale per tipologia di piano nel periodo selezionato.
- **Occupazione PT per trainer**: tabella con slot disponibili, slot prenotati
  e percentuale di occupazione per ogni trainer nel periodo.
- **Sessioni PT completate per trainer**: conteggio sessioni PT con stato
  `completed` per trainer nel periodo, utile per la rendicontazione.

## Come usare il report

1. Apri **Report → Report finanziario** (visibile solo con flag attivo).
2. Imposta l'intervallo di date (default: mese corrente).
3. I dati si aggiornano al cambio delle date.

## Download PDF

Dalla pagina e' disponibile un pulsante per esportare il report in PDF.
Il file generato viene salvato temporaneamente sul server e scaricato tramite
la route `/backoffice/reports/download/{file}`.

## Cache

I KPI sono in cache Redis tag `kpi` con TTL 1 ora. Se i dati sembrano non
aggiornati dopo operazioni recenti (nuovi abbonamenti, nuove prenotazioni),
attendi la scadenza della cache o esegui `php artisan cache:clear` in
sviluppo.

## Errori comuni

**La pagina mostra 403**: il flag `financial_reports` e' OFF. Attivalo in
Impostazioni → Funzioni.

**I ricavi non corrispondono ai dati attesi**: i ricavi sono calcolati sul
prezzo dei piani abbonamento al momento della creazione dell'abbonamento.
Modifiche successive al prezzo del piano non si riflettono sugli abbonamenti
storici.
