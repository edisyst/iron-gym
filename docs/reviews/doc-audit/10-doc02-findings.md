# DOC02 — Findings post-DOC01

**Data:** 2026-08-30
**Fase:** 1 di 4 — READ-ONLY
**Delta coperto:** commit dal 2026-08-23 al 2026-08-30 (SET01 + PERF01 + release R09–R31 + refactor seeder)

Legenda severità: **[CRITICO]** informazione sbagliata che porta a codice o azione errata;
**[MEDIO]** divergenza rilevante che causa confusione operativa o tecnica;
**[BASSO]** gap o incompletezza non bloccante.

---

## Finding MEDIO

### M-01 — `docs/review/test-per-ruolo.md`: suite dichiarata vs reale

Documento dichiara **226 test** (220 pass + 6 skip), ultima verifica 2026-08-23.
Suite reale alla chiusura SET01: **506 test** (500 pass + 6 skip).
Delta: +280 test aggiunti in R09 Step 1–6, R10–R31, SET01 (FeatureFlagGatingTest,
OutboundNotificationsKillSwitchTest, SessionFlagGatingTest, ModuleFlagGatingTest,
ManualViewerTest, e decine di file di feature test per i tab di AthleteProfile, R22–R31).

Il documento ha ancora valore strutturale (mappa per ruolo) ma il conteggio e la tabella
sono massiccamente incompleti. Valutare se aggiornare o archiviare — vedi Decisioni #1.

Evidence: `docs/review/test-per-ruolo.md:3`, CLAUDE.md "Suite corrente: 506 test".

---

### M-02 — `docs/devops/go-live-checklist.md`: sezione Roll-out graduale incompleta

La sezione elenca 4 flag:
```
financial_reports, periodization_engine, push_notifications, group_classes
```
`config/features.php` definisce **14 managed flags** in 3 gruppi:

**Moduli (3):** `group_classes`, `messaging`, `pt_bookings`
**Sessione atleta (6):** `readiness_check`, `exercise_substitution`, `session_recap`,
`personal_records`, `weekly_volume`, `plate_calculator`
**Sistema (5):** `financial_reports`, `periodization_engine`, `push_notifications`,
`outbound_notifications`, `in_app_feedback`

Mancano 10 flag dalla checklist. Chi esegue il go-live basandosi sul documento
non configura il kill switch `outbound_notifications` (blocca tutte le email e push
se lasciato OFF) né i flag di sessione atleta.

Evidence: `docs/devops/go-live-checklist.md:33-37`, `config/features.php:13-141`.

---

### M-03 — `docs/architecture/component-map.md`: sezione Feature flags obsoleta

La sezione "Feature flags" in fondo al documento (dopo la tabella Layout, righe ~263-276
nella versione pre-delta) elenca ancora solo 4 flag e riporta come route di gestione UI
`/backoffice/admin/feature-flags` senza indicare che questa route fa 301 redirect
a `/backoffice/settings/feature-flags` (la nota del 301 esiste alla riga 58 del documento,
ma la sezione Flag non vi fa riferimento).

Secondariamente, la descrizione di `Settings\FeatureFlagManager` nella tabella componenti
dice "Toggle **13** flag" ma i managed_flags in `config/features.php` sono **14**.

Evidence: `component-map.md:127`, `config/features.php` (conteggio: 3+6+5=14).

---

### M-04 — `docs/architecture/component-map.md`: tabella Seeder obsoleta

Il refactor del 2026-08-28 (commit `f282382`) ha rinominato ed eliminato diversi seeder.
La tabella nel documento riflette lo stato pre-refactor.

Voci obsolete (nome sbagliato):

| Nome nel documento | Nome reale | Tipo divergenza |
|---|---|---|
| `DemoSeeder` | `CoreDemoSeeder` | rinominato |
| `PilotTemplateSeeder` | `TemplateSeeder` | rinominato |
| `FunctionalTestSeeder` | `ScenarioDemoSeeder` | rinominato |
| `GroupClassSeeder` | *(eliminato)* | non esiste piu' |

Seeder mancanti dal documento (esistono in filesystem):

| Seeder | Contenuto |
|---|---|
| `ClassDemoSeeder` | Dati demo corsi collettivi, palinsesti, occorrenze |
| `DumbbellInventorySeeder` | Inventario manubri |
| `TrainingDemoSeeder` | Storico sessioni di allenamento demo |
| `VolumeDemoSeeder` | 50 atleti con 12 settimane di volume |
| `VolumeLandmarkDemoSeeder` | Landmarks MEV/MAV/MRV demo per atleta ID=5 |

Evidence: `database/seeders/` (filesystem), `component-map.md` sezione Seeder,
commit `f282382 2026-08-28`.

---

## Finding BASSO

### B-01 — `docs/README.md`: 8 file esistenti non elencati

Nessuna voce punta ai seguenti file, tutti verificati con esistenza nel filesystem:

File in `docs/reviews/` (5 nuovi da SET01/PERF01/R09):
- `docs/reviews/set01-step0-assessment.md`
- `docs/reviews/set01-chiusura.md`
- `docs/reviews/perf-audit-2026-08-30.md`
- `docs/reviews/r09-plus-test-assessment.md`
- `docs/reviews/r09-step0-assessment.md`

Altri:
- `docs/manual-howto.md` — guida per aggiungere sezioni al manuale (SET01 Step 3)
- `docs/test-funzionali.md` — guida scenari demo + FunctionalTestSeeder
- `docs/testing/r09-plus-functional-test-plan.md` — piano test 109 casi R09+

Nota aggiuntiva: il manuale operativo in `resources/docs/manual/` (16 sezioni,
raggiungibile da backoffice → Impostazioni → Manuale) non ha nessuna voce in `docs/README.md`.

Evidence: `find docs/ -name "*.md"`, `docs/README.md`.

---

### B-02 — `docs/architecture/ui-atleta.md`: navigazione filtrata per flag non documentata

SET01 Step 2C ha introdotto gating sulla navigazione della PWA atleta. Il documento
non ne fa menzione.

Comportamenti non documentati:

1. **Bottom nav "Progressi"** (`x-athlete.bottom-nav` e `layouts/athlete.blade.php`):
   href condizionale — se `weekly_volume` attivo → `route('athlete.volume')`,
   altrimenti → `route('athlete.measurements')`. Il tab rimane sempre visibile.

2. **Toast PR** nel layout atleta: wrappato in `@feature('personal_records')`.

3. **Badge unread e fetch unread-count** nel layout atleta:
   condizionale su `@feature('messaging')` — se il flag e' spento, nessuna chiamata
   HTTP viene emessa.

4. **Link "Apri messaggi"** nell'empty-state dashboard atleta: gated su `messaging`.

5. **Link "Prenota"** nel bottom-nav: gated su `@can('view-athlete-bookings')`
   (gate attivo se `pt_bookings` OR `group_classes`).

Evidence: `resources/views/layouts/athlete.blade.php:74-129`,
`resources/views/components/athlete/bottom-nav.blade.php:34-37`,
commit `46c231d 2026-08-29 SET01 Step 2C`.

---

### B-03 — Manuale operativo: route prenotazioni PT errata in sezioni 09 e 10

Sezione 09 (`09-calendario-disponibilita.md`) e sezione 10 (`10-prenotazioni-pt.md`)
citano la route `/backoffice/calendar/bookings`.

La route reale (da `routes/backoffice.php:81` e `component-map.md:38`) e':
`/backoffice/bookings`

Evidence: `resources/docs/manual/09-calendario-disponibilita.md:49`,
`resources/docs/manual/10-prenotazioni-pt.md:5`, `routes/backoffice.php:81`.

---

### B-04 — Tre directory di review senza criterio documentato

Le directory `docs/review/`, `docs/reviews/` e `docs/audit/` coesistono senza
un criterio esplicito di assegnazione.

Inventario attuale:

**`docs/review/`** (4 voci):
- `audit-codice.md` — snapshot storico 2026-06-28
- `audit-grafica.md` — snapshot storico 2026-06-28
- `audit-receptionist-2026-08-19.md` — snapshot storico 2026-08-19
- `test-per-ruolo.md` — documento vivo (o semi-vivo, vedi M-01)
- `doc-audit/` — directory DOC01 con 4 file collegati tra loro

**`docs/reviews/`** (9 voci):
- `ui-atleta-audit-2026-07-05.md` — snapshot storico 2026-07-05
- `ui-atleta-ergonomia-2026-07-05.md` — snapshot storico 2026-07-05
- `ui-atleta-ergonomia-2026-07-06.md` — snapshot storico 2026-07-06
- `ui-atleta-funzionale-2026-08-18.md` — snapshot storico 2026-08-18
- `r09-step0-assessment.md` — snapshot storico R09
- `r09-plus-test-assessment.md` — snapshot storico DOC02
- `set01-step0-assessment.md` — snapshot storico SET01
- `set01-chiusura.md` — documento di chiusura SET01 (contiene decisioni)
- `perf-audit-2026-08-30.md` — snapshot storico PERF01

**`docs/audit/`** (2 voci):
- `hk01-report.md` — snapshot storico HK01
- `hk01-report-v2.md` — snapshot storico HK01 v2

Proposta di consolidamento (da confermare, vedi Decisioni #2):
Unificare tutto in `docs/reviews/` con sottodirectory `doc-audit/`
(la struttura DOC01 ha link interni, va spostata intatta).
Eliminare `docs/review/` e `docs/audit/` dopo lo spostamento.
`docs/README.md` aggiornato di conseguenza.

---

### B-05 — `docs/api/` e' una directory vuota

La directory esiste ma non contiene file e non e' referenziata da `docs/README.md`.
Probabile residuo di un'area non ancora sviluppata.

Evidence: `ls docs/api/` (output vuoto).

---

## CLAUDE.md: verifiche puntuali

Le sezioni di CLAUDE.md aggiornate dal 2026-08-30 (chiusura SET01) appaiono allineate
al codice per quanto riguarda:
- Tabella feature flag (14 flag, gruppi corretti dopo la correzione del commit `7014339`)
- Servizi e observer citati (tutti esistenti in `app/`)
- Voce PERF01 presente ("Storico completo release e audit: CHANGELOG.md")
- Stato sviluppo aggiornato

Nessun finding su CLAUDE.md.

---

## Manuale operativo: verifiche perimetro ridotto

Verifica eseguita su: link interni, route/URL, etichette menu, SECTION_FLAGS.

**SECTION_FLAGS in ManualViewer:**
- `11-corsi-collettivi` → `group_classes` ✓ (flag esiste in config/features.php)
- `14-report-finanziari` → `financial_reports` ✓ (flag esiste in config/features.php)

**Route errate (vedi B-03):** sezioni 09 e 10 citano `/backoffice/calendar/bookings`.

**Nessun altro refuso, link interno rotto o etichetta divergente trovata** nel
perimetro ridotto (solo queste quattro classi di problema).

SET01 Fase 3 aveva gia' chiuso S-01/S-02/S-03/S-04/S-05. Non riapertura.

---

## Finding sul codice emersi durante DOC02

Nessun finding sul codice identificato.

---

## Piano di intervento

### Fase 2 — Architettura, devops, indice

| Priorita' | File | Operazione | Finding |
|---|---|---|---|
| 1 | `docs/architecture/component-map.md` | Aggiorna sezione Seeder (rinomina + aggiungi + rimuovi); aggiorna sezione Feature flags (14 flag, route corretta) | M-03, M-04 |
| 2 | `docs/devops/go-live-checklist.md` | Riscrivi sezione Roll-out graduale con tutti e 14 i flag | M-02 |
| 3 | `docs/architecture/ui-atleta.md` | Aggiungi sezione "Navigazione filtrata per flag" | B-02 |
| 4 | `docs/README.md` | Aggiungi voci mancanti (5 reviews, manual-howto, test-funzionali, testing/, manuale operativo) | B-01 |
| 5 | `docs/review/test-per-ruolo.md` | Secondo decisione #1: aggiorna o archivia | M-01 |

### Fase 3 — Piani di test per ruolo

- `docs/test/01-gestore.md`: aggiungi scenari sezione Impostazioni (accesso, toggle flag, manuale, slug inesistente)
- Gli altri tre piani (`02`, `03`, `04`): verifica che non citino route morte o feature non piu' gated

### Fase 4 — Manuale, consolidamento, chiusura

| Priorita' | Operazione | Finding |
|---|---|---|
| 1 | Correggi `/backoffice/calendar/bookings` → `/backoffice/bookings` nelle sezioni 09 e 10 | B-03 |
| 2 | Consolida `docs/review/`, `docs/reviews/`, `docs/audit/` secondo decisione #2 | B-04 |
| 3 | Rimuovi `docs/api/` se confermata inutile | B-05 |
| 4 | CHANGELOG.md: aggiungi voce DOC02 | — |

---

## Decisioni che richiedono conferma

**#1 — `docs/review/test-per-ruolo.md`: aggiornare o archiviare**

Il documento mappa i test Pest per ruolo. Ha valore strutturale ma e' scalato
male: 226 → 506 test, delta di 280 entry da aggiungere.

- (a) **Aggiornare**: aggiungere le righe mancanti per i ~280 test nuovi. Mantiene
  la mappatura per ruolo come riferimento rapido. Labor-intensive.
- (b) **Archiviare**: sostituire il contenuto con una nota di archiviazione
  (suite al 2026-08-23, non piu' mantenuta) e un rimando a `./vendor/bin/pest --list`.
  Piu' onesto rispetto allo stato reale.

**#2 — Consolidamento directory review**

- (a) **Consolidare in `docs/reviews/`**: spostare `docs/audit/hk01-*.md` →
  `docs/reviews/`, spostare `docs/review/audit-*.md` e `docs/review/test-per-ruolo.md`
  → `docs/reviews/`, spostare `docs/review/doc-audit/` → `docs/reviews/doc-audit/`.
  Aggiornare tutti i link entranti. Risultato: una sola directory per tutti i report.
- (b) **Documentare il criterio esistente e lasciare intatto**: aggiungere un
  `docs/reviews/README.md` che spiega la distinzione (per chi ha creato cosa e quando).
  Zero rischio di link broken, zero rework.

**#3 — `docs/api/` vuota**

- (a) Rimuovere la directory (residuo non utilizzato).
- (b) Lasciarla come segnaposto (area API da sviluppare).

---

*Generato da DOC02 fase 1 — 2026-08-30*
