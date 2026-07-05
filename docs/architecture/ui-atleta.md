# UI Atleta — Design System (UX01)

Inventario dei token CSS e dei componenti Blade della PWA atleta.
File di riferimento per le release UX02–UX05.

---

## Token CSS

Definiti in `:root` dentro `public/css/athlete.css`.
Dark theme di default. Light theme via `[data-theme="light"]` (Alpine/localStorage) e
`@media (prefers-color-scheme: light)` (fallback sistema se nessun tema esplicito salvato).

### Superfici

| Token | Dark | Light | Uso |
|---|---|---|---|
| `--ig-bg` | `#121212` | `#F5F5F0` | Sfondo body |
| `--ig-surface` | `#1E1E1E` | `#FFFFFF` | Card, nav bar |
| `--ig-surface-raised` | `#2A2A2A` | `#F0F0EB` | Input, badge, bottoni secondari |
| `--ig-border` | `#2E2E2E` | `#DCDCD6` | Bordi card, input |
| `--ig-border-subtle` | `#222222` | `#E8E8E4` | Separatori interni |

### Testo

| Token | Dark | Light |
|---|---|---|
| `--ig-text-1` | `#FFFFFF` | `#111111` |
| `--ig-text-2` | `#AAAAAA` | `#555555` |
| `--ig-text-3` | `#666666` | `#888888` |

### Accento (brand arancio)

| Token | Dark | Light |
|---|---|---|
| `--ig-accent` | `#FF6B00` | `#D45A00` |
| `--ig-accent-hover` | `#E05E00` | `#B84E00` |
| `--ig-accent-subtle` | `rgba(255,107,0,0.12)` | `rgba(212,90,0,0.10)` |

### Semantic

| Token | Dark | Light | Uso |
|---|---|---|---|
| `--ig-success` | `#22C55E` | `#16A34A` | Set completato, PR |
| `--ig-success-subtle` | `rgba(34,197,94,0.15)` | `rgba(22,163,74,0.12)` | Badge completato |
| `--ig-warning` | `#F59E0B` | `#D97706` | Deload, readiness media |
| `--ig-warning-subtle` | `rgba(245,158,11,0.15)` | `rgba(217,119,6,0.12)` | Badge warning |
| `--ig-danger` | `#EF4444` | `#DC2626` | Errori, joint pain, skip |
| `--ig-danger-subtle` | `rgba(239,68,68,0.15)` | `rgba(220,38,38,0.10)` | Badge danger |

### Tipografia

| Token | Valore | Uso |
|---|---|---|
| `--ig-font-sans` | system-ui stack | Font di base |
| `--ig-text-xs` | 11px | Label uppercase, badge, colonne tabella |
| `--ig-text-sm` | 13px | Testo secondario, date |
| `--ig-text-base` | 16px | Corpo testo, input (evita zoom iOS) |
| `--ig-text-md` | 18px | Unità in stat, sottotitoli |
| `--ig-text-lg` | 22px | Input numerici set |
| `--ig-text-xl` | 28px | Valori stat (e1RM, peso, reps) |
| `--ig-text-display` | 42px | Timer, numero peso principale |

### Spacing (base 4px)

`--ig-sp-1` (4px) → `--ig-sp-2` (8px) → `--ig-sp-3` (12px) → `--ig-sp-4` (16px)
→ `--ig-sp-5` (20px) → `--ig-sp-6` (24px) → `--ig-sp-8` (32px) → `--ig-sp-10` (40px)

### Radius

| Token | Valore | Uso |
|---|---|---|
| `--ig-radius-sm` | 6px | Input, bottoni piccoli |
| `--ig-radius` | 10px | Bottoni standard, input numerico |
| `--ig-radius-lg` | 14px | Card |
| `--ig-radius-full` | 999px | Badge, pill |

### Valori speciali

| Token | Valore | Uso |
|---|---|---|
| `--ig-touch-target` | 48px | `min-height` per tutti gli elementi interattivi |
| `--ig-z-overlay` | 400 | Modal detail/history |
| `--ig-z-modal` | 600 | Modal principali |
| `--ig-z-nav` | 1000 | Top bar, bottom nav |
| `--ig-z-sidenav` | 1001 | Sidebar desktop |
| `--ig-z-toast` | 1060 | Toast PR |

---

## Componenti Blade

Namespace: `x-athlete.*`
Path: `resources/views/components/athlete/`

### `x-athlete.button`

```blade
<x-athlete.button variant="primary" wire:click="save">Salva</x-athlete.button>
<x-athlete.button variant="ghost" :full="true">Annulla</x-athlete.button>
<x-athlete.button variant="danger" size="sm">Elimina</x-athlete.button>
```

Props:
- `variant`: `primary` | `secondary` | `ghost` | `danger` (default: `primary`)
- `type`: `button` | `submit` | `reset` (default: `button`)
- `full`: bool — `width:100%` (default: `false`)
- `size`: `base` | `sm` (default: `base`)

Comportamento: gestisce automaticamente `wire:loading` per il target del `wire:click`/`wire:submit` dell'attributo. Spinner integrato. `min-height: var(--ig-touch-target)`.

### `x-athlete.card`

```blade
<x-athlete.card>Contenuto con padding</x-athlete.card>
<x-athlete.card :padding="false">Contenuto a bordo</x-athlete.card>
<x-athlete.card tag="section">Card semantica</x-athlete.card>
```

Props:
- `padding`: bool — aggiunge `ig-card--padded` (default: `true`)
- `mb`: bool — aggiunge `ig-card--mb` margin-bottom (default: `true`)
- `tag`: elemento HTML (default: `div`)

### `x-athlete.stat`

```blade
<x-athlete.stat label="e1RM" unit="kg">102.5</x-athlete.stat>
<x-athlete.stat label="Durata">47 min</x-athlete.stat>
```

Props:
- `label`: string — etichetta uppercase
- `unit`: string|null — unità di misura (più piccola, colore secondario)

Slot: il valore numerico. Usa `font-variant-numeric: tabular-nums` e `--ig-text-xl`.

### `x-athlete.badge`

```blade
<x-athlete.badge status="completed">Completata</x-athlete.badge>
<x-athlete.badge status="deload">DELOAD</x-athlete.badge>
<x-athlete.badge status="warning">Readiness bassa</x-athlete.badge>
```

`status` map → `ig-badge--{status}`:
- `planned` / `gray` / `secondary` → grigio
- `in_progress` / `accent` → arancio
- `completed` / `success` → verde
- `skipped` / `danger` → rosso
- `deload` → arancio pieno
- `warning` → giallo

### `x-athlete.input-number`

```blade
{{-- Solo campo --}}
<x-athlete.input-number wire:model="setData.1.reps" mode="numeric" placeholder="0" />

{{-- Con stepper +/− (UX02) --}}
<x-athlete.input-number
    wire:model="weight"
    mode="decimal"
    step="2.5"
    :stepper="true"
    placeholder="kg"
/>
```

Props:
- `mode`: `numeric` | `decimal` (imposta `inputmode`, default: `numeric`)
- `min`, `max`, `step`: passati all'`<input>` HTML
- `placeholder`: stringa
- `stepper`: bool — mostra bottoni +/− con `min-height: var(--ig-touch-target)` (default: `false`)

I bottoni stepper usano `x-ref="numInput"` via Alpine; emettono `input` e `change` per compatibilità con `wire:model`.

---

## Gestione tema

**Inizializzazione (no FOUC):** script inline nel `<head>` del layout `athlete.blade.php` legge
`localStorage.getItem('ig-theme')` e lo imposta come `data-theme` su `<html>` prima che il CSS venga applicato.

**Toggle:** bottone `.ig-theme-toggle` nella topbar (Alpine `x-data` sull'`<header>`).
Salva la scelta in `localStorage['ig-theme']`.

**Cascade di precedenza:**
1. `[data-theme="light"]` / `[data-theme="dark"]` — scelta esplicita utente (vince sempre)
2. `@media (prefers-color-scheme: light)` su `:root:not([data-theme])` — preferenza sistema
3. `:root` dark default

---

## Classi legacy ancora in uso

Le seguenti classi di `athlete.css` non sono ancora migrate a `ig-*` e restano per backward compat
con le view UX02–UX05. Usano già i token CSS e si adattano al tema light/dark.

| Classe legacy | Componente ig-* target | Migrata in |
|---|---|---|
| `.athlete-card` | `x-athlete.card` | UX02–UX05 (progressivo) |
| `.athlete-badge` | `x-athlete.badge` | UX02–UX05 |
| `.btn-accent` | `x-athlete.button variant="primary"` | UX02 |
| `.btn-ghost` | `x-athlete.button variant="ghost"` | UX02 |
| `.workout-input` | `x-athlete.input-number` | UX02 |
| `.section-title` | (inline o classe ig-) | UX02–UX05 |
| `.status-*` | `x-athlete.badge status="*"` | UX03 |

---

## File CSS

| File | Scopo |
|---|---|
| `public/css/athlete.css` | Token + base + legacy + componenti `ig-*` — unico entry point CSS atleta |
| `public/css/session-recap.css` | Standalone card recap (esportata come PNG, nessuna dipendenza da `athlete.css`) |
