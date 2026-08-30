# Come aggiungere una sezione al manuale

Il manuale operativo e' composto da file Markdown in `resources/docs/manual/`.
Ogni file e' una sezione. L'ordine di visualizzazione dipende dall'ordinamento
alfabetico del nome file.

## Aggiungere una sezione

1. Crea un file `NN-slug.md` nella directory `resources/docs/manual/`, dove
   `NN` e' un numero a due cifre che determina l'ordine (es. `07-nuova-sezione.md`).

2. La prima riga del file deve essere un titolo H1 (`# Titolo sezione`).
   ManualRenderer lo usa per estrarre il titolo da mostrare nella sidebar.

3. Scrivi il contenuto in Markdown standard. Il rendering usa `Str::markdown()`
   (league/commonmark). L'output viene mostrato via `{!! $renderedHtml !!}`:
   il Markdown viene interpretato, l'HTML grezzo viene escapato dalla libreria.

4. La sezione compare automaticamente nella sidebar del manuale al prossimo
   caricamento della pagina. Non serve nessuna altra registrazione.

5. La cache per ogni sezione usa chiave `manual.{slug}.{mtime}` con TTL 1 ora.
   Aggiornando il file la chiave cambia automaticamente grazie all'mtime.

## Associare un feature flag a una sezione

Se la sezione descrive una funzionalita' gated da un feature flag, puoi mostrare
un badge ON/OFF nella sidebar. Aggiungi l'associazione in `SECTION_FLAGS` nel
componente `app/Livewire/Backoffice/Settings/ManualViewer.php`:

```php
private const SECTION_FLAGS = [
    '07-nuova-sezione' => 'nome_flag',
];
```

Il badge viene mostrato solo se lo slug e' presente nella mappa. Il nome del
flag deve corrispondere a un flag definito in `config/features.php`.

## Rinominare o rimuovere una sezione

- **Rinomina**: cambia il nome del file. Lo slug cambia di conseguenza; se hai
  un flag associato in `SECTION_FLAGS` aggiorna anche la chiave nella mappa.
- **Rimozione**: elimina il file. La sezione sparisce dalla sidebar al prossimo
  caricamento. Non servono altre operazioni.

## Sicurezza

Lo slug viene usato solo come chiave nell'array restituito da `sections()`,
mai concatenato a un path sul filesystem. La protezione da path-traversal e'
garantita dalla struttura del servizio `ManualRenderer`.
