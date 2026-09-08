# Standard strutturale UI backoffice — BO01

## Canone

Ogni pagina backoffice segue questa gerarchia di blocchi di primo livello:

1. **`<x-bo.filters>`** — box filtri (opzionale, solo se la view ha filtri)
2. **`<x-bo.card>`** — contenitore del corpo principale

Il layout imposta il titolo via `->layoutData(['page_title' => '...'])` nel `render()` del componente (new-style Livewire 3). Tutti i `render()` backoffice devono usare questa forma.

Il breadcrumb è rinviato a BO02.

---

## Componenti `x-bo.*`

Namespace: `x-bo.*`
Path: `resources/views/components/bo/`
Nessuna classe PHP — solo Blade anonimi con `@props`.

### `x-bo.filters`

Box filtri standard.

```blade
@props(['title' => 'Filtri'])
```

| Prop | Default | Descrizione |
|---|---|---|
| `title` | `'Filtri'` | Testo `card-title` nell'header |

Slot: `$slot` — contenuto del `card-body` (controlli filtro).

Markup prodotto: `card card-outline card-primary mb-3` con `card-header` + `card-body`.

Classi aggiuntive passabili tramite `$attributes`:

```blade
<x-bo.filters class="mb-4">
    ...
</x-bo.filters>
```

**Esempio — lista con filtri:**

```blade
<x-bo.filters>
    <div class="row">
        <div class="col-md-6">
            <label class="small">Cerca</label>
            <input type="text" wire:model.live.debounce.300ms="search"
                   class="form-control form-control-sm filter-w-lg">
        </div>
        <div class="col-md-3">
            <label class="small">Stato</label>
            <select wire:model.live="statusFilter" class="form-control form-control-sm filter-w-md">
                <option value="">Tutti</option>
                <option value="active">Attivi</option>
            </select>
        </div>
    </div>
</x-bo.filters>
```

---

### `x-bo.card`

Contenitore del corpo principale.

```blade
@props(['title' => null, 'bodyClass' => 'p-0'])
```

| Prop | Default | Descrizione |
|---|---|---|
| `title` | `null` | Testo `card-title` nell'header (omesso se null) |
| `bodyClass` | `'p-0'` | Classe CSS del `card-body` |

Slot:

| Nome | Reso in | Condizione |
|---|---|---|
| `$slot` (default) | `card-body` | sempre |
| `$actions` (named) | `card-tools` nel `card-header` | solo se non vuoto |
| `$footer` (named) | dopo `card-body` | solo se non vuoto |

L'header è renderizzato solo se `$title !== null` oppure `$actions` non è vuoto.

**Convenzione `bodyClass`:**
- Liste tabellari: `bodyClass="p-0"` (default)
- Form: `bodyClass="p-3"`

**Esempio — lista:**

```blade
<x-bo.card title="Tesserati" bodyClass="p-0">
    <x-slot name="actions">
        <a href="{{ route('...create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Nuovo
        </a>
    </x-slot>

    <table class="table table-hover table-striped mb-0">
        <thead>...</thead>
        <tbody>
            @forelse ($items as $item)
                <tr>...</tr>
            @empty
                <x-bo.empty :colspan="5">Nessun elemento trovato.</x-bo.empty>
            @endforelse
        </tbody>
    </table>

    <x-slot name="footer">
        <x-bo.pagination :paginator="$items" />
    </x-slot>
</x-bo.card>
```

**Esempio — form:**

```blade
<form wire:submit="save">
    <x-bo.card title="Nuovo elemento" bodyClass="p-3">
        <div class="form-group">
            <label>Nome</label>
            <input type="text" wire:model="name" class="form-control">
        </div>

        <x-slot name="footer">
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Salva</button>
                <a href="{{ route('...index') }}" class="btn btn-default ml-2">Annulla</a>
            </div>
        </x-slot>
    </x-bo.card>
</form>
```

Nota: nei form, il tag `<form>` wrappa l'intera `x-bo.card`. Il bottone `type="submit"` nel footer slot è dentro il form e funziona correttamente.

---

### `x-bo.empty`

Empty state unificato per liste vuote.

```blade
@props(['colspan' => null])
```

| Prop | Default | Descrizione |
|---|---|---|
| `colspan` | `null` | Se valorizzato, rende `<tr><td colspan="N">` (per tabelle); altrimenti `<div>` |

Slot: `$slot` — testo dell'empty state (deve corrispondere alla stringa asserita dai test).

**Esempio in tabella:**

```blade
@empty
    <x-bo.empty :colspan="6">Nessun tesserato trovato.</x-bo.empty>
@endforelse
```

**Esempio fuori tabella:**

```blade
<x-bo.empty>Nessun risultato disponibile.</x-bo.empty>
```

---

### `x-bo.pagination`

Footer di paginazione condizionale.

```blade
@props(['paginator'])
```

| Prop | Obbligatoria | Descrizione |
|---|---|---|
| `paginator` | sì | Istanza `LengthAwarePaginator` (risultato di `->paginate()`) |

Non renderizza nulla se `$paginator->hasPages()` è false.
Renderizza `<div class="card-footer">{{ $paginator->links() }}</div>` se true.

Usato sempre nello slot `footer` di `x-bo.card`:

```blade
<x-slot name="footer">
    <x-bo.pagination :paginator="$items" />
</x-slot>
```

---

## Layout `render()`

Standard new-style Livewire 3 obbligatorio per tutte le view backoffice:

```php
return view('livewire.backoffice.area.component-name', [...])
    ->layout('layouts.backoffice')
    ->layoutData(['page_title' => 'Titolo pagina']);
```

Per titoli dinamici (form create/edit):

```php
$title = $this->itemId ? 'Modifica elemento' : 'Nuovo elemento';

return view('livewire.backoffice.area.component-name')
    ->layout('layouts.backoffice')
    ->layoutData(['page_title' => $title]);
```

---

## View esentate dal canone del corpo

Le seguenti view hanno struttura non standard per ragioni di dominio — non applicare `x-bo.card` come contenitore principale:

| View | Motivo esenzione |
|---|---|
| `dashboard` | Widget `small-box` + KPI card — layout composito AdminLTE |
| `athlete-profile` | Nav tabs + sub-componenti `@livewire` |
| `athlete-analytics` | Layout multi-card grafico |
| `body-measurement-form` | Due colonne: form + history |
| `trainer-calendar` | FullCalendar — card con padding specifico |
| `communication-campaign` | Card sezionata con sub-card |
| `financial-report` / `manager-dashboard` | KPI row + chart + tabelle — layout composito |
| `global-search` | Card per categoria — nessuna tabella unica |
| `message-thread` | Chat — overflow-y fixed height |
| `template-builder` | Builder custom — layout colonne specifico |
| `plate-inventory-manager` | Multi-sezione — card per tipo disco |
| `artisan-runner` / `feature-flag-manager` / `settings-hub` | Multi-card per gruppo / nav tabs |
| `exercise-form` / `exercise-detail` | Multi-card (sezioni dati complessi) |
| `mesocycle-detail` | Card con selettore settimana integrato |
| `expiry-dashboard` | Due sezioni distinte — card separate |
| `availability-manager` | Due card operative — non lista/filtro |
| `opening-hours-manager` | Due card operative con colori semantici |
| `quick-checkin` | Layout due colonne operativo |
| `volume-landmark-manager` | Tabelle multiple per gruppo muscolare |

---

## Note di migrazione BO01

- **Breadcrumb:** rinviato a BO02. Nessuna view implementa `@section('breadcrumb')`.
- **`py-3` vs `py-4`:** lo standard è `py-4` nell'empty state. Le view non pilota con `py-3` saranno corrette nelle release successive.
- **Paginazione always-on:** 5 view usano `card-footer` incondizionale (senza `hasPages()`). Corrette in BO01 solo per member-list via `x-bo.pagination`.
- **Test a rischio alto:** solo `MemberNotesTest` (assert su classe CSS `fa-sticky-note`). Non toccare l'icona senza aggiornare il test.
