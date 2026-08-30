# SET01 — Documento di chiusura release

Data: 2026-08-30
Stato: chiuso

---

## Correzioni applicate in Fase 3

### C-01 — CLAUDE.md tabella flag: gruppo errato per financial_reports e periodization_engine

Fonte di verita': `config/features.php` (`'group'` di ogni flag).

| Flag | Valore CLAUDE.md precedente | Valore corretto |
|---|---|---|
| `financial_reports` | Moduli | Sistema |
| `periodization_engine` | Moduli | Sistema |

Correzione applicata direttamente in CLAUDE.md.

### C-02 — CLAUDE.md tabella flag: platea outbound_notifications

| Colonna | Valore CLAUDE.md precedente | Valore corretto (config) |
|---|---|---|
| Platea | Job di sistema | Tutta la palestra (flag globale) |

Correzione applicata direttamente in CLAUDE.md.

---

## Scostamenti manuale vs menu (solo documentazione, nessuna correzione)

### S-01 — Sezione 08 (Progressione e volume landmarks): nessuna voce sidebar diretta

La sezione documenta funzionalita' raggiungibili solo navigando all'interno di
altre pagine:
- Dettaglio mesociclo (pulsante "Applica progressione", tab volume)
- Profilo atleta in backoffice (link "Volume landmarks")

Nessuna voce `config/adminlte.php` punta a una route `/backoffice/volume-landmarks`
o simile. La sezione 08 del manuale e' concettualmente una sotto-sezione della 07
(Schede template e mesocicli).

**Impatto operativo:** il lettore che cerca "Volume landmarks" nel menu non trova
una voce corrispondente. La sezione 08 e' comunque raggiungibile dal manuale.

### S-02 — Sezione 14 (Report finanziari): nessuna voce sidebar

La route `/backoffice/reports/manager` e' gated da `can:view-financial-reports`
(implicito: flag `financial_reports` ON + ruolo gestore). Non esiste pero' una
voce nel menu `config/adminlte.php` che la colleghi.

La pagina e' raggiungibile solo tramite URL diretta o eventuale link interno
dalla dashboard. Non e' presente in TRAINING ne' in IMPOSTAZIONI.

**Impatto operativo:** il gestore con flag attivo non vede la voce nel menu.
Da valutare se aggiungere una voce "Report finanziario" sotto TRAINING o
sotto IMPOSTAZIONI in un rilascio futuro.

### S-03 — "Feedback utenti" (menu COMUNICAZIONE): nessuna sezione manuale

La voce `Feedback utenti` (`/backoffice/admin/feedback`) appare nella sidebar
sotto COMUNICAZIONE (`can: access-admin-section`). Non esiste una sezione
dedicata nel manuale.

**Impatto operativo:** limitato — e' una lista read-only dei feedback in-app
inviati dagli utenti. La sezione 12 (Comunicazione e campagne) non la menziona.

### S-04 — Sezione 12: citazione errata della route messaggistica

La sezione 12 del manuale cita `/backoffice/messages` come URL della messaggistica
one-to-one. La route effettiva e' `backoffice.athletes.messages` a
`/backoffice/athletes/{athleteId}/messages` (per-atleta). Non esiste una route
standalone `/backoffice/messages` ne' una voce sidebar corrispondente.

**Impatto operativo:** il lettore della sezione 12 cerca una voce "Messaggi" nel
menu che non esiste. La messaggistica e' accessibile solo dal profilo atleta
backoffice (tab o link diretto all'interno di `AthleteProfile`).

### S-05 — File Admin/FeatureFlagManager.php non rimosso dopo migrazione a Settings

Dopo SET01 Step 1 (spostamento FeatureFlagManager da Admin a Settings), il file
originale `app/Livewire/Backoffice/Admin/FeatureFlagManager.php` non e' stato
eliminato. Esiste tuttora con namespace `App\Livewire\Backoffice\Admin`.

La route `backoffice.settings.feature-flags` punta correttamente alla classe
`Settings\FeatureFlagManager`. Il file Admin e' dead code ma non causa errori
(non e' referenziato da route o test). Da eliminare in un rilascio di cleanup.

---

## Nessun riferimento a route obsolete trovato in CLAUDE.md

La route `/backoffice/admin/feature-flags` e' presente solo come redirect 301
in `routes/backoffice.php:223`. CLAUDE.md non la cita come route corrente.
Il documento di assessment `set01-step0-assessment.md` la menziona in contesto
storico; nessuna correzione necessaria su quel file.

---

## Stato finale release SET01

| Voce | Stato |
|---|---|
| SettingsHub + FeatureFlagManager spostato | Fatto (Step 1) |
| Gating completo 13 flag | Fatto (Step 2, 2B, 2C) |
| Manualistica sezioni 1-6 | Fatto (Step 3) |
| Manualistica sezioni 7-16 | Fatto (Step 4) |
| SECTION_FLAGS ManualViewer | Fatto (Step 4) |
| Tabella flag CLAUDE.md allineata a config | Fatto (Fase 3) |
| Suite test | 506 test (500 pass / 6 skipped) |
| PHPStan | Livello 6, 0 errori |
| Pint | Conforme |
