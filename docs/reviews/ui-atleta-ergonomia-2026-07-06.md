# Audit Ergonomia PWA Atleta — 2026-07-06

Passata trasversale su tutte le view atleta. Criteri: ergonomia uso in sala pesi (una mano,
attenzione bassa), WCAG AA, input mobile, safe-area, semantica SR, motion, asset.

---

## Legenda priorità

| Livello | Significato |
|---|---|
| P0 | Blocco funzionale / sicurezza |
| P1 | Alta — impatta utenti reali; fix in Fase B |
| P2 | Media — degrada usabilità / accessibilità |
| P3 | Bassa — polish, edge case |

---

## P1 — Critici

### P1-A · Safe-area topbar annullata da shorthand `padding`
**File:** `public/css/athlete.css:183-190`  
**Problema:** `padding-top: env(safe-area-inset-top, 0px)` alla riga 183 viene sovrascritto da
`padding: 0 var(--ig-sp-4)` alla riga 190. Su iPhone X+ con `viewport-fit=cover` il contenuto
della topbar si sovrappone alla camera notch / Dynamic Island.  
**Fix Fase B:** Sostituire `padding: 0 var(--ig-sp-4)` con
`padding: env(safe-area-inset-top, 0px) var(--ig-sp-4) 0`.

### P1-B · Accento light theme non raggiunge WCAG AA su testo
**File:** `public/css/athlete.css:111`  
**Calcolo:** `#D45A00` su `#FFFFFF` → L_accent=0.213, L_bg=1.0 → **3.99:1** (soglia 4.5:1).  
**Impatto:** `.app-brand` (topbar, 16px/700), `.ig-badge--in_progress` (11px/700), overlap
%.  
**Fix Fase B:** Cambiare `--ig-accent` in `[data-theme="light"]` da `#D45A00` a `#C05000`
(L=0.1695, contrasto 4.78:1 su bianco).

---

## P2 — Medi

### P2-A · Touch target sotto 48px — elementi frequenti sessione
Token `--ig-touch-target: 48px`. Elementi sotto soglia (verificati in CSS):

| Elemento | CSS / File | Altezza misurata | Gap |
|---|---|---|---|
| `.ws-icon-btn` (Info, Sost.) | `athlete.css:1013` | 44px min-height | −4px |
| `.ws-warmup-gen-btn` | `athlete.css:1075` | 44px min-height | −4px |
| `.ws-action-timer-skip` (Salta) | `athlete.css:1297` | 44px min-height | −4px |
| `.ws-exec-btn` (Come eseguire) | `athlete.css:1037` | 36px min-height | −12px |
| `.ig-theme-toggle` | `athlete.css:818` | 32px min-height | −16px |
| Bottone × delete warmup | `session-exercise.blade.php:204` | inline, ~24px | −24px |
| Bottone × close modali (4x) | `workout-session.blade.php:515,560,598,659` | ~22px | −26px |
| "Usa questo esercizio" | `workout-session.blade.php:691` | ~31px | −17px |
| Plate calc open btn | `workout-session.blade.php:258-259` | 32px min-height | −16px |
| `.home-last-link` (share icon) | `athlete.css:1528` | ~18px | −30px |
| `.home-week-action` (freccia) | `athlete.css:1595` | ~16px | −32px |
| `.home-week-restore` (Ripristina) | `athlete.css:1603` | ~28px | −20px |
| `Termina` button in progress header | `workout-session.blade.php:206` | 40px min-height | −8px |

### P2-B · Input `font-size: 14px` apre zoom iOS
**File:** `workout-session.blade.php:607` (input peso nel plate calculator)  
`font-size:14px` sotto la soglia iOS (16px) → zoom automatico al focus.  
**Fix Fase B:** aggiungere `font-size:16px`.

### P2-C · Label non associate agli input nella zona azione
**File:** `workout-session.blade.php:406-458`  
I label "Reps", "Kg", "RIR", "Secondi" sono `<span class="ws-action-input-label">` non
collegati semanticamente all'input-number sotto. Screen reader non annuncia il campo.  
**Fix Fase B (basso rischio):** non toccando il componente `x-athlete.input-number`, wrappare
ogni `ws-action-input-group` in un `<label>` o aggiungere `aria-label` al campo.  
Meglio: passare il prop `aria-label` all'attributo `{{ $attributes }}` già presente nel
componente.

### P2-D · Modali sessione non usano token CSS (dark-only)
**File:** `workout-session.blade.php:508-700` (detail, history, plate, substitution)  
Background hardcoded `#1A1A1A`, testi `#ccc`, `#666`. In light theme le modali restano
dark (ok per la modal in sé) ma il contrasto testo su fondo hardcoded varia.  
Colori hardcoded anche per success/warning: `#22c55e`, `#f59e0b` invece di
`var(--ig-success)` / `var(--ig-warning)`.  
**Fix Fase C** (cambio markup esteso).

### P2-E · `--ig-text-3` in dark (#7A7A7A) su `--ig-surface` (#1E1E1E): 3.88:1
Fallisce WCAG AA per testo normale (soglia 4.5:1). Usato in `.section-title` (11px/700),
`.ws-warmup-label` (11px), `.wv-lm-text` (11px), body map label SVG (11px).  
Testo grande (≥18pt) o componenti UI (≥3:1) non è impattato.  
**Fix Fase B:** alzare `--ig-text-3` dark da `#7A7A7A` a `#888888`
(L=0.228, contrasto 4.56:1 su `--ig-surface`).

---

## P3 — Bassi

### P3-A · Jump drawer: `translate-y-full` assente nel CSS compilato
**File:** `workout-session.blade.php:273-275`, `public/build/assets/app-B22fhkjk.css`  
`x-transition:enter-start="transform translate-y-full"` — la classe Tailwind
`translate-y-full` non è nel bundle (mancante dalla scansione JIT). Il drawer appare
istantaneamente senza slide-up. Funzionalità intatta, solo animazione mancante.  
**Fix Fase C:** aggiungere `translate-y-full` ai safelist in `tailwind.config.js`.

### P3-B · `autocomplete` assente su form profilo e misurazioni
**File:** `profile.blade.php:36-45`, `body-measurement-form.blade.php`  
Input nome, email, password senza `autocomplete`. Password manager e Safari non aiutano.  
**Fix Fase B:** aggiungere `autocomplete="name"`, `autocomplete="email"`,
`autocomplete="current-password"` / `new-password`.

### P3-C · `--ig-text-3` light (#777777) su bianco: 4.47:1 (borderline)
Tecnicamente sotto WCAG AA (4.5:1) di 0.03 punti. Impatto pratico minimo.  
Risolto di fatto dal fix P2-E se si porta #888 in dark; per light si può alzare a `#767676`
(standard accessibility baseline, 4.54:1) da `#777777`.

### P3-D · PR toast usa classi Tailwind non standard (`opacity-0 translate-y-4`)
**File:** `layouts/athlete.blade.php:158-161`  
Tailwind JIT include `opacity-0`, `translate-y-4`, `translate-y-0` ma mancano nelle
x-transition — verificato presenti nel bundle, quindi funziona. Nota: inconsistenza con
sistema ig-toast che usa classi CSS custom. Non urgente.

### P3-E · Larghezza 360px: 3 stepper in action zone possono comprimere i campi
**File:** `workout-session.blade.php:404-458`, `athlete.css:743-805`  
Con 3 `x-athlete.input-number stepper` in flex: ogni stepper minimo 96px (48+48) + campo
72px = 168px. A 360px (328 disponibili dopo padding) ogni gruppo ottiene ~109px; i campi
si comprimono a ~13px di larghezza → illeggibili.  
**Fix Fase C** (ristrutturazione layout action zone, es. grid 2-col su schermi stretti).

---

## Verifica 7 punti checklist

| Punto | Stato | Note |
|---|---|---|
| 1. Touch target 48px | ⚠ Parziale | P2-A: 13 elementi sotto soglia |
| 2. Contrasto WCAG AA | ⚠ Parziale | P1-B, P2-E, P3-C |
| 3. Input mobile (inputmode, font-size ≥ 16px) | ⚠ Quasi OK | P2-B: 1 campo 14px; inputmode OK ovunque |
| 4. Viewport + safe-area | ⚠ Bug | P1-A: topbar overrides safe-area inset |
| 5. Semantica SR (button, label, aria-live) | ⚠ Parziale | P2-C: label/input non associati; button veri OK; aria-live timer OK |
| 6. prefers-reduced-motion | ✓ OK | Coperta globalmente in sezione 15 athlete.css |
| 7. Peso asset atleta | ✓ OK | athlete.css 53KB; no AdminLTE nel bundle Vite |

---

## Riepilogo fix per fase

**Fase B (applica — low-risk):**
- P1-A safe-area topbar
- P1-B accento light → #C05000
- P2-A touch targets in CSS e blade inline (elementi ≤ 5 file)
- P2-B font-size 16px plate input
- P2-C aria-label su input-number action zone
- P2-E text-3 dark → #888888
- P3-B autocomplete su form
- P3-C text-3 light → #767676

**Fase C (proponi, non applicare):**
- P2-D tokenizzare colori modali sessione
- P3-A safelist translate-y-full in tailwind.config.js
- P3-E action zone layout a 360px
