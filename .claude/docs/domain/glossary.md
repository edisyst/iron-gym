# iron-gym · Glossario di dominio

> **Stato:** v0.1
> **Fonte:** estratto e consolidato da `step-0-discovery.md` (sezioni 2 e 4) e `exercises-catalog.md`.
> **Scopo:** riferimento rapido condiviso tra dev, trainer e gestore. Quando un termine compare nel codice, nelle UI o nelle altre doc, la definizione canonica è questa. Se una definizione qui diverge da `step-0-discovery.md`, vince lo step-0 (questo file ne è una vista, non la fonte di verità dello schema).

---

## 1. Terminologia bodybuilding e programmazione

| Termine | Definizione operativa nel sistema |
|---|---|
| **Set** | Una singola serie di ripetizioni eseguite senza interruzione significativa. È l'unità atomica di lavoro (tabella `exercise_sets`). |
| **Rep** | Ripetizione, ciclo completo di un movimento. |
| **Working set** | Set che conta ai fini del volume di allenamento. |
| **Warm-up set** | Set di riscaldamento (`is_warmup = 1`), non conta per il volume settimanale né per i landmarks. |
| **1RM** | One Repetition Maximum, carico massimo sollevabile per una ripetizione singola. |
| **e1RM** | Estimated 1RM, calcolato dal sistema da set submassimali. Formula primaria: **Epley** `1RM = w * (1 + r/30)`. Alternative selezionabili: **Brzycki** e **Lombardi**. |
| **RPE** | Rate of Perceived Exertion, scala 1-10 dello sforzo percepito. Il trainer prescrive un target, l'atleta lo riporta. |
| **RIR** | Reps in Reserve, ripetizioni residue prima del cedimento. Conversione approssimata: `RIR = 10 - RPE`. Nel modello dati teniamo entrambi i campi, uno è derivabile dall'altro. |
| **Volume** | Due accezioni: **tonnellaggio** (`sets x reps x weight`, kg sollevati) e **hard sets per muscolo per settimana** (set effettivi vicini al cedimento). Il secondo è quello usato per programmare. |
| **Hard set** | Set allenante vicino al cedimento (entro ~3 RIR). È l'unità di conteggio del volume settimanale per muscolo, pesata per `contribution_pct`. |
| **MEV** | Minimum Effective Volume. Soglia minima di hard set settimanali per muscolo sotto la quale non c'è stimolo ipertrofico significativo. |
| **MAV** | Maximum Adaptive Volume. Range di volume in cui si ottengono i migliori adattamenti. |
| **MRV** | Maximum Recoverable Volume. Soglia massima oltre la quale si entra in over-reaching e si compromette il recupero. |
| **Mesocycle** | Ciclo di allenamento di 4-6 settimane con progressione coerente. Unità massima di programmazione nel sistema (tabella `mesocycles`). |
| **Microcycle** | Singola settimana del mesociclo (tabella `microcycle_weeks`). |
| **Session** | Singola sessione di allenamento (giorno), appartiene a un microciclo (tabella `sessions`). |
| **Deload** | Settimana di scarico programmata, tipicamente l'ultima del mesociclo: volume ridotto del ~50% e intensità del ~10% (`is_deload = 1`). |
| **Autoregulation** | Aggiustamento dei parametri prescritti sulla base del feedback dell'atleta (fatica, dolori articolari, performance). |
| **Tempo** | Cadenza del movimento espressa come `eccentrica-pausa_bassa-concentrica-pausa_alta` (es. `3-1-1-0`). Campo `tempo VARCHAR(7)`. |
| **Periodization model** | Schema di progressione del mesociclo: `linear`, `undulating_dup` (Daily Undulating Periodization), `block`. |

## 2. Tecniche speciali di serie

| Tecnica | `technique_type` | Definizione |
|---|---|---|
| **Straight set** | `straight` | Serie standard indipendenti. |
| **Superset** | (gruppo) | 2 esercizi eseguiti in sequenza senza recupero. Modellato via `session_exercise_groups.group_type = superset`. |
| **Giant set** | (gruppo) | 3+ esercizi in sequenza senza recupero. `group_type = giant_set`. |
| **Drop set** | `drop_set` | Set portato a cedimento seguito da scarico di peso e immediata continuazione, ripetibile. Sub-set in sequenza con peso decrescente. |
| **Rest-pause** | `rest_pause` | Set principale seguito da brevi pause (10-20s) e ripartenze fino a esaurimento residuo. |
| **Myo-reps** | `myo_reps` | Variante di rest-pause con set di attivazione + cluster di mini-set ad alta intensità neurale. |
| **Cluster set** | `cluster` | Set frammentato in mini-blocchi (es. 6 reps = 2+2+2 con 15s di pausa). Campo `intra_cluster_rest_sec`. |
| **Pre-exhaustion** | `pre_exhaustion` | Tecnica di ordinamento (isolamento prima del composto). Solo flag e ordine, nessuna struttura speciale. |
| **21s** | `twenty_ones` | Set unico: 7 reps mezza escursione bassa + 7 mezza alta + 7 full ROM. Tre sub-set con `set_subtype`. |
| **EMOM** | `emom` | Every Minute On the Minute. Struttura time-based, `duration_sec` per minuto. |
| **AMRAP** | `amrap` | As Many Reps As Possible. Struttura time-based, 1 set con `duration_sec` e reps totali. |

## 3. Personas

| Persona | Accesso | Ruolo |
|---|---|---|
| **Atleta** | PWA | Tesserato che si allena. Vede solo le sue schede, esegue il workout loggando peso/reps/RIR, registra feedback post-sessione, consulta storico e grafici. |
| **Trainer** | Backoffice | Crea/modifica template, costruisce mesocicli, li assegna agli atleti, monitora feedback, applica autoregolazione. Template gym-wide visibili a tutti i trainer. |
| **Gestore** | Backoffice | Proprietario/direttore tecnico. Privilegi del trainer + KPI, dati finanziari, gestione staff e listini. |
| **Receptionist** | Backoffice | Front-desk. Check-in, anagrafica tesserati, certificati medici, abbonamenti e ingressi. Dominio training in sola lettura. |

---

## 4. Tassonomia degli esercizi

Ogni esercizio è classificato lungo assi indipendenti. Dettaglio in `step-0-discovery.md` sezione 4.

| Asse | Tipo | Valori |
|---|---|---|
| **Movement pattern** | lookup `movement_patterns` (FK `compound_pattern_id` / `joint_action_id`) | vedi 4.1 e 4.2 |
| **Mechanic** | ENUM | `compound`, `isolation` |
| **Equipment** | lookup `equipment` (N-M) | `barbell`, `dumbbell`, `cable`, `machine`, `smith_machine`, `bodyweight`, `kettlebell`, `band`, `plate_loaded`, `bench`, `pull_up_bar`, `dip_bar`, `hyperextension`, `ab_wheel` (14) |
| **Plane of motion** | ENUM | `sagittal`, `frontal`, `transverse`, `multiplanar` |
| **Laterality** | ENUM | `bilateral`, `unilateral_alternating`, `unilateral_isolated` |
| **Skill level** | ENUM | `beginner`, `intermediate`, `advanced` |
| **Measurement type** | ENUM | `reps_weight`, `reps_only`, `time`, `time_weight`, `distance`, `isometric_hold` |

Un esercizio è classificato lungo **uno solo** dei due assi di pattern: `compound_pattern` **oppure** `joint_action` (mai entrambi, mai nessuno — vincolo CHECK XOR a livello DB). Regola pratica: compound puri → `compound_pattern`; isolation delle estremità → `joint_action`; pattern del core (`rotation`/`anti_rotation`) → `compound_pattern` anche se isolation; multi-articolari dominati da una singola azione (es. hanging leg raise) → `joint_action` anche se compound.

### 4.1 Movement patterns — `compound_pattern` (12)

Pattern motori "globali" che descrivono un movimento di tutta la catena cinetica.

| slug | name_it |
|---|---|
| `squat` | Squat |
| `hinge` | Hinge (cerniera dell'anca) |
| `lunge` | Affondo |
| `horizontal_push` | Spinta orizzontale |
| `vertical_push` | Spinta verticale |
| `horizontal_pull` | Trazione orizzontale |
| `vertical_pull` | Trazione verticale |
| `carry` | Trasporto |
| `rotation` | Rotazione |
| `anti_rotation` | Anti-rotazione |
| `plyometric` | Pliometrico |
| `locomotion` | Locomozione |

### 4.2 Movement patterns — `joint_action` (15)

Pattern definiti a livello di singola articolazione e direzione del movimento. Classificano con precisione gli esercizi di isolamento.

| slug | name_it |
|---|---|
| `shoulder_abduction` | Abduzione di spalla |
| `shoulder_horizontal_abduction` | Abduzione orizzontale di spalla |
| `shoulder_horizontal_adduction` | Adduzione orizzontale di spalla |
| `shoulder_extension` | Estensione di spalla |
| `elbow_flexion` | Flessione di gomito |
| `elbow_extension` | Estensione di gomito |
| `scapular_elevation` | Elevazione scapolare |
| `ankle_plantarflexion` | Flessione plantare di caviglia |
| `spinal_flexion` | Flessione del rachide |
| `hip_flexion` | Flessione d'anca |
| `hip_extension` | Estensione d'anca |
| `hip_abduction` | Abduzione d'anca |
| `hip_adduction` | Adduzione d'anca |
| `knee_extension` | Estensione di ginocchio |
| `knee_flexion` | Flessione di ginocchio |

Totale: 27 pattern. La lookup è progettata per crescere: aggiungere `wrist_flexion`, `cervical_rotation`, ecc. è una semplice INSERT, senza migration.

### 4.3 Coinvolgimento muscolare

Relazione N-M (`exercise_muscle`). Ogni associazione esercizio-muscolo ha un `role` (`primary`, `secondary`, `stabilizer`) e un `contribution_pct` (0-100) che attribuisce il volume al muscolo nel calcolo settimanale. Criteri: somma dei `primary` tra 60% e 100%; `stabilizer` sempre ≤ 10% (lavoro isometrico, escluso dal volume ipertrofico); isolation puro = un solo `primary` al 100%. Esempio: una panca piana contribuisce ~60% al gran pettorale sternale, ~20% al tricipite, ~15% al deltoide anteriore.

I 26 muscoli seed coprono: gran pettorale (clavicolare/sternale), deltoide (anteriore/laterale/posteriore), tricipite, bicipite, brachiale, brachioradiale, avambraccio, gran dorsale, trapezio (superiore/medio/inferiore), romboidi, erettori spinali, quadricipite, ischiocrurali, gluteo (massimo/medio), adduttori, polpacci (gastrocnemio/soleo), addome (retto/obliqui/trasverso). La granularità maggiore (es. capo lungo vs breve) è opzionale via `muscle_head`.

---

## 5. Convenzioni di naming ricorrenti

| Convenzione | Regola |
|---|---|
| **slug** | Identificatore stabile in `snake_case` inglese, unico per tabella. Usato nei seed al posto degli id auto-increment. |
| **name_it** | Etichetta human-readable in italiano, mostrata nelle UI. |
| **`_id`** | Suffisso per ogni foreign key. |
| **`planned_*` / `actual_*`** | Prefissi per distinguere prescrizione (cosa ha programmato il trainer) ed esecuzione (cosa ha fatto l'atleta) sullo stesso record `exercise_sets`. |
| **`is_*`** | Prefisso per flag booleani (`is_warmup`, `is_deload`, `is_active`). |
