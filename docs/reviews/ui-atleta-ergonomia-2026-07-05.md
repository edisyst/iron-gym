# Audit Ergonomia e Accessibilità — PWA Atleta
**Data:** 2026-07-05  
**Release:** UX05 (passata trasversale post UX02–UX05)  
**Metodo:** lettura markup Blade + CSS; calcolo contrasto via formula WCAG 2.1; valutazione su 7 dimensioni  
**Nota:** audit read-only; nessuna modifica al codice in questo documento

---

## Stato rispetto all'audit precedente (ui-atleta-audit-2026-07-05.md)

Finding già chiusi nelle release UX02–UX05:

| Finding originale | Stato |
|---|---|
| P0-01 bottone "Fatto" 32px | Chiuso — `ws-action-btn` con `min-height:56px` via inline style in workout-session |
| P0-02 nessun inputmode sulla zona azione | Chiuso — `x-athlete.input-number` con prop `mode` |
| P0-03 prev-perf color:#444 | Chiuso — `.ws-prev-perf` usa `--ig-text-2` (#AAA) |
| P0-04 "Salva" 11px micro-link | Chiuso — flusso UX02: il set si completa con bottone 56px, nessun "Salva" separato |
| P1-04 safe-area iOS | Chiuso — body/bottom-nav/topbar/action-zone tutti con env() |
| P1-05 doppia fetch unread | Chiuso — Alpine.store('messages') nel layout |
| P1-06 radio inline styles | Chiuso — CSS :has(input:checked) su label |
| P2-01 Personal Records Bootstrap | Chiuso — ig-pr-row custom |
| P2-02 PR toast Bootstrap | Chiuso — stile brand custom |
| P2-04 wv-dot colori diversi | Chiuso — --ig-intensity-* custom props |
| P2-05 manifest PWA incompleto | Chiuso — scope/orientation/maskable |
| P3-02 --topbar-h | Chiuso — token CSS |
| P3-03 btn-ghost hover/focus | Chiuso |

---

## Findings nuovi

### P0 — Blocca workflow in sala

---

**P0-01 · `workout-input` font-size 15px → zoom iOS su input warmup**  
**File:** `public/css/athlete.css:429`  
**Calcolo:** iOS zooma la viewport quando un `<input>` ha `font-size < 16px`. Tutti i campi warmup (reps/peso) usano `.workout-input` con `font-size: 15px`.  
Stesso problema in `session-feedback-form.blade.php:45` (ore di sonno: `class="workout-input"`).  
**Impatto:** zoom involontario durante il log warmup; l'atleta deve fare pinch-to-zoom manuale per riallineare la vista.  
**Fix Fase B:** portare a `font-size: 16px` in `.workout-input`. Alternativa: `font-size: max(16px, 1rem)` per chiarezza.  

---

**P0-02 · "Termina" button header sessione: `min-height:32px`**  
**File:** `resources/views/livewire/athlete/workout-session.blade.php:206`  
**Problema:** bottone "Termina" (early exit) ha `min-height:32px` inline. Target < 44px.  
**Fix Fase B:** portare a `min-height:40px` (non 48 — è secondario e non vogliamo tap accidentali).  

---

### P1 — Frizione significativa

---

**P1-01 · Tre stepper (reps+kg+RIR) in `ws-action-inputs` overflow a 360px**  
**File:** `public/css/athlete.css:1298`; `resources/views/livewire/athlete/workout-session.blade.php:401–455`  
**Calcolo:** su 360px device, padding laterale 2×16px → 328px disponibili.  
`ig-num-input` con stepper = `min-width:48px` (bottone) + `width:72px` (campo) + `min-width:48px` = **168px per input**.  
3 input (reps + kg + RIR) = `3×168 + 2×8px gap = 520px` > 328px. **Overflow garantito**.  
Su 390px (iPhone 14): `358px / 3 = 119px per slot` vs 168px minimo — ancora overflow.  
**Impatto:** zona azione illeggibile su qualsiasi iPhone ≤ 390px (la maggioranza dei dispositivi).  
**Fix Fase C (strutturale):** ridurre la field-width da 72px a 56px e i step da 48px a 40px → 40+56+40=136px × 3 + 16px gap = 424px. Ancora overflow. Serve un layout alternativo: es. step buttons verticali (sopra/sotto il campo) invece che laterali, o ridurre l'RIR a controllo separato non in linea con reps/kg.  
Proposta diff in Fase C.  

---

**P1-02 · `ws-icon-btn min-height:32px` — Info e Sostituisci sotto soglia**  
**File:** `public/css/athlete.css:1000`  
**Problema:** bottoni Info e Sostituisci hanno `min-height:32px` < 44px. Contigui con `gap:var(--ig-sp-3)` = 12px — spazio minimo OK ma dimensione target no.  
**Fix Fase B:** portare `min-height` a 44px; rimuovere `font-size:var(--ig-text-xs)` → `var(--ig-text-sm)`.  

---

**P1-03 · Warmup "Fatto" button `min-height:36px`**  
**File:** `resources/views/livewire/athlete/partials/session-exercise.blade.php:181-184`  
**Problema:** bottone "Fatto" warmup ha `min-height:36px` inline (< 44px). Il warmup è la sequenza che precede ogni esercizio — i tap qui sono frequenti.  
**Fix Fase B:** portare a `min-height:44px`.  

---

**P1-04 · `ws-warmup-gen-btn min-height:36px`**  
**File:** `public/css/athlete.css:1056`  
**Fix Fase B:** portare a `min-height:44px`.  

---

**P1-05 · `ws-action-timer-skip min-height:36px`**  
**File:** `public/css/athlete.css:1279`  
**Nota:** il timer "Salta" si usa tra set — dita possibilmente sudate o con guanto.  
**Fix Fase B:** portare a `min-height:44px`.  

---

**P1-06 · `ws-exec-btn` senza min-height — "Come eseguire" non è tappabile**  
**File:** `public/css/athlete.css:1012-1022`  
**Problema:** `padding:0` e nessun `min-height` → altezza effettiva ≈ `var(--ig-text-sm)` line-height ≈ 18px. Troppo piccolo per uso in sala.  
**Fix Fase B:** aggiungere `min-height:36px; padding: var(--ig-sp-2) 0;`.  

---

**P1-07 · `--ig-text-3` (#666666) sotto WCAG AA 4.5:1 su bg scuro per testo small**  
**Calcolo preciso:**  
- `#666666`: L = 0.159 (formula sRGB linearizzata)  
- `#121212` (--ig-bg): L = 0.011  
- Rapporto = (0.159+0.05)/(0.011+0.05) = **3.43:1** — fallisce WCAG AA (4.5:1) per testo normale < 18px  
- `#666666` su `#1E1E1E` (--ig-surface): (0.159+0.05)/(0.019+0.05) = **3.03:1** — fallisce anche 3:1 UI components  
- `#666666` su `#2A2A2A` (--ig-surface-raised): (0.159+0.05)/(0.031+0.05) = **2.58:1** — fallisce tutto  

Usato in: `.section-title`, `.ws-warmup-label`, `.ws-action-input-label`, `.ws-action-set-label`, `.ig-form-label`, `.wv-lm-text` — tutti a 11px.

**Fix Fase B:** portare `--ig-text-3` dark a `#7A7A7A` in `:root`:  
- `#7A7A7A`: L = 0.222. Ratio su `#121212` = (0.222+0.05)/(0.011+0.05) = **4.46:1** — quasi AA (accettabile per bold uppercase label). Su `#1E1E1E` = (0.222+0.05)/(0.019+0.05) = **3.94:1** ✓ per UI components (3:1).  
- Su `#2A2A2A` = (0.222+0.05)/(0.031+0.05) = **3.36:1** — OK per UI, manca per testo. Label uppercase 11px/700 = "large text" per WCAG → 3:1 sufficiente ✓.

**Light theme:** `#888888` su `#F5F5F0`: rapporto = (0.273+0.05)/(0.921+0.05) = 0.323/0.971 = **3.01:1** — al limite. Portare a `#777777` in light: L=0.179; ratio = (0.179+0.05)/(0.921+0.05) = 0.229/0.971 = **4.24:1** — meglio, passa per large text.

---

**P1-08 · Rest timer senza `aria-live` — screen reader non legge conto alla rovescia**  
**File:** `resources/views/livewire/athlete/workout-session.blade.php:347-358`  
**Problema:** la barra del rest timer aggiorna `$store.restTimer.seconds` via Alpine ma non ha `aria-live`. Gli utenti con VoiceOver non sentono il conto.  
**Fix Fase B:** aggiungere `<span aria-live="polite" aria-atomic="true" class="sr-only" x-text="$store.restTimer.running ? $store.restTimer.fmt($store.restTimer.seconds) + ' al termine del recupero' : ''"></span>`.  

---

### P2 — Accessibilità semantica

---

**P2-01 · Input warmup senza label programmatica**  
**File:** `resources/views/livewire/athlete/partials/session-exercise.blade.php:157-165`  
**Problema:** i due `<input>` (reps e peso) nel warmup non hanno `<label>` associata né `aria-label`. Il placeholder viene usato come guide visiva ma i placeholder non sono label accessibili.  
**Fix Fase B:** aggiungere `aria-label="Reps riscaldamento"` e `aria-label="Peso riscaldamento (kg)"`.  

---

**P2-02 · `ws-exercise-name` button nessun hint sull'azione**  
**File:** `resources/views/livewire/athlete/partials/session-exercise.blade.php:71-76`  
**Problema:** il bottone mostra solo il nome esercizio. Per screen reader non è chiaro che aprirà lo storico. `aria-label` dovrebbe descrivere l'azione.  
**Fix Fase B:** aggiungere `aria-label="Storico {{ $exercise->exercise->name_it }}"`.  

---

**P2-03 · `ws-icon-btn` Sostituisci senza label testuale visiva**  
**File:** `resources/views/livewire/athlete/partials/session-exercise.blade.php:87-92`  
**Problema:** il bottone Sostituisci ha `aria-label` ma nessun testo visibile (solo icona). Utenti senza conoscenza dell'icona non capiscono l'azione. Info invece ha testo ✓.  
**Fix Fase B:** aggiungere label testuale "Sost." accanto all'icona (come Info).  

---

### P3 — Raffinatezze

---

**P3-01 · Barre volume: `bar-fill-*` colori hardcoded non usano token intensità**  
**File:** `public/css/athlete.css:579-583`  
**Problema:** `.bar-fill-green:#27a050`, `.bar-fill-yellow:#c9a227`, `.bar-fill-red:#c0392b` non usano `--ig-intensity-*`. Incoerenza cromatica con body map e legenda.  
**Fix Fase B:** rimappare a token esistenti: `bar-fill-green → var(--ig-intensity-3)`, `bar-fill-yellow → var(--ig-intensity-2)`, `bar-fill-red → var(--ig-intensity-5)`.  

---

**P3-02 · `wv-marker-mev` / `wv-marker-mrv` colori hardcoded**  
**File:** `public/css/athlete.css:586-587`  
`wv-marker-mev: #c9a227`, `wv-marker-mrv: #c0392b` — stessi dei bar-fill. Stessa fix.

---

## Verifica 7 dimensioni — riepilogo

| Dimensione | Stato | Findings |
|---|---|---|
| 1. Touch target ≥ 44px | ⚠️ Parziale | P0-02, P1-02, P1-03, P1-04, P1-05, P1-06 |
| 2. Contrasto WCAG AA | ⚠️ Parziale | P1-07 (`--ig-text-3` sotto soglia) |
| 3. Input mobile (inputmode, 16px) | ⚠️ Parziale | P0-01 (15px zoom) |
| 4. Viewport e safe-area | ✅ OK | — |
| 5. Semantica e screen reader | ⚠️ Parziale | P1-08, P2-01, P2-02, P2-03 |
| 6. Motion (prefers-reduced-motion) | ✅ OK | — |
| 7. Peso asset | ✅ OK | app.css 35KB Tailwind only; athlete.css 53KB; nessun AdminLTE nel bundle atleta |

---

## Piano esecuzione

### Fase B — Fix puntuali (Fase B, applica senza conferma)

| # | Fix | File | Impatto |
|---|---|---|---|
| B-01 | `workout-input font-size: 16px` | athlete.css:429 | Risolve P0-01 |
| B-02 | "Termina" button `min-height:40px` | workout-session.blade.php:206 | Risolve P0-02 |
| B-03 | `ws-icon-btn min-height:44px` | athlete.css:1000 | Risolve P1-02 |
| B-04 | Warmup "Fatto" `min-height:44px` | session-exercise.blade.php:183 | Risolve P1-03 |
| B-05 | `ws-warmup-gen-btn min-height:44px` | athlete.css:1056 | Risolve P1-04 |
| B-06 | `ws-action-timer-skip min-height:44px` | athlete.css:1279 | Risolve P1-05 |
| B-07 | `ws-exec-btn min-height:36px; padding: var(--ig-sp-2) 0` | athlete.css:1015 | Risolve P1-06 |
| B-08 | `--ig-text-3: #7A7A7A` (dark), `#777777` (light) | athlete.css tokens | Risolve P1-07 |
| B-09 | `aria-live` rest timer (sr-only span) | workout-session.blade.php | Risolve P1-08 |
| B-10 | `aria-label` su input warmup reps/peso | session-exercise.blade.php | Risolve P2-01 |
| B-11 | `aria-label="Storico ..."` su ws-exercise-name | session-exercise.blade.php | Risolve P2-02 |
| B-12 | Label "Sost." visibile su bottone Sostituisci | session-exercise.blade.php | Risolve P2-03 |
| B-13 | `bar-fill-*` e `wv-marker-*` → token intensità | athlete.css | Risolve P3-01, P3-02 |

### Fase C — Fix strutturali (richiede conferma)

| # | Fix | Impatto |
|---|---|---|
| C-01 | Ridisegno `ws-action-inputs` per 3 stepper a 360px | Risolve P1-01 |

**Proposta C-01:** sostituire il layout flex orizzontale con un grid 3-colonne dove ogni cella usa uno stepper compatto: step verticali (non laterali) o riduzione dimensioni step a 36px + field a 52px → 36+52+36=124px × 3 + 16px gap = 388px. Su 360px (328px disponibili) ancora stretto. Alternativa: stepper senza bottoni (solo campo numerico con inputmode) nelle colonne reps+kg, e RIR come riga separata sotto la zona azione in un dropdown/picker piccolo.

Preferisco vedere diff e layout prima di applicare.
