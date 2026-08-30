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

### S-01 — Sezione 08 (Progressione e volume landmarks): nessuna voce sidebar diretta ✓ RISOLTO

La sezione documenta funzionalita' accessibili solo dall'interno di altre pagine
(dettaglio mesociclo, profilo atleta). Non esiste una route top-level sensata
poiche' entrambe le sotto-pagine sono per-atleta.

**Fix applicato:** aggiunta sezione "Come raggiungerla" in `08-progressione-volume.md`
con percorso di navigazione esplicito per entrambe le funzioni
(Mesocicli → dettaglio e profilo atleta → Volume landmarks).

### S-02 — Sezione 14 (Report finanziari): nessuna voce sidebar ✓ RISOLTO

**Fix applicato:** aggiunta voce "Report finanziario" in `config/adminlte.php`
sotto TRAINING, con `'can' => 'view-financial-reports'`. Visibile solo al
gestore con flag `financial_reports` ON; nascosta automaticamente quando il
flag e' OFF (gate restituisce false).

### S-03 — "Feedback utenti" (menu COMUNICAZIONE): nessuna sezione manuale ✓ RISOLTO

**Fix applicato:** aggiunta sottosezione "Feedback utenti" in
`12-comunicazione-campagne.md` con descrizione della pagina, gate di accesso
(solo gestore), comportamento con flag `in_app_feedback` OFF e persistenza dei
dati gia' archiviati.

### S-04 — Sezione 12: citazione errata della route messaggistica ✓ RISOLTO

La sezione 12 citava `/backoffice/messages` come URL della messaggistica
one-to-one. La route effettiva e' `/backoffice/athletes/{athleteId}/messages`
(per-atleta, accessibile dal profilo atleta in backoffice).

**Fix applicato:** testo della sezione 12 corretto per descrivere il percorso
reale (Tesserati → profilo atleta → tab Messaggi).

### S-05 — File Admin/FeatureFlagManager.php non rimosso dopo migrazione a Settings ✓ RISOLTO

Dopo SET01 Step 1, il file originale `app/Livewire/Backoffice/Admin/FeatureFlagManager.php`
era rimasto come dead code. Nessun riferimento attivo (route, test, alias Livewire).

**Fix applicato:** file eliminato. `Settings\FeatureFlagManager` e' l'unica implementazione attiva.

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
