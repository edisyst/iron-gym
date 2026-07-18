# UI Atleta — Design System

Inventario completo dei token CSS, componenti Blade e pattern CSS della PWA atleta.
Aggiornato dopo UX01–UX06.

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
| `--ig-text-3` | `#888888` | `#767676` |

### Accento (brand arancio)

| Token | Dark | Light |
|---|---|---|
| `--ig-accent` | `#FF6B00` | `#C05000` |
| `--ig-accent-hover` | `#E05E00` | `#B84E00` |
| `--ig-accent-subtle` | `rgba(255,107,0,0.12)` | `rgba(192,80,0,0.10)` |

### Semantic

| Token | Dark | Light | Uso |
|---|---|---|---|
| `--ig-success` | `#22C55E` | `#16A34A` | Set completato, PR |
| `--ig-success-subtle` | `rgba(34,197,94,0.15)` | `rgba(22,163,74,0.12)` | Badge completato |
| `--ig-warning` | `#F59E0B` | `#D97706` | Deload, readiness media |
| `--ig-warning-subtle` | `rgba(245,158,11,0.15)` | `rgba(217,119,6,0.12)` | Badge warning |
| `--ig-danger` | `#EF4444` | `#DC2626` | Errori, joint pain, skip |
| `--ig-danger-subtle` | `rgba(239,68,68,0.15)` | `rgba(220,38,38,0.10)` | Badge danger |

### Intensità volume muscolare

Unica fonte di verità per `body-map-muscle` (SVG fill) e `wv-dot` (legenda).

| Token | Colore dark | Uso |
|---|---|---|
| `--ig-intensity-0` | `#2a2a2a` | Riposo / nessun volume |
| `--ig-intensity-1` | `#1a3a5c` | Volume basso |
| `--ig-intensity-2` | `#7a6010` | Volume moderato-basso |
| `--ig-intensity-3` | `#1a6635` | Volume moderato-alto |
| `--ig-intensity-4` | `#a05510` | Volume alto |
| `--ig-intensity-5` | `#8b2020` | Volume massimo / prossimo MRV |

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
| `--topbar-h` | 48px | Altezza top bar — usato in `body padding-top` e `.app-main margin-top` |
| `--ig-transition` | 180ms | Durata standard transizioni CSS |
| `--ig-transition-slow` | 280ms | Transizioni più lente (es. modali) |
| `--ig-z-overlay` | 400 | Modal detail/history |
| `--ig-z-modal` | 600 | Modal principali |
| `--ig-z-nav` | 1000 | Top bar, bottom nav |
| `--ig-z-sidenav` | 1001 | Sidebar desktop |
| `--ig-z-toast` | 1060 | Toast stack |

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

Spinner `wire:loading` integrato. `min-height: var(--ig-touch-target)`.

### `x-athlete.card`

```blade
<x-athlete.card>Contenuto con padding</x-athlete.card>
<x-athlete.card :padding="false">Contenuto a bordo</x-athlete.card>
<x-athlete.card tag="section">Card semantica</x-athlete.card>
```

Props: `padding` (bool, default true), `mb` (bool, default true), `tag` (default `div`).

### `x-athlete.stat`

```blade
<x-athlete.stat label="e1RM" unit="kg">102.5</x-athlete.stat>
```

Props: `label` (string), `unit` (string|null). Slot: valore numerico. Usa `tabular-nums` e `--ig-text-xl`.

### `x-athlete.badge`

```blade
<x-athlete.badge status="completed">Completata</x-athlete.badge>
<x-athlete.badge status="deload">DELOAD</x-athlete.badge>
```

`status` → `ig-badge--{status}`: `planned`/`gray` (grigio), `in_progress`/`accent` (arancio),
`completed`/`success` (verde), `skipped`/`danger` (rosso), `deload` (arancio pieno), `warning` (giallo).

### `x-athlete.input-number`

```blade
<x-athlete.input-number wire:model="setData.1.reps" mode="numeric" placeholder="0" />
<x-athlete.input-number wire:model="weight" mode="decimal" step="2.5" :stepper="true" placeholder="kg" />
```

Props: `mode` (`numeric`|`decimal`), `min`, `max`, `step`, `placeholder`, `stepper` (bool, default false).
Stepper usa `x-ref="numInput"` Alpine; emette `input`+`change` per compatibilità `wire:model`.

### `x-athlete.bottom-nav` (UX03)

```blade
<x-athlete.bottom-nav />
```

4 tab: Home (`athlete.dashboard`), Allenamento (`athlete.history`), Progressi (`athlete.volume`),
Profilo (`athlete.profile`). Profilo mostra badge messaggi non letti via `$store.messages.unread`
(inizializzato una sola volta nel layout, nessun fetch duplicato).
Attivo per route correlate: es. `session/*` attiva Allenamento, `records/*` attiva Progressi.

### `x-athlete.empty-state` (UX04)

```blade
<x-athlete.empty-state title="Nessuna sessione" body="Completa qualche sessione." href="/athlete" cta="Vai alla home">
    <svg>...</svg>  {{-- icona opzionale nello slot --}}
</x-athlete.empty-state>
```

Props: `title` (string), `body` (string|null), `href` (string|null), `cta` (string|null).
Slot opzionale: icona SVG. Classe CSS: `.ig-empty`. Usa `role="status"`.

### `x-athlete.toast` (UX04)

```blade
{{-- Incluso una sola volta in athlete.blade.php, dopo il bottom nav --}}
<x-athlete.toast />
```

Alpine `x-data` con coda `queue[]`. Ascolta eventi browser:
- `toast` → `{ message, type }` — tipi: `success`, `error`, `info`, `set` (2 s), altri (3.2 s)
- `set-completed` → shortcut toast tipo `set`

Dispatch da Livewire: `$this->dispatch('toast', message: '...', type: 'success')`.
`role="alert"` su ogni item. x-transition con classi CSS `.ig-toast-enter` / `.ig-toast-leave`.

### `x-athlete.skeleton` (UX04)

```blade
<div wire:loading wire:target="loadData">
    <x-athlete.skeleton :lines="4" height="200px" />
</div>
```

Props: `lines` (int, default 3), `height` (string|null — altezza prima riga).
`aria-hidden="true"` + `aria-label="Caricamento..."`. Shimmer via `@keyframes ig-shimmer`.
Rispetta `@media (prefers-reduced-motion: reduce)`.

---

## Classi CSS pattern

### Feedback metriche (`.metric-options`)

```blade
<div class="metric-options">
    <label>
        <input type="radio" wire:model="pump" value="0">
        <span>0</span>
    </label>
</div>
```

Active state via CSS puro `label:has(input:checked)` — nessun PHP conditional inline.
Fallback `input:checked + span` per browser senza `:has()`.

### Tab switcher (`.ig-tab-group` / `.ig-tab`)

```blade
<div class="ig-tab-group">
    <button class="ig-tab {{ $activeTab === 'pt' ? 'ig-tab--active' : '' }}"
            wire:click="$set('activeTab','pt')">Sessione PT</button>
    <button class="ig-tab {{ $activeTab === 'classes' ? 'ig-tab--active' : '' }}"
            wire:click="$set('activeTab','classes')">Corsi</button>
</div>

{{-- Con Alpine (stato client-side immediato) --}}
<div class="ig-tab-group">
    <button class="ig-tab" :class="{ 'ig-tab--active': tab === 'body' }"
            @click="tab='body'">Corpo</button>
</div>
```

Variante danger: aggiungere `ig-tab--danger` al bottone + `ig-tab--active` → background `--ig-danger`.

### Campi form (`.ig-form-input` / `.ig-form-label` / `.ig-field-error`)

```blade
<label class="ig-form-label">Nome</label>
<input type="text" wire:model="name"
       class="ig-form-input {{ $errors->has('name') ? 'is-invalid' : '' }}">
@error('name') <span class="ig-field-error">{{ $message }}</span> @enderror
```

`.is-invalid` → `border-color: var(--ig-danger)`. `.ig-field-error` usa `var(--ig-danger)` e `font-size: var(--ig-text-xs)`.

### Loading / wire:loading

Pattern standard per bottoni azione:
```blade
<button wire:click="save" wire:loading.attr="disabled">
    <span wire:loading.remove>Salva</span>
    <span wire:loading>Salvataggio...</span>
</button>
```

Per liste con skeleton:
```blade
<div wire:loading wire:target="loadData"><x-athlete.skeleton :lines="3" /></div>
<div wire:loading.remove wire:target="loadData">
    @forelse($items as $item) ... @empty <x-athlete.empty-state ... /> @endforelse
</div>
```

---

## Gestione tema

**No FOUC:** script inline nel `<head>` (prima di `<link rel="stylesheet">`) legge `localStorage['ig-theme']`
e imposta `data-theme` su `<html>`. Nessun flash visibile.

**Toggle:** bottone `.ig-theme-toggle` nella topbar. Props a11y: `aria-pressed` dinamico
(`:aria-pressed="theme === 'light'"`), `aria-label` "Attiva tema chiaro/scuro",
label testuale visibile "Chiaro"/"Scuro" via `.ig-theme-toggle-label`. `min-height: var(--ig-touch-target)`.

**Cascade:**
1. `[data-theme="light"]` / `[data-theme="dark"]` — scelta utente (vince sempre)
2. `@media (prefers-color-scheme: light)` su `:root:not([data-theme])` — preferenza sistema
3. `:root` dark default

---

## Toggle viewport (devtools, solo `local`)

Strumento di revisione grafica — non esposto in produzione.

**Meccanismo:** script inline nel `<head>` (subito dopo lo script anti-FOUC, protetto da `@if(app()->environment('local'))`)
legge `localStorage['ig-viewport']`. Se vale `'desktop'`, sovrascrive il `content` di `meta[name=viewport]`
a `width=1280, initial-scale=1` prima che il CSS venga applicato.

**Toggle UI:** sezione "Strumenti sviluppo" in `/athlete/profile`, visibile solo in `local`.
Bottone con `aria-pressed`, al click scrive/rimuove `localStorage['ig-viewport']` e chiama `location.reload()`.

**Badge stato:** `.ig-viewport-badge` — pill giallo fisso in alto a destra (`z-index: var(--ig-z-toast)`),
visibile via Alpine `x-show` quando `localStorage['ig-viewport'] === 'desktop'`. `pointer-events: none`.

**Limiti noti:**
- `safe-area-inset-*` non calcolate (meta viewport diverso) — padding topbar/bottom-nav perde i margini iOS
- DPR del device rimane quello fisico (es. 3x) — testo piu' nitido rispetto a un desktop reale
- Hover states, font hinting e scroll behavior differiscono da un browser desktop reale
- `location.reload()` necessario per riapplicare il meta viewport in modo pulito

---

## Alpine store globali (layout `athlete.blade.php`)

| Store | Proprietà | Inizializzazione | Uso |
|---|---|---|---|
| `messages` | `unread: int` | `fetch('/athlete/messages-unread-count')` una volta per pagina | Badge in bottom-nav e sidebar |
| `restTimer` | `active`, `remaining`, `fmt(s)`, `start(sec)`, `skip()` | definito in `workout-session.blade.php` | Timer riposo fisso in basso durante sessione |
| `syncQueue` | `enqueue()`, `flush()`, `isPending()` | definito in `workout-session.blade.php` | Coda operazioni offline → IndexedDB |

**Network error:** `livewire:request-failed` → `toast` event con `type: 'error'` (gestito in layout).

---

## File CSS

| File | Scopo |
|---|---|
| `public/css/athlete.css` | Token + base + legacy + componenti `ig-*` — unico entry point CSS atleta |
| `public/css/session-recap.css` | Standalone card recap (export PNG, nessuna dipendenza da `athlete.css`) |

---

## Classi legacy ancora in uso

Non ancora migrate a `ig-*`. Usano già i token CSS e si adattano al tema.

| Classe legacy | Target ig-* | Note |
|---|---|---|
| `.athlete-card` | `x-athlete.card` | In graduale sostituzione |
| `.athlete-badge` | `x-athlete.badge` | In graduale sostituzione |
| `.btn-accent` | `x-athlete.button variant="primary"` | Usato in view non ancora migrate |
| `.btn-ghost` | `x-athlete.button variant="ghost"` | Usato in view non ancora migrate |
| `.workout-input` | `x-athlete.input-number` | Usato in sessione; `font-size:16px` obbligatorio (evita zoom iOS) |
| `.section-title` | (label uppercase inline) | Usato in molte view |
| `.metric-row` / `.metric-options` | — | Pattern feedback sessione, non componente |
| `.sr-only` | — | Screen-reader only; visivamente nascosto, accessibile ai SR |
| `.bar-fill-green/yellow/red` | — | Barre volume; mappate a `--ig-intensity-3/2/5` |
