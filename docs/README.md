# Documentazione Iron Gym

Indice di tutta la documentazione di progetto.

## Architettura

- [component-map.md](architecture/component-map.md) — mappa completa componenti Livewire, route, observers, seeder, artisan commands
- [body-map-svg.md](architecture/body-map-svg.md) — struttura SVG body map muscolare (WeeklyVolume)
- [ui-atleta.md](architecture/ui-atleta.md) — design system PWA atleta: token CSS, componenti x-athlete.*, gestione tema dark/light

## Dominio

- [step-0-discovery.md](domain/step-0-discovery.md) — ERD, schema SQL, regole progressione
- [exercises-catalog.md](domain/exercises-catalog.md) — catalogo 83 esercizi (tassonomia, muscoli; dati in `database/database.sqlite`)
- [glossary.md](domain/glossary.md) — terminologia bodybuilding e tassonomia

## Review e audit

- [audit-codice.md](review/audit-codice.md) — security/performance audit codice (2026-06-28); 15 fix applicati
- [audit-grafica.md](review/audit-grafica.md) — audit grafico backoffice (2026-06-28); brand identity + coerenza UI
- [audit-receptionist-2026-08-19.md](review/audit-receptionist-2026-08-19.md) — audit ruolo receptionist (2026-08-19); 17 finding, 11 fix applicati
- [test-per-ruolo.md](review/test-per-ruolo.md) — matrice test Pest automatici per ruolo (226 test)
- [hk01-report.md](audit/hk01-report.md) — HK01 housekeeping audit (2026-08-22), report originale
- [hk01-report-v2.md](audit/hk01-report-v2.md) — HK01 audit v2: verifica manuale sezioni 1 e 3
- [ui-atleta-audit-2026-07-05.md](reviews/ui-atleta-audit-2026-07-05.md) — audit UX/UI PWA atleta pre-UX02; 18 findings P0–P3, ordine esecuzione UX02/03/04
- [ui-atleta-ergonomia-2026-07-05.md](reviews/ui-atleta-ergonomia-2026-07-05.md) — audit ergonomia post UX02–UX05 (passata trasversale)
- [ui-atleta-ergonomia-2026-07-06.md](reviews/ui-atleta-ergonomia-2026-07-06.md) — audit ergonomia in sala (UX05-B): touch target, contrasto WCAG AA, safe-area, input mobile, SR
- [ui-atleta-funzionale-2026-08-18.md](reviews/ui-atleta-funzionale-2026-08-18.md) — audit funzionale PWA atleta (2026-08-18); 11 finding, 8 fix applicati

## Test

- [README.md](test/README.md) — panoramica suite test
- [01-gestore.md](test/01-gestore.md) — scenari test ruolo gestore
- [02-trainer.md](test/02-trainer.md) — scenari test ruolo trainer
- [03-receptionist.md](test/03-receptionist.md) — scenari test ruolo receptionist
- [04-atleta.md](test/04-atleta.md) — scenari test ruolo atleta

## DevOps

- [go-live-checklist.md](devops/go-live-checklist.md) — checklist pre-go-live

## Installazione

- [installation.md](installation.md) — setup ambiente di sviluppo
