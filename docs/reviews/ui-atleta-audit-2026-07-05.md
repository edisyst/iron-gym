# Audit UX/UI — App Atleta
**Data:** 2026-07-05  
**Scope:** PWA atleta (`/athlete/*`) — layout, navigazione, sessione live, stati, ergonomia touch  
**Metodo:** lettura markup Blade, CSS, componenti Livewire; valutazione euristica su 7 dimensioni  
**Nota:** audit read-only; nessuna modifica al codice in questo documento

---

## Inventario schermate

| Route | Componente | View | Scopo atleta |
|---|---|---|---|
| `/athlete` | `Dashboard` | `livewire/athlete/dashboard.blade.php` | Mesociclo attivo + sessioni settimana corrente |
| `/athlete/session/{session}` | `WorkoutSession` | `livewire/athlete/workout-session.blade.php` | Logging live set, readiness pre-sessione, plate calc, sostituzione esercizio |
| `/athlete/session/{session}/recap` | `SessionRecap` | `livewire/athlete/session-recap.blade.php` | Card riepilogo condivisibile post-sessione |
| `/athlete/history` | `TrainingHub` | `livewire/athlete/training-hub.blade.php` | Storico sessioni con drill-down |
| `/athlete/measurements` | `BodyMeasurementForm` | `livewire/athlete/body-measurement-form.blade.php` | Rilevazione misure corporee |
| `/athlete/photos/upload` | `ProgressPhotoUpload` | `livewire/athlete/progress-photo-upload.blade.php` | Caricamento foto progressi |
| `/athlete/exercises` | `ExerciseCatalog` | `livewire/athlete/exercise-catalog.blade.php` | Ricerca catalogo 83 esercizi |
| `/athlete/exercises/{slug}` | `ExerciseDetail` | `livewire/athlete/exercise-detail.blade.php` | Scheda esercizio: muscoli, tecnica, equipment |
| `/athlete/bookings` | `Booking` | `livewire/athlete/booking.blade.php` | Prenotazione PT e corsi |
| `/athlete/volume` | `WeeklyVolume` | `livewire/athlete/weekly-volume.blade.php` | Volume muscolare settimanale: body map SVG + barre MEV/MAV/MRV |
| `/athlete/records` | `PersonalRecords` | `livewire/athlete/personal-records.blade.php` | Lista PR e1RM paginata |
| `/athlete/messages` | `Messages` | `livewire/athlete/messages.blade.php` | Thread messaggi trainer↔atleta |
| `/athlete/profile` | `Profile` | `livewire/athlete/profile.blade.php` | Dati personali, abbonamento |

**Layout:** `resources/views/layouts/athlete.blade.php` — layout dedicato, non AdminLTE.  
**Asset caricati:** `resources/css/app.css` + `resources/js/app.js` (Vite), `public/css/athlete.css`.  
**AdminLTE:** non importato nel layout atleta. Alcune classi Bootstrap (`alert`, `table-striped`) filtrano da `app.css` compilato da Vite (che include Bootstrap via AdminLTE nel bundle globale).

---

## Findings prioritizzati

### P0 — Ostacola concretamente il logging in sala

---

**P0-01 · Bottone "Fatto" working set sotto soglia touch**  
**Schermata:** WorkoutSession  
**File:** `resources/views/livewire/athlete/partials/exercise-card.blade.php:286`  
**Problema:** Il bottone primario dell'intera PWA — "Fatto" sui set di lavoro — ha `height:32px`. La soglia WCAG/Apple HIG è 44 px; quella aggressiva ma accettabile in gym app è 48 px. 32 px è troppo piccolo con le dita sudate o con i guanti da allenamento. Stesso problema per i set warmup: `height:30px` (riga 172).  
**Impatto atleta:** tap mancati tra un set e l'altro, interruzione del flusso, frustrazione ripetuta ogni sessione.  
**Intervento proposto:** portare `height` a minimo 44 px (consigliato 48 px), aumentare `font-size` a 14 px. Il bottone occupa `flex:1` nella colonna azione — non ci sono vincoli di spazio che impediscano l'aumento.  
**Afferisce a:** UX02 (sessione live)

---

**P0-02 · Nessun `inputmode` sugli input numerici**  
**Schermata:** WorkoutSession  
**File:** `resources/views/livewire/athlete/partials/exercise-card.blade.php:148,153,223,232,239,247`  
**Problema:** tutti gli input usano `type="number"` senza `inputmode`. Su iOS, `type="number"` senza `inputmode` apre una tastiera con layout non ottimale (include tasto +/- e virgola mobile in alcune versioni). Per reps/RIR serve `inputmode="numeric"` (solo cifre intere); per peso (step=0.5) serve `inputmode="decimal"`. Su alcuni Android con `type="number"` appare la tastiera QWERTY completa. La stessa mancanza vale in `session-feedback-form.blade.php:45` (ore di sonno).  
**Impatto atleta:** due extra-tap per chiudere la tastiera sbagliata e riaprire quella corretta, o errori di input.  
**Intervento proposto:**
- reps, RIR: aggiungere `inputmode="numeric"`
- peso (step=0.5): aggiungere `inputmode="decimal"`
- durata: `inputmode="numeric"`  
**Afferisce a:** UX02

---

**P0-03 · Testo "performance precedente" illeggibile in luce artificiale**  
**Schermata:** WorkoutSession  
**File:** `resources/views/livewire/athlete/partials/exercise-card.blade.php:322`  
**Problema:** la riga `prec: Xkg × Y • RIR Z` — informazione critica per decidere il carico — è renderizzata con `color:#444` su sfondo `#121212`. Contrasto stimato ≈ 1.4:1, ampiamente sotto il minimo AA (4.5:1 per testo piccolo). In una sala pesi con illuminazione mista o diretta, è virtualmente invisibile.  
**Impatto atleta:** l'atleta non legge il benchmark della sessione precedente, deve fare memoria o lasciare il telefono per guardare il notebook.  
**Intervento proposto:** portare a `color:#777` (contrasto ≈ 4.6:1) o `color:#888` (≈ 5.3:1). Considerare `font-size:12px` invece di 11px e rimuovere il padding esterno eccessivo (`padding:3px 28px 5px`).  
**Afferisce a:** UX02

---

**P0-04 · "Salva" post-quickLog è un micro-link da 11px**  
**Schermata:** WorkoutSession  
**File:** `resources/views/livewire/athlete/partials/exercise-card.blade.php:311-314`  
**Problema:** dopo il tap su "Fatto" (quickLog), appare un link testuale "Salva" con `font-size:11px; text-decoration:underline; padding:0` per confermare i valori effettivi digitati. Target touch stimato: ~20×20 px. Questa è la seconda azione più critica del flusso — senza "Salva" i valori actual non vengono registrati nel DB.  
**Impatto atleta:** tap mancati, dati persi, necessità di rifare il set manualmente.  
**Intervento proposto:** convertire in bottone con altezza minima 36 px, label "Salva" + icona matita, bordo visibile, colore `#aaa`. Non deve competere visivamente con "Fatto" ma deve essere unmissable.  
**Afferisce a:** UX02

---

### P1 — Frizione significativa

---

**P1-01 · Colonna "Piano" troppo stretta nella grid esercizi**  
**Schermata:** WorkoutSession  
**File:** `resources/views/livewire/athlete/partials/exercise-card.blade.php:120-129`  
**Problema:** la grid ha colonne `24px 1fr 62px 62px 52px 96px` (con bilanciere). Su un display 390px con 32px padding, la larghezza disponibile è 358px. Le colonne fisse sommano 296px + 20px di gap (5×4px) = 316px. Il `1fr` per la colonna "Piano" vale quindi ~42px — insufficiente per mostrare "10r 80kg RIR2" senza troncamento. Il testo è `font-size:12px; color:#888` e lo spazio è insufficiente; il contenuto trabocca o viene omesso.  
**Impatto atleta:** il piano prescritto (reps/kg/RIR) non è leggibile — l'informazione guida del set è nascosta.  
**Intervento proposto:** riprogettare la riga set su due righe invece di una griglia monorighe: riga superiore con numero set + valori planned, riga inferiore con inputs + azione. Questo rimuove la rigidità della grid e permette elementi più grandi.  
**Afferisce a:** UX02

---

**P1-02 · Readiness check: 16+ tap prima di iniziare la sessione**  
**Schermata:** WorkoutSession  
**File:** `resources/views/livewire/athlete/workout-session.blade.php` (modale readiness)  
**Problema:** il check pre-sessione richiede 4 metriche × 4 pulsanti = 16 tap minimi, più un campo note opzionale e 2 bottoni di submit. Il modal si apre per ogni sessione `planned`. Per atleti che fanno 4 sessioni/settimana questo è un rituale da 64 tap/settimana prima ancora di cominciare.  
**Impatto atleta:** frizione alta al momento di massima motivazione; probabile abitudine di premere "Salta".  
**Intervento proposto:** raggruppare le 4 metriche in una sola schermata con slider orizzontali o segmented control a 4 posizioni; preferibilmente con default precompilato dall'ultima sessione; aggiungere un bottone "Stessa di ieri" per risposta one-tap quando tutto va bene.  
**Afferisce a:** UX03 (stati e transizioni)

---

**P1-03 · Bottoni header esercizio sotto soglia touch**  
**Schermata:** WorkoutSession  
**File:** `resources/views/livewire/athlete/partials/exercise-card.blade.php:38-61`  
**Problema:** i bottoni "Info" e "Sostituisci" hanno `padding:4px 10px; font-size:11px`. Altezza stimata: circa 26-28px. La zona di tocco è troppo piccola, specialmente "Sostituisci" che richiede precisione per non attivare "Info" accidentalmente (i due bottoni sono contigui con `gap:8px`).  
**Impatto atleta:** tap errati, apertura del modale sbagliato.  
**Intervento proposto:** portare a `padding:8px 12px; font-size:12px` (target ≥ 36px); separare visivamente i due bottoni o convertire in icone da 44px senza label testuale.  
**Afferisce a:** UX02

---

**P1-04 · Safe-area iOS non gestita**  
**Schermata:** layout atleta (tutte le schermate su iPhone con notch/Dynamic Island)  
**File:** `public/css/athlete.css:15,67-76`  
**Problema:** `padding-bottom:80px` su `body` e `bottom-nav` posizionata con `bottom:0` — nessun uso di `env(safe-area-inset-bottom)`. Su iPhone con home indicator, la bottom nav si sovrappone parzialmente alla gesture area. Il contenuto scorribile non ha padding per il safe area inferiore quando la bottom nav è nascosta (es. durante sessione in landscape).  
**Impatto atleta:** tap sulla bottom nav possono essere intercettati dal gesture area iOS; ultimo elemento lista non visibile.  
**Intervento proposto:**
```css
body { padding-bottom: calc(80px + env(safe-area-inset-bottom, 0px)); }
.bottom-nav { padding-bottom: max(8px, env(safe-area-inset-bottom)); height: auto; min-height: 72px; }
```
**Afferisce a:** UX04 (navigazione)

---

**P1-05 · Doppia fetch unread badge per ogni page load**  
**Schermata:** layout atleta (tutte le schermate)  
**File:** `resources/views/layouts/athlete.blade.php:84-90, 207-212`  
**Problema:** sia la sidebar desktop che la bottom nav mobile hanno ciascuna un `x-init` che esegue `fetch('/athlete/messages-unread-count')` indipendentemente. Su ogni page load vengono fatte 2 richieste HTTP per lo stesso dato. Su mobile (connessione lenta in palestra), questo rallenta il rendering.  
**Intervento proposto:** usare un Alpine store globale `messageStore` inizializzato una sola volta nel `<head>` o in `x-data` del `<body>`, condiviso tra sidebar e bottom nav.  
**Afferisce a:** UX04

---

**P1-06 · Feedback radio: dual styling PHP+CSS crea deriva visiva**  
**Schermata:** SessionFeedbackForm  
**File:** `resources/views/livewire/athlete/session-feedback-form.blade.php:19-43`  
**Problema:** i radio button hanno stile duplicato: il CSS in `athlete.css:271-288` definisce `.metric-options label` e il selettore `:checked + span`, ma le `<span>` nel Blade hanno anche inline styles PHP `style="background:{{ ... }}"` che ridefiniscono colori in base a confronto PHP. Se il CSS viene aggiornato ma l'inline style no (o viceversa), i due si discordano. Inoltre il target di click è `<label>` (36×36px) — sotto 44px.  
**Impatto atleta:** potenziali glitch visivi; target piccoli.  
**Intervento proposto:** rimuovere gli inline styles PHP dai `<span>` e fare affidamento solo su CSS `:checked + span`; aumentare `label` a 44×44px.  
**Afferisce a:** UX05 (ergonomia)

---

### P2 — Incoerenza visiva

---

**P2-01 · Personal Records usa classi Bootstrap su tema scuro**  
**Schermata:** PersonalRecords  
**File:** `resources/views/livewire/athlete/personal-records.blade.php`  
**Problema:** la view usa `class="table table-striped table-hover table-dark"` e `class="alert alert-info"` — classi Bootstrap/AdminLTE che non appartengono al design system atleta definito in `athlete.css`. Il colore di stripe Bootstrap dark e il background `alert-info` non matchano la palette `#121212`/`#1E1E1E` dell'app.  
**Intervento proposto:** sostituire con card `.athlete-card` e lista custom, coerente con il pattern usato in dashboard e session-recap. Eliminare dipendenza da Bootstrap in questa view.  
**Afferisce a:** UX05

---

**P2-02 · PR toast usa `alert alert-warning` Bootstrap**  
**Schermata:** layout atleta  
**File:** `resources/views/layouts/athlete.blade.php:244`  
**Problema:** il toast "Nuovo PR!" usa `class="alert alert-warning shadow d-flex align-items-center"` — classi Bootstrap con sfondo giallo paglierino su tema scuro. Non segue la palette brand.  
**Intervento proposto:** stile custom con `background:#1E1E1E; border:1px solid #FF6B00; border-radius:12px; padding:12px 16px` — coerente con `.athlete-card`.  
**Afferisce a:** UX05

---

**P2-03 · Inline styles massiccio in exercise-card e feedback form**  
**Schermata:** WorkoutSession, SessionFeedbackForm  
**File:** `partials/exercise-card.blade.php` (intero file), `session-feedback-form.blade.php` (intero file)  
**Problema:** quasi tutto il layout è definito con `style="..."` inline. Nessuna riusabilità, nessuna centralizzazione. Quando cambierà la palette (es. accent color) si dovranno trovare tutti gli occorrences manualmente. Contrasta con `athlete.css` che è ben strutturato con classi semantiche.  
**Intervento proposto:** estrarre le classi comuni in `athlete.css`: `.set-row`, `.set-action-btn`, `.set-plan-text`, `.set-prev-perf`, `.exercise-card-header`. Non richiede refactor completo — si può fare gradualmente per schermata.  
**Afferisce a:** UX05

---

**P2-04 · Dot legenda volume: colori diversi da classi intensity**  
**Schermata:** WeeklyVolume  
**File:** `public/css/athlete.css:401-406` vs `347-357`  
**Problema:** le classi `.body-map-muscle.intensity-N` definiscono fill per i path SVG (es. `intensity-2: #7a6010`), ma i `.wv-dot.intensity-N` usati nella legenda hanno colori diversi (es. `intensity-2: #c9a227`). Un atleta che confronta la legenda con il body map vedrà colori non corrispondenti.  
**Intervento proposto:** unificare i valori colore usando CSS custom properties: `--ig-intensity-2: #c9a227` condiviso tra body map e legenda. (Il fill SVG scuro era probabilmente pensato per l'antialiasing delle path, ma la legenda dovrebbe essere identica o il motivo va documentato.)  
**Afferisce a:** UX05

---

**P2-05 · Manifest PWA incompleto**  
**File:** `public/manifest.json`  
**Problema:** manifest manca di: `scope`, `orientation`, `shortcuts`, icone `maskable` (solo `"any"` implicito). Su Android, senza icona maskable il launcher applica uno sfondo bianco al cerchio — discordante col tema scuro. `scope` non definito implica scope radice ma è meglio esplicitarlo.  
**Intervento proposto:**
```json
{
  "scope": "/athlete",
  "orientation": "portrait",
  "icons": [
    { "src": "/icons/icon-192.png", "sizes": "192x192", "type": "image/png", "purpose": "any" },
    { "src": "/icons/icon-512.png", "sizes": "512x512", "type": "image/png", "purpose": "any" },
    { "src": "/icons/icon-192-maskable.png", "sizes": "192x192", "type": "image/png", "purpose": "maskable" },
    { "src": "/icons/icon-512-maskable.png", "sizes": "512x512", "type": "image/png", "purpose": "maskable" }
  ]
}
```
**Afferisce a:** UX04

---

**P2-06 · Sidebar messages badge: position:static invece di absolute**  
**Schermata:** layout atleta — sidebar desktop  
**File:** `resources/views/layouts/athlete.blade.php:97`  
**Problema:** il badge non-letto nella sidebar desktop usa `style="position:static; margin-left:auto;"` mentre quello nella bottom nav usa `.nav-unread-badge` con `position:absolute; top:-4px; right:-2px`. Comportamento diverso: nella sidebar il badge allunga la riga del link; nella bottom nav fluttua sull'icona. Incoerente visivamente e structuralmente.  
**Intervento proposto:** uniformare — preferibilmente entrambi position:absolute sull'icona.  
**Afferisce a:** UX05

---

### P3 — Rifinitura

---

**P3-01 · Exercise name come `<button>` invisibile come tale**  
**File:** `partials/exercise-card.blade.php:29-32`  
Il nome esercizio è un `<button>` con `background:none; border:none; text-decoration:underline dotted`. Funzionalmente corretto, ma non riconoscibile come interattivo per chi non conosce la convenzione dotted-underline. Gli utenti potrebbero non scoprire che tap → storico sessioni.  
**Intervento proposto:** aggiungere un'icona clock 12×12px accanto al nome (come suggerimento visivo) o usare un'icona button esplicita.  
**Afferisce a:** UX02

---

**P3-02 · `app-main` non ha `padding-top` su desktop**  
**File:** `public/css/athlete.css:322-325`  
`margin-top:48px` commentato come workaround. Funziona ma è fragile se la topbar cambia altezza.  
**Intervento proposto:** usare `padding-top: 48px` sul `body` anche su desktop o una variabile CSS `--topbar-h: 48px`.  
**Afferisce a:** UX04

---

**P3-03 · `btn-ghost` manca di hover/focus state esplicito**  
**File:** `public/css/athlete.css:229-236`  
`.btn-ghost` non definisce `:hover` né `:focus-visible` — in ambienti touch non si vede feedback al tap.  
**Afferisce a:** UX05

---

**P3-04 · Manca empty state nella pagina Misurazioni**  
Schermata `BodyMeasurementForm`: non verificata in dettaglio nell'audit — da verificare in UX03.

---

## Quick wins (< 1 ora ciascuno, rischio zero)

| # | Intervento | File | Stima |
|---|---|---|---|
| QW-01 | Aggiungere `inputmode="numeric"` su reps/RIR e `inputmode="decimal"` su peso | `exercise-card.blade.php` | 5 min |
| QW-02 | Portare `height` bottone "Fatto" working set da 32px a 48px; warmup da 30px a 44px | `exercise-card.blade.php` | 5 min |
| QW-03 | Portare `color` riga "prec:" da `#444` a `#888` | `exercise-card.blade.php` | 2 min |
| QW-04 | Aggiungere `env(safe-area-inset-bottom)` a body e bottom-nav | `athlete.css` | 10 min |
| QW-05 | Unificare fetch unread in Alpine store globale | `athlete.blade.php` | 20 min |
| QW-06 | Sostituire `alert alert-warning` PR toast con stile brand | `athlete.blade.php` | 10 min |
| QW-07 | Aggiungere `scope`, `orientation`, flag `maskable` a manifest | `manifest.json` | 5 min |
| QW-08 | Fix colori dot legenda volume per matchare intensity palette | `athlete.css` | 5 min |
| QW-09 | Aggiungere `:hover` e `:focus-visible` a `.btn-ghost` | `athlete.css` | 5 min |

---

## Ordine di esecuzione raccomandato per UX02/UX03/UX04

**1. UX02 — Sessione live (prima)**  
È la schermata usata ogni giorno da ogni atleta. I P0 qui (bottone 32px, no inputmode, testo precedente illeggibile, "Salva" invisibile) bloccano concretamente il workflow. ROI massimo. Iniziare dai quick wins QW-01/02/03 immediatamente, poi riprogettare la riga set (P1-01) come lavoro più esteso.

**2. UX04 — Navigazione (seconda)**  
Safe-area iOS (P1-04) e unread badge duplicato (P1-05) sono fix veloci con impatto immediato su iPhone, il dispositivo primario in palestra. Il manifest (P2-05) abilita l'installazione corretta come PWA.

**3. UX03 — Stati e transizioni (terza)**  
Il readiness check (P1-02) è frizione rilevante ma non blocca il logging. Richiede una riprogettazione UX più ragionata (default precompilati, one-tap "tutto ok") — meglio affrontarla dopo aver stabilizzato la sessione live.

**Nota:** UX05 (ergonomia e coerenza visiva — inline styles, Bootstrap leakage, palette volume) può procedere in parallelo con UX03 perché non tocca la schermata sessione.
