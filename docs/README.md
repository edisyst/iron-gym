# Documentazione Iron Gym

Indice di tutta la documentazione di progetto.

## Architettura

- [component-map.md](architecture/component-map.md) — mappa completa componenti Livewire, route, observers, seeder, artisan commands *(rev. 2026-08-30)*
- [body-map-svg.md](architecture/body-map-svg.md) — struttura SVG body map muscolare (WeeklyVolume)
- [ui-atleta.md](architecture/ui-atleta.md) — design system PWA atleta: token CSS, componenti x-athlete.*, gestione tema dark/light

## Dominio

- [step-0-discovery.md](domain/step-0-discovery.md) — ERD, schema SQL, regole progressione
- [exercises-catalog.md](domain/exercises-catalog.md) — catalogo 83 esercizi (tassonomia, muscoli; dati in `database/database.sqlite`) *(rev. 2026-08-23)*
- [glossary.md](domain/glossary.md) — terminologia bodybuilding e tassonomia *(rev. 2026-08-23)*

## Review e audit

- [audit-codice.md](reviews/audit-codice.md) — security/performance audit codice (2026-06-28); 15 fix applicati
- [audit-grafica.md](reviews/audit-grafica.md) — audit grafico backoffice (2026-06-28); brand identity + coerenza UI
- [audit-receptionist-2026-08-19.md](reviews/audit-receptionist-2026-08-19.md) — audit ruolo receptionist (2026-08-19); 17 finding, 11 fix applicati
- [test-per-ruolo.md](reviews/test-per-ruolo.md) — matrice test Pest per ruolo (archiviata al 2026-08-23, suite ora a 506 test)
- [hk01-report.md](reviews/hk01-report.md) — HK01 housekeeping audit (2026-08-22), report originale
- [hk01-report-v2.md](reviews/hk01-report-v2.md) — HK01 audit v2: verifica manuale sezioni 1 e 3
- [ui-atleta-audit-2026-07-05.md](reviews/ui-atleta-audit-2026-07-05.md) — audit UX/UI PWA atleta pre-UX02; 18 findings P0–P3, ordine esecuzione UX02/03/04
- [ui-atleta-ergonomia-2026-07-05.md](reviews/ui-atleta-ergonomia-2026-07-05.md) — audit ergonomia post UX02–UX05 (passata trasversale)
- [ui-atleta-ergonomia-2026-07-06.md](reviews/ui-atleta-ergonomia-2026-07-06.md) — audit ergonomia in sala (UX05-B): touch target, contrasto WCAG AA, safe-area, input mobile, SR
- [ui-atleta-funzionale-2026-08-18.md](reviews/ui-atleta-funzionale-2026-08-18.md) — audit funzionale PWA atleta (2026-08-18); 11 finding, 8 fix applicati
- [r09-step0-assessment.md](reviews/r09-step0-assessment.md) — assessment pre-R09: schema GroupClass→ClassSchedule→ClassOccurrence
- [r09-plus-test-assessment.md](reviews/r09-plus-test-assessment.md) — assessment funzionale R09+ post-DOC02: copertura test e finding F-01..F-06
- [set01-step0-assessment.md](reviews/set01-step0-assessment.md) — assessment pre-SET01: gap sicurezza, difetti flag, GAP-03
- [set01-chiusura.md](reviews/set01-chiusura.md) — chiusura SET01: 5 scostamenti manuale-menu risolti (S-01..S-05)
- [perf-audit-2026-08-30.md](reviews/perf-audit-2026-08-30.md) — audit prestazioni PERF01 (2026-08-30): 5 finding, N+1 e cache KpiService
- [doc-audit/](reviews/doc-audit/) — DOC01 audit documentazione (2026-08-23): inventario, snapshot, findings, matrice permessi; DOC02 findings (2026-08-30)

## Test

- [README.md](test/README.md) — panoramica suite test e credenziali demo
- [01-gestore.md](test/01-gestore.md) — scenari test ruolo gestore *(rev. 2026-08-30)*
- [02-trainer.md](test/02-trainer.md) — scenari test ruolo trainer *(rev. 2026-08-30)*
- [03-receptionist.md](test/03-receptionist.md) — scenari test ruolo receptionist *(rev. 2026-08-30)*
- [04-atleta.md](test/04-atleta.md) — scenari test ruolo atleta *(rev. 2026-08-30)*
- [test-funzionali.md](test-funzionali.md) — guida scenari demo con ScenarioDemoSeeder (FIX02/DOC02)
- [testing/r09-plus-functional-test-plan.md](testing/r09-plus-functional-test-plan.md) — piano test manuale 109 casi R09+ (DOC02)

## Manuale operativo

Manuale utente del backoffice in 16 sezioni. Raggiungibile da backoffice → Impostazioni → Manuale.
File sorgente: `resources/docs/manual/`. Per aggiungere sezioni: [manual-howto.md](manual-howto.md).

## DevOps

- [go-live-checklist.md](devops/go-live-checklist.md) — checklist pre-go-live *(rev. 2026-08-30)*

## Installazione

- [installation.md](installation.md) — setup ambiente di sviluppo *(rev. 2026-08-23)*
