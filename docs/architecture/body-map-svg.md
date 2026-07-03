# Body map SVG — mappatura slug muscolo → path

**File:** `resources/views/livewire/athlete/partials/body-map.blade.php`

La body map è un SVG inline stilizzato (non anatomico) con due viste affiancate (fronte a sinistra, retro a destra) in un viewBox `0 0 400 520`. Ogni muscolo è uno o più `<path>` con attributo `data-muscle="{slug}"`.

## Mappa slug → path SVG

| Slug DB | Vista | Note rappresentazione |
|---|---|---|
| `pectoralis_major_sternal` | Fronte | Due metà simmetriche; area sotto clavicolare |
| `pectoralis_major_clavicular` | Fronte | Fascia alta petto vicino alla clavicola |
| `deltoid_anterior` | Fronte | Spalla anteriore bilaterale |
| `deltoid_lateral` | Fronte + Retro | Fianco spalla; appare in entrambe le viste |
| `deltoid_posterior` | Retro | Spalla posteriore bilaterale |
| `biceps_brachii` | Fronte | Braccio anteriore bilaterale |
| `brachialis` | Fronte | Sotto il bicipite, braccio prossimale |
| `brachioradialis` | Fronte + Retro | Avambraccio prossimale fronte; retro avambraccio |
| `forearm_flexors` | Fronte | Avambraccio distale fronte bilaterale |
| `triceps_brachii` | Retro | Braccio posteriore bilaterale |
| `trapezius_upper` | Fronte + Retro | Colmo spalle, visibile in entrambe le viste |
| `trapezius_middle` | Retro | Area trapezio medio bilaterale |
| `trapezius_lower` | Retro | Piccola fascia sotto trapezio medio; aggregato visivamente su `trapezius_middle` |
| `rhomboids` | Retro | Rettangolo tra le due aree trapezio medio (zona interscapolare) |
| `latissimus_dorsi` | Retro | Gran dorsale bilaterale (fascia larga) |
| `erector_spinae` | Retro | Colonna lombare/toracica centrale |
| `rectus_abdominis` | Fronte | Due colonne verticali centrali (addome) |
| `obliques` | Fronte | Fianchi addominali bilaterali |
| `gluteus_maximus` | Retro | Gluteo bilaterale |
| `gluteus_medius` | Retro | Fianco sopra gluteo massimo bilaterale |
| `quadriceps` | Fronte | Coscia anteriore bilaterale (ampia) |
| `hamstrings` | Retro | Coscia posteriore bilaterale |
| `adductors` | Fronte | Interno coscia bilaterale (striscia mediale) |
| `gastrocnemius` | Retro | Polpaccio bilaterale (porzione laterale) |
| `soleus` | Retro | Polpaccio mediale; aggregato visivamente su gastrocnemio, path distinto |
| `transverse_abdominis` | — | **Non rappresentato** (muscolo profondo non visibile superficialmente) |

## Muscoli senza path autonomo

- `transverse_abdominis`: muscolo profondo; nessun path nel SVG. Eventuali dati di volume compaiono solo nelle barre, non nella body map.

## Colorazione

I path ricevono la classe `intensity-{0..5}` da `$intensityMap` calcolato in `WeeklyVolume::buildIntensityMap()`. Le classi CSS sono in `public/css/athlete.css`.

| Classe | Colore | Significato con landmarks | Significato scala assoluta |
|---|---|---|---|
| `intensity-0` | `#2a2a2a` (grigio) | 0 hard set | 0 hard set |
| `intensity-1` | `#1a3a5c` (blu) | Sotto MEV | 1–2 set |
| `intensity-2` | `#7a6010` (giallo) | Tra MEV e MAV min | 3–4 set |
| `intensity-3` | `#1a6635` (verde) | In MAV | 5–7 set |
| `intensity-4` | `#a05510` (arancio) | Tra MAV max e MRV | 8–10 set |
| `intensity-5` | `#8b2020` (rosso) | Oltre MRV | 11+ set |

La scala assoluta si applica ai muscoli con `status = no_landmark` (nessun landmark DB né default config).

## Interazione Alpine

Tap su un path → `bodyMapAlpine().tap(slug)` → `$dispatch('highlight-muscle', { slug })` → tutti i path con quel `data-muscle` ricevono la classe `body-map-highlighted` (bordo arancio) → scroll alla barra corrispondente (`id="muscle-bar-{slug}"`).

## Manutenzione

Per aggiungere un muscolo alla body map:
1. Disegna il path nel SVG con attributo `data-muscle="{slug_esatto_da_muscles_table}"`.
2. Aggiungi il nome italiano in `WeeklyVolume::muscleName()`.
3. Se il muscolo non ha default in `config/volume_landmarks.php`, la scala assoluta viene usata automaticamente.
4. Nessuna migration necessaria (la body map legge dati già esistenti).
