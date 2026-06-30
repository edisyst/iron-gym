# iron-gym — Guida per agenti AI

> Riferimento principale: **CLAUDE.md** (convenzioni, dominio, architettura, vincoli).
> Questo file aggiunge contesto sullo stato corrente e le indicazioni operative.

## Stato del progetto

Tutti gli step 1-10 sono stati implementati e verificati. Il sistema è in fase di
pilota pre-produzione con dati reali. Suite test: 90/96 verdi (6 skipped su
staging), PHPStan L6 a 0 errori, Pint conforme.

Stack corrente: PHP 8.3 + Laravel 11, Livewire 3 + Alpine.js + AdminLTE 3 per il
backoffice, layout `/athlete` dedicato per l'app atleta (PWA attiva con service
worker e Web Push VAPID). MySQL 8 + Redis 7 via Docker Compose. CI GitHub Actions.

## Documenti di dominio

Richiedili esplicitamente; non sono caricati automaticamente per non saturare il contesto:

- `.claude/docs/domain/step-0-discovery.md` — ERD, schema SQL, regole di progressione
- `.claude/docs/domain/exercises-catalog.md` — catalogo 83 esercizi (tassonomia, muscoli, contribution_pct, descrizioni esecuzione)
- `.claude/docs/domain/glossary.md` — terminologia BB, tecniche speciali, tassonomia (corto, OK caricarlo)

Per la mappa completa di route, componenti Livewire, observers e artisan commands:
`docs/architecture/component-map.md`

## Indicazioni operative

- **Search-first:** prima di rispondere su librerie, versioni, comandi o best practice,
  cercare sempre online. Preferire risposte aggiornate.
- **Ambiguità:** se la richiesta è incompleta o ambigua, chiedere chiarimenti prima
  di procedere. Nessuna assunzione silenziosa.
- **Codice:** rispettare la formattazione esistente. Per modifiche localizzate mostrare
  solo le parti cambiate con contesto minimo; per file brevi mostrare il file completo.
- **Spiegazioni:** brevi, dirette, in prosa. Niente liste puntate salvo richiesta.
  Non approfondire oltre quanto chiesto.
- **Proattività:** non suggerire approcci alternativi o best practice se non richiesti
  esplicitamente.
- **Rischi:** segnalare side effect solo se critici (sicurezza, perdita irreversibile
  di dati). Omettere avvertenze minori.
- **Niente emoji nel codice o nei commenti.**

## Cosa NON fare

- Non proporre Vue.js, Inertia, SPA.
- Non proporre Filament, Nova, Backpack.
- Non introdurre multi-tenancy.
- Non aggiungere colonne o tabelle senza discuterne prima con l'utente.

## Comandi di sviluppo

```bash
docker compose up -d
php artisan serve
npm run dev
php artisan queue:work redis --queue=default
php artisan schedule:work

php artisan migrate:fresh --seed
./vendor/bin/pest
./vendor/bin/phpstan analyse --memory-limit=512M
./vendor/bin/pint --test
```
