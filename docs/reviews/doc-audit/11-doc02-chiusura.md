# DOC02 — Chiusura audit documentazione

**Data:** 2026-08-30  
**Baseline:** DOC01 (2026-08-23, 226 test) — delta coperto: SET01 + PERF01  
**Suite al momento della chiusura:** 506 test (500 pass / 6 skipped)

---

## Findings applicati

### MEDIO

| ID | Finding | Risolto in |
|---|---|---|
| M-01 | `component-map.md` obsoleto: 13 flag (erano 14), seeder rinominati | Fase 2 — commit `b203061` |
| M-02 | `go-live-checklist.md`: roll-out descriveva 4 flag, non 14 | Fase 2 — commit `cdd668c` |
| M-03 | `ui-atleta.md`: nav filtrata per flag (SET01 Step 2C) non documentata | Fase 2 — commit `70a88c9` |
| M-04 | Seeder rinominati nel 2026-08-28 non tracciati in `component-map.md` | Fase 2 (compreso in M-01) |

### BASSO

| ID | Finding | Risolto in |
|---|---|---|
| B-01 | `docs/README.md`: 5 voci mancanti (reviews/ e chiusure) | Fase 2 — commit `ec24e4d` |
| B-02 | `test-per-ruolo.md`: snapshot al 2026-08-23, non aggiornato | Fase 2 — commit `4c4af5f` (archiviato) |
| B-03 | Manuale sez. 09 e 10: URL `/backoffice/calendar/bookings` inesistente | Fase 4 — commit `db557d4` |
| B-04 | `docs/test/01-04`: scenari non coprono SET01, plate calculator rimosso | Fase 3-B — commit `c231eab..999e307` |
| B-05 | `docs/review/`, `docs/audit/` separate da `docs/reviews/` | Fase 4 — commit `6465215` |

---

## Decisioni prese (approvate)

1. **`test-per-ruolo.md`**: archiviato con notice — non aggiornato (506 test, `pest --list` autorevole)
2. **Consolidamento**: `docs/review/` + `docs/audit/` → `docs/reviews/`; link aggiornati in `README.md`
3. **`docs/api/`**: non esisteva in filesystem (vuota) — nessuna azione necessaria

---

## Permessi verificati (Fase 3-A)

Matrice completa in `.tmp/doc02/permission-matrix.txt`. Punti chiave confermati dal codice:

- `communications/campaign`: `role:gestore` — trainer e receptionist ricevono 403
- `group_classes` route: gate `view-group-classes` senza parametro utente — 403 per tutti con flag OFF
- `/backoffice/settings`: gate `access-admin-section` (`role:gestore`) — 403 per trainer e receptionist
- `/backoffice/admin/feature-flags`: 301 redirect a `/backoffice/settings/feature-flags`, poi stesso gate
- `view-athlete-bookings`: `pt_bookings OR group_classes` — route 403 solo se entrambi i flag OFF

---

## File modificati in DOC02

### Fase 2 (architettura/devops/indice)

| File | Modifiche |
|---|---|
| `docs/architecture/component-map.md` | 14 flag, seeder rinominati, feature flags section riscritta |
| `docs/devops/go-live-checklist.md` | Roll-out 14 flag in 3 tabelle |
| `docs/architecture/ui-atleta.md` | Sezione navigazione filtrata per flag |
| `docs/README.md` | 5 voci aggiunte, link corretti, date aggiornate |
| `docs/reviews/test-per-ruolo.md` | Notice archiviazione |

### Fase 3-B (piani test per ruolo)

| File | Modifiche principali |
|---|---|
| `docs/test/01-gestore.md` | Sez. 18 riscritta (Impostazioni), aggiunte sez. 20-22 |
| `docs/test/02-trainer.md` | Sez. 11 rimossa (communications/campaign → 403), sez. 12 aggiornata |
| `docs/test/03-receptionist.md` | Nota group_classes corretta (403 con flag OFF), sez. 9 aggiornata |
| `docs/test/04-atleta.md` | Sez. 11 rimossa (plate calculator), note flag, sez. 16 (notifiche) |

### Fase 4 (consolidamento)

| File | Modifiche |
|---|---|
| `resources/docs/manual/09-calendario-disponibilita.md` | Fix B-03: URL prenotazioni corretto |
| `resources/docs/manual/10-prenotazioni-pt.md` | Fix B-03: URL prenotazioni corretto |
| `docs/reviews/` | +7 file da `docs/review/` e `docs/audit/` (git mv) |
| `docs/README.md` | Link aggiornati a `reviews/` |
| `CHANGELOG.md` | Voce DOC02 aggiunta |

---

## Stato post-DOC02

- PHPStan: livello 6, 0 errori
- Pint: conforme
- Pest: 506 test (500 pass / 6 skipped)
- Nessun file PHP/Blade/CSS/JS/migration/test/config modificato
