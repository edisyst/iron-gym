# iron-gym · Catalogo esercizi iniziale

> **Stato:** draft v0.3
> **Riferimento dominio:** vedi `step-0-discovery.md` (v0.3)
> **Scope:** catalogo seed di 83 esercizi BB-oriented da caricare al boot del sistema. Include classificazione `compound_pattern` / `joint_action` (mutuamente esclusivi via CHECK XOR a livello DB) e `contribution_pct` per `exercise_muscle`.

## Changelog

**v0.3** — Allineamento allo schema `exercises` v0.3 di step-0: la singola colonna `movement_pattern` (ENUM) è sostituita da due FK nullable `compound_pattern_id` e `joint_action_id` verso la lookup `movement_patterns`, con vincolo CHECK XOR (esattamente una delle due valorizzata). Cinque esercizi sono stati riclassificati con joint_action più precise:
- `cable_chest_fly` e `pec_deck_machine`: da `horizontal_push` (isolation) a `shoulder_horizontal_adduction`
- `cable_pullover` e `straight_arm_pulldown`: da `vertical_pull` (isolation) a `shoulder_extension`
- `hyperextension_45`: da `hinge` (isolation) a `hip_extension`

Il pattern generico `isolation` è stato eliminato dal vocabolario perché ridondante con `mechanic=isolation`. Il seed SQL ha ora un pre-step di popolamento della tabella `movement_patterns` (27 pattern: 12 compound + 15 joint action) e gli INSERT degli esercizi usano JOIN su slug per ricavare gli id di pattern.

**v0.2** — Tassonomia degli isolation rivista e 25 nuovi esercizi (catalogo da 58 a 83).

**v0.1** — Versione iniziale, 58 esercizi base.

---

## Note metodologiche

I `contribution_pct` per il pivot esercizio-muscolo sono basati su EMG studies e letteratura standard del bodybuilding scientifico, con i seguenti criteri operativi: la somma dei contribution_pct dei muscoli `primary` di un esercizio si attesta tipicamente tra 60% e 100%, con il resto distribuito tra `secondary` e `stabilizer`; i muscoli `stabilizer` hanno sempre contribution_pct ≤ 10% perché lavorano in isometria e non vanno conteggiati nel volume settimanale ipertrofico; esercizi di pura isolazione hanno un solo muscolo primary al 100% e zero secondary; per esercizi unilaterali le percentuali restano per lato, il sistema conta un set come uno hard set per quel muscolo.

Sulla classificazione del pattern motorio applico due regole pragmatiche. Per esercizi compound puri (squat, panca, deadlift, OHP, trazioni, ecc.) valorizzo `compound_pattern` e lascio `joint_action` NULL. Per esercizi isolation delle estremità (curl, leg extension, calf raise, alzate laterali, ecc.) valorizzo `joint_action` e lascio `compound_pattern` NULL. Casi ibridi: per pattern del core (`rotation`, `anti_rotation`) uso `compound_pattern` anche se `mechanic=isolation`, perché sono pattern di stabilizzazione macro non riconducibili a una singola joint action; per esercizi multi-articolari dominati da una specifica azione articolare (es. `hanging_leg_raise`, dominato dalla flessione d'anca) uso `joint_action` anche se `mechanic=compound`. Il CHECK XOR a livello DB garantisce che esattamente una delle due colonne sia sempre valorizzata.

Nelle schede sotto uso il prefisso `cp:` per indicare il valore di `compound_pattern` e `ja:` per `joint_action`, così è subito chiaro quale dei due assi tassonomici classifica l'esercizio. I muscoli sono identificati nel seed tramite il loro `slug` (più stabile dell'id numerico).

## Anagrafica muscoli (lookup di base)

| slug | name_it | muscle_group | muscle_head |
|---|---|---|---|
| `pectoralis_major_clavicular` | Gran pettorale (clavicolare) | chest | clavicular |
| `pectoralis_major_sternal` | Gran pettorale (sternale) | chest | sternal |
| `deltoid_anterior` | Deltoide anteriore | shoulders | anterior |
| `deltoid_lateral` | Deltoide laterale | shoulders | lateral |
| `deltoid_posterior` | Deltoide posteriore | shoulders | posterior |
| `triceps_brachii` | Tricipite brachiale | arms | — |
| `biceps_brachii` | Bicipite brachiale | arms | — |
| `brachialis` | Brachiale | arms | — |
| `brachioradialis` | Brachioradiale | arms | — |
| `forearm_flexors` | Flessori dell'avambraccio | arms | — |
| `latissimus_dorsi` | Gran dorsale | back | — |
| `trapezius_upper` | Trapezio superiore | back | upper |
| `trapezius_middle` | Trapezio medio | back | middle |
| `trapezius_lower` | Trapezio inferiore | back | lower |
| `rhomboids` | Romboidi | back | — |
| `erector_spinae` | Erettori spinali | back | — |
| `quadriceps` | Quadricipite | legs | — |
| `hamstrings` | Ischiocrurali | legs | — |
| `gluteus_maximus` | Grande gluteo | legs | — |
| `gluteus_medius` | Medio gluteo | legs | — |
| `adductors` | Adduttori | legs | — |
| `gastrocnemius` | Gastrocnemio | legs | — |
| `soleus` | Soleo | legs | — |
| `rectus_abdominis` | Retto dell'addome | core | — |
| `obliques` | Obliqui | core | — |
| `transverse_abdominis` | Trasverso dell'addome | core | — |

## Anagrafica equipment (lookup di base)

| slug | name_it |
|---|---|
| `barbell` | Bilanciere |
| `dumbbell` | Manubrio |
| `cable` | Cavi |
| `machine` | Macchina |
| `smith_machine` | Smith machine |
| `bodyweight` | Corpo libero |
| `kettlebell` | Kettlebell |
| `band` | Elastico |
| `plate_loaded` | Macchina a piastre |
| `bench` | Panca |
| `pull_up_bar` | Sbarra |
| `dip_bar` | Parallele |
| `hyperextension` | Panca hyperextension |
| `ab_wheel` | Ruota addominale |

## Anagrafica movement_patterns (lookup di base)

Classificazione completa: vedi sezione 4 dello step-0-discovery.md. Riassunto: 12 compound + 15 joint action = 27 pattern.

| slug | name_it | category |
|---|---|---|
| `squat` | Squat | compound_pattern |
| `hinge` | Hinge (cerniera dell'anca) | compound_pattern |
| `lunge` | Affondo | compound_pattern |
| `horizontal_push` | Spinta orizzontale | compound_pattern |
| `vertical_push` | Spinta verticale | compound_pattern |
| `horizontal_pull` | Trazione orizzontale | compound_pattern |
| `vertical_pull` | Trazione verticale | compound_pattern |
| `carry` | Trasporto | compound_pattern |
| `rotation` | Rotazione | compound_pattern |
| `anti_rotation` | Anti-rotazione | compound_pattern |
| `plyometric` | Pliometrico | compound_pattern |
| `locomotion` | Locomozione | compound_pattern |
| `shoulder_abduction` | Abduzione di spalla | joint_action |
| `shoulder_horizontal_abduction` | Abduzione orizzontale di spalla | joint_action |
| `shoulder_horizontal_adduction` | Adduzione orizzontale di spalla | joint_action |
| `shoulder_extension` | Estensione di spalla | joint_action |
| `elbow_flexion` | Flessione di gomito | joint_action |
| `elbow_extension` | Estensione di gomito | joint_action |
| `scapular_elevation` | Elevazione scapolare | joint_action |
| `ankle_plantarflexion` | Flessione plantare di caviglia | joint_action |
| `spinal_flexion` | Flessione del rachide | joint_action |
| `hip_flexion` | Flessione d'anca | joint_action |
| `hip_extension` | Estensione d'anca | joint_action |
| `hip_abduction` | Abduzione d'anca | joint_action |
| `hip_adduction` | Adduzione d'anca | joint_action |
| `knee_extension` | Estensione di ginocchio | joint_action |
| `knee_flexion` | Flessione di ginocchio | joint_action |

---

## Catalogo per gruppo muscolare

### Petto (11 esercizi)

**Panca piana con bilanciere** · `barbell_bench_press` · *cp:* horizontal_push · compound · sagittal · bilateral · intermediate · reps_weight · equipment: barbell, bench

| Muscolo | Ruolo | % |
|---|---|---|
| pectoralis_major_sternal | primary | 60 |
| pectoralis_major_clavicular | primary | 15 |
| triceps_brachii | secondary | 20 |
| deltoid_anterior | secondary | 15 |

**Panca piana con manubri** · `dumbbell_bench_press` · *cp:* horizontal_push · compound · sagittal · bilateral · intermediate · reps_weight · equipment: dumbbell, bench

| Muscolo | Ruolo | % |
|---|---|---|
| pectoralis_major_sternal | primary | 65 |
| pectoralis_major_clavicular | primary | 15 |
| triceps_brachii | secondary | 15 |
| deltoid_anterior | secondary | 15 |

**Panca inclinata con bilanciere** · `incline_barbell_bench_press` · *cp:* horizontal_push · compound · sagittal · bilateral · intermediate · reps_weight · equipment: barbell, bench

| Muscolo | Ruolo | % |
|---|---|---|
| pectoralis_major_clavicular | primary | 60 |
| pectoralis_major_sternal | secondary | 20 |
| deltoid_anterior | secondary | 25 |
| triceps_brachii | secondary | 15 |

**Panca inclinata con manubri** · `incline_dumbbell_bench_press` · *cp:* horizontal_push · compound · sagittal · bilateral · intermediate · reps_weight · equipment: dumbbell, bench

| Muscolo | Ruolo | % |
|---|---|---|
| pectoralis_major_clavicular | primary | 60 |
| pectoralis_major_sternal | secondary | 20 |
| deltoid_anterior | secondary | 25 |
| triceps_brachii | secondary | 15 |

**Panca declinata con bilanciere** · `decline_barbell_bench_press` · *cp:* horizontal_push · compound · sagittal · bilateral · intermediate · reps_weight · equipment: barbell, bench

| Muscolo | Ruolo | % |
|---|---|---|
| pectoralis_major_sternal | primary | 75 |
| triceps_brachii | secondary | 20 |
| deltoid_anterior | secondary | 10 |

**Croci ai cavi** · `cable_chest_fly` · *ja:* shoulder_horizontal_adduction · isolation · transverse · bilateral · intermediate · reps_weight · equipment: cable

| Muscolo | Ruolo | % |
|---|---|---|
| pectoralis_major_sternal | primary | 80 |
| pectoralis_major_clavicular | primary | 15 |
| deltoid_anterior | stabilizer | 5 |

**Pectoral machine (peck deck)** · `pec_deck_machine` · *ja:* shoulder_horizontal_adduction · isolation · transverse · bilateral · beginner · reps_weight · equipment: machine

| Muscolo | Ruolo | % |
|---|---|---|
| pectoralis_major_sternal | primary | 85 |
| pectoralis_major_clavicular | primary | 10 |
| deltoid_anterior | stabilizer | 5 |

**Dip alle parallele per pettorali** · `chest_dips` · *cp:* vertical_push · compound · sagittal · bilateral · advanced · reps_weight · equipment: dip_bar, bodyweight

| Muscolo | Ruolo | % |
|---|---|---|
| pectoralis_major_sternal | primary | 55 |
| triceps_brachii | primary | 30 |
| deltoid_anterior | secondary | 15 |

**Chest press alla macchina** · `machine_chest_press` · *cp:* horizontal_push · compound · sagittal · bilateral · beginner · reps_weight · equipment: machine

| Muscolo | Ruolo | % |
|---|---|---|
| pectoralis_major_sternal | primary | 70 |
| pectoralis_major_clavicular | primary | 15 |
| triceps_brachii | secondary | 15 |
| deltoid_anterior | secondary | 10 |

**Spinte inclinate allo Smith** · `smith_incline_press` · *cp:* horizontal_push · compound · sagittal · bilateral · intermediate · reps_weight · equipment: smith_machine, bench

| Muscolo | Ruolo | % |
|---|---|---|
| pectoralis_major_clavicular | primary | 60 |
| pectoralis_major_sternal | secondary | 20 |
| deltoid_anterior | secondary | 25 |
| triceps_brachii | secondary | 15 |

**Piegamenti sulle braccia** · `push_up` · *cp:* horizontal_push · compound · sagittal · bilateral · beginner · reps_only · equipment: bodyweight

| Muscolo | Ruolo | % |
|---|---|---|
| pectoralis_major_sternal | primary | 50 |
| pectoralis_major_clavicular | primary | 15 |
| triceps_brachii | secondary | 20 |
| deltoid_anterior | secondary | 15 |
| transverse_abdominis | stabilizer | 5 |

---

### Schiena (14 esercizi)

**Stacco da terra convenzionale** · `conventional_deadlift` · *cp:* hinge · compound · sagittal · bilateral · advanced · reps_weight · equipment: barbell

| Muscolo | Ruolo | % |
|---|---|---|
| hamstrings | primary | 30 |
| gluteus_maximus | primary | 25 |
| erector_spinae | primary | 25 |
| latissimus_dorsi | secondary | 10 |
| trapezius_middle | secondary | 5 |
| forearm_flexors | stabilizer | 5 |

**Stacco rumeno (RDL)** · `romanian_deadlift` · *cp:* hinge · compound · sagittal · bilateral · intermediate · reps_weight · equipment: barbell

| Muscolo | Ruolo | % |
|---|---|---|
| hamstrings | primary | 55 |
| gluteus_maximus | primary | 30 |
| erector_spinae | secondary | 15 |

**Trazioni alla sbarra prone** · `pull_up_pronated` · *cp:* vertical_pull · compound · sagittal · bilateral · advanced · reps_weight · equipment: pull_up_bar, bodyweight

| Muscolo | Ruolo | % |
|---|---|---|
| latissimus_dorsi | primary | 65 |
| biceps_brachii | secondary | 15 |
| trapezius_middle | secondary | 10 |
| rhomboids | secondary | 10 |
| brachialis | secondary | 5 |

**Trazioni supinate (chin-up)** · `chin_up_supinated` · *cp:* vertical_pull · compound · sagittal · bilateral · intermediate · reps_weight · equipment: pull_up_bar, bodyweight

| Muscolo | Ruolo | % |
|---|---|---|
| latissimus_dorsi | primary | 55 |
| biceps_brachii | primary | 25 |
| brachialis | secondary | 10 |
| trapezius_middle | secondary | 10 |

**Lat machine avanti** · `lat_pulldown_front` · *cp:* vertical_pull · compound · sagittal · bilateral · beginner · reps_weight · equipment: cable

| Muscolo | Ruolo | % |
|---|---|---|
| latissimus_dorsi | primary | 65 |
| biceps_brachii | secondary | 15 |
| trapezius_middle | secondary | 10 |
| rhomboids | secondary | 10 |

**Pulley basso (seated cable row)** · `seated_cable_row` · *cp:* horizontal_pull · compound · sagittal · bilateral · beginner · reps_weight · equipment: cable

| Muscolo | Ruolo | % |
|---|---|---|
| latissimus_dorsi | primary | 35 |
| trapezius_middle | primary | 25 |
| rhomboids | primary | 25 |
| biceps_brachii | secondary | 15 |

**Rematore con bilanciere** · `barbell_row` · *cp:* horizontal_pull · compound · sagittal · bilateral · intermediate · reps_weight · equipment: barbell

| Muscolo | Ruolo | % |
|---|---|---|
| latissimus_dorsi | primary | 40 |
| trapezius_middle | primary | 25 |
| rhomboids | primary | 20 |
| biceps_brachii | secondary | 15 |
| erector_spinae | stabilizer | 10 |

**Rematore con manubrio (one-arm)** · `one_arm_dumbbell_row` · *cp:* horizontal_pull · compound · sagittal · unilateral_isolated · intermediate · reps_weight · equipment: dumbbell, bench

| Muscolo | Ruolo | % |
|---|---|---|
| latissimus_dorsi | primary | 50 |
| trapezius_middle | primary | 20 |
| rhomboids | secondary | 15 |
| biceps_brachii | secondary | 15 |

**T-bar row** · `t_bar_row` · *cp:* horizontal_pull · compound · sagittal · bilateral · intermediate · reps_weight · equipment: plate_loaded

| Muscolo | Ruolo | % |
|---|---|---|
| latissimus_dorsi | primary | 40 |
| trapezius_middle | primary | 25 |
| rhomboids | primary | 20 |
| biceps_brachii | secondary | 15 |

**Pullover ai cavi (alto)** · `cable_pullover` · *ja:* shoulder_extension · isolation · sagittal · bilateral · intermediate · reps_weight · equipment: cable

| Muscolo | Ruolo | % |
|---|---|---|
| latissimus_dorsi | primary | 85 |
| trapezius_lower | secondary | 10 |
| triceps_brachii | stabilizer | 5 |

**Rematore con appoggio al petto** · `chest_supported_row` · *cp:* horizontal_pull · compound · sagittal · bilateral · beginner · reps_weight · equipment: dumbbell, bench

| Muscolo | Ruolo | % |
|---|---|---|
| latissimus_dorsi | primary | 40 |
| trapezius_middle | primary | 25 |
| rhomboids | primary | 20 |
| biceps_brachii | secondary | 15 |

**Rematore alla macchina** · `machine_row` · *cp:* horizontal_pull · compound · sagittal · bilateral · beginner · reps_weight · equipment: machine

| Muscolo | Ruolo | % |
|---|---|---|
| latissimus_dorsi | primary | 40 |
| trapezius_middle | primary | 25 |
| rhomboids | primary | 20 |
| biceps_brachii | secondary | 15 |

**Pulldown a braccia tese** · `straight_arm_pulldown` · *ja:* shoulder_extension · isolation · sagittal · bilateral · intermediate · reps_weight · equipment: cable

| Muscolo | Ruolo | % |
|---|---|---|
| latissimus_dorsi | primary | 85 |
| trapezius_lower | secondary | 10 |
| rectus_abdominis | stabilizer | 5 |

**Stacco dai blocchi (rack pull)** · `rack_pull` · *cp:* hinge · compound · sagittal · bilateral · advanced · reps_weight · equipment: barbell

| Muscolo | Ruolo | % |
|---|---|---|
| erector_spinae | primary | 35 |
| gluteus_maximus | primary | 25 |
| hamstrings | primary | 20 |
| trapezius_middle | secondary | 10 |
| latissimus_dorsi | secondary | 10 |
| forearm_flexors | stabilizer | 5 |

---

### Spalle (10 esercizi)

**Military press in piedi (OHP)** · `overhead_press_standing` · *cp:* vertical_push · compound · sagittal · bilateral · advanced · reps_weight · equipment: barbell

| Muscolo | Ruolo | % |
|---|---|---|
| deltoid_anterior | primary | 55 |
| deltoid_lateral | primary | 20 |
| triceps_brachii | secondary | 20 |
| trapezius_upper | stabilizer | 10 |
| erector_spinae | stabilizer | 10 |

**Lento avanti con manubri (seduto)** · `seated_dumbbell_press` · *cp:* vertical_push · compound · sagittal · bilateral · intermediate · reps_weight · equipment: dumbbell, bench

| Muscolo | Ruolo | % |
|---|---|---|
| deltoid_anterior | primary | 50 |
| deltoid_lateral | primary | 25 |
| triceps_brachii | secondary | 20 |
| trapezius_upper | stabilizer | 10 |

**Arnold press** · `arnold_press` · *cp:* vertical_push · compound · multiplanar · bilateral · intermediate · reps_weight · equipment: dumbbell, bench

| Muscolo | Ruolo | % |
|---|---|---|
| deltoid_anterior | primary | 50 |
| deltoid_lateral | primary | 30 |
| triceps_brachii | secondary | 15 |
| trapezius_upper | stabilizer | 10 |

**Alzate laterali con manubri** · `dumbbell_lateral_raise` · *ja:* shoulder_abduction · isolation · frontal · bilateral · beginner · reps_weight · equipment: dumbbell

| Muscolo | Ruolo | % |
|---|---|---|
| deltoid_lateral | primary | 90 |
| deltoid_anterior | secondary | 10 |

**Alzate laterali ai cavi** · `cable_lateral_raise` · *ja:* shoulder_abduction · isolation · frontal · unilateral_isolated · intermediate · reps_weight · equipment: cable

| Muscolo | Ruolo | % |
|---|---|---|
| deltoid_lateral | primary | 95 |
| deltoid_anterior | secondary | 5 |

**Reverse pec deck** · `reverse_pec_deck` · *ja:* shoulder_horizontal_abduction · isolation · transverse · bilateral · beginner · reps_weight · equipment: machine

| Muscolo | Ruolo | % |
|---|---|---|
| deltoid_posterior | primary | 75 |
| rhomboids | secondary | 15 |
| trapezius_middle | secondary | 10 |

**Alzate posteriori a busto in avanti** · `bent_over_rear_delt_raise` · *ja:* shoulder_horizontal_abduction · isolation · transverse · bilateral · intermediate · reps_weight · equipment: dumbbell

| Muscolo | Ruolo | % |
|---|---|---|
| deltoid_posterior | primary | 75 |
| rhomboids | secondary | 15 |
| trapezius_middle | secondary | 10 |

**Tirata al mento (upright row)** · `upright_row` · *cp:* vertical_pull · compound · frontal · bilateral · intermediate · reps_weight · equipment: barbell

| Muscolo | Ruolo | % |
|---|---|---|
| deltoid_lateral | primary | 50 |
| trapezius_upper | primary | 25 |
| deltoid_anterior | secondary | 15 |
| biceps_brachii | secondary | 10 |

**Croci posteriori ai cavi** · `cable_rear_delt_fly` · *ja:* shoulder_horizontal_abduction · isolation · transverse · bilateral · intermediate · reps_weight · equipment: cable

| Muscolo | Ruolo | % |
|---|---|---|
| deltoid_posterior | primary | 75 |
| rhomboids | secondary | 15 |
| trapezius_middle | secondary | 10 |

**Alzate laterali alla macchina** · `machine_lateral_raise` · *ja:* shoulder_abduction · isolation · frontal · bilateral · beginner · reps_weight · equipment: machine

| Muscolo | Ruolo | % |
|---|---|---|
| deltoid_lateral | primary | 90 |
| deltoid_anterior | secondary | 10 |

---

### Bicipiti (8 esercizi)

**Curl con bilanciere** · `barbell_curl` · *ja:* elbow_flexion · isolation · sagittal · bilateral · beginner · reps_weight · equipment: barbell

| Muscolo | Ruolo | % |
|---|---|---|
| biceps_brachii | primary | 80 |
| brachialis | secondary | 15 |
| brachioradialis | secondary | 5 |

**Curl con manubri alternato** · `alternating_dumbbell_curl` · *ja:* elbow_flexion · isolation · sagittal · unilateral_alternating · beginner · reps_weight · equipment: dumbbell

| Muscolo | Ruolo | % |
|---|---|---|
| biceps_brachii | primary | 80 |
| brachialis | secondary | 15 |
| brachioradialis | secondary | 5 |

**Curl alla panca Scott** · `scott_curl` · *ja:* elbow_flexion · isolation · sagittal · bilateral · intermediate · reps_weight · equipment: barbell, bench

| Muscolo | Ruolo | % |
|---|---|---|
| biceps_brachii | primary | 85 |
| brachialis | secondary | 15 |

**Hammer curl** · `hammer_curl` · *ja:* elbow_flexion · isolation · sagittal · unilateral_alternating · beginner · reps_weight · equipment: dumbbell

| Muscolo | Ruolo | % |
|---|---|---|
| brachialis | primary | 50 |
| brachioradialis | primary | 30 |
| biceps_brachii | secondary | 20 |

**Curl ai cavi** · `cable_curl` · *ja:* elbow_flexion · isolation · sagittal · bilateral · beginner · reps_weight · equipment: cable

| Muscolo | Ruolo | % |
|---|---|---|
| biceps_brachii | primary | 85 |
| brachialis | secondary | 10 |
| brachioradialis | secondary | 5 |

**Curl concentrato** · `concentration_curl` · *ja:* elbow_flexion · isolation · sagittal · unilateral_isolated · beginner · reps_weight · equipment: dumbbell, bench

| Muscolo | Ruolo | % |
|---|---|---|
| biceps_brachii | primary | 90 |
| brachialis | secondary | 10 |

**Curl con manubri su panca inclinata** · `incline_dumbbell_curl` · *ja:* elbow_flexion · isolation · sagittal · unilateral_alternating · intermediate · reps_weight · equipment: dumbbell, bench

| Muscolo | Ruolo | % |
|---|---|---|
| biceps_brachii | primary | 85 |
| brachialis | secondary | 10 |
| brachioradialis | secondary | 5 |

**Bayesian curl ai cavi** · `bayesian_curl` · *ja:* elbow_flexion · isolation · sagittal · unilateral_isolated · intermediate · reps_weight · equipment: cable

| Muscolo | Ruolo | % |
|---|---|---|
| biceps_brachii | primary | 90 |
| brachialis | secondary | 10 |

---

### Tricipiti (9 esercizi)

**French press con bilanciere EZ** · `ez_bar_french_press` · *ja:* elbow_extension · isolation · sagittal · bilateral · intermediate · reps_weight · equipment: barbell, bench

| Muscolo | Ruolo | % |
|---|---|---|
| triceps_brachii | primary | 95 |
| deltoid_posterior | stabilizer | 5 |

**Push down ai cavi (sbarra dritta)** · `cable_pushdown_straight` · *ja:* elbow_extension · isolation · sagittal · bilateral · beginner · reps_weight · equipment: cable

| Muscolo | Ruolo | % |
|---|---|---|
| triceps_brachii | primary | 100 |

**Push down ai cavi con corda** · `cable_pushdown_rope` · *ja:* elbow_extension · isolation · sagittal · bilateral · beginner · reps_weight · equipment: cable

| Muscolo | Ruolo | % |
|---|---|---|
| triceps_brachii | primary | 100 |

**Dip alle parallele (tricipiti)** · `triceps_dips` · *cp:* vertical_push · compound · sagittal · bilateral · advanced · reps_weight · equipment: dip_bar, bodyweight

| Muscolo | Ruolo | % |
|---|---|---|
| triceps_brachii | primary | 55 |
| pectoralis_major_sternal | secondary | 30 |
| deltoid_anterior | secondary | 15 |

**Skullcrusher** · `skullcrusher` · *ja:* elbow_extension · isolation · sagittal · bilateral · intermediate · reps_weight · equipment: barbell, bench

| Muscolo | Ruolo | % |
|---|---|---|
| triceps_brachii | primary | 95 |
| deltoid_posterior | stabilizer | 5 |

**Panca stretta** · `close_grip_bench_press` · *cp:* horizontal_push · compound · sagittal · bilateral · intermediate · reps_weight · equipment: barbell, bench

| Muscolo | Ruolo | % |
|---|---|---|
| triceps_brachii | primary | 55 |
| pectoralis_major_sternal | primary | 25 |
| deltoid_anterior | secondary | 20 |

**Estensioni tricipiti sopra la testa ai cavi** · `overhead_cable_extension` · *ja:* elbow_extension · isolation · sagittal · bilateral · intermediate · reps_weight · equipment: cable

| Muscolo | Ruolo | % |
|---|---|---|
| triceps_brachii | primary | 100 |

**Push down ai cavi a un braccio** · `single_arm_cable_pushdown` · *ja:* elbow_extension · isolation · sagittal · unilateral_isolated · beginner · reps_weight · equipment: cable

| Muscolo | Ruolo | % |
|---|---|---|
| triceps_brachii | primary | 100 |

**JM press** · `jm_press` · *cp:* horizontal_push · compound · sagittal · bilateral · advanced · reps_weight · equipment: barbell, bench

| Muscolo | Ruolo | % |
|---|---|---|
| triceps_brachii | primary | 75 |
| pectoralis_major_sternal | secondary | 15 |
| deltoid_anterior | secondary | 10 |

---

### Gambe (18 esercizi)

**Squat con bilanciere (high-bar)** · `back_squat_high_bar` · *cp:* squat · compound · sagittal · bilateral · advanced · reps_weight · equipment: barbell

| Muscolo | Ruolo | % |
|---|---|---|
| quadriceps | primary | 50 |
| gluteus_maximus | primary | 25 |
| hamstrings | secondary | 15 |
| erector_spinae | stabilizer | 10 |
| adductors | secondary | 10 |

**Front squat** · `front_squat` · *cp:* squat · compound · sagittal · bilateral · advanced · reps_weight · equipment: barbell

| Muscolo | Ruolo | % |
|---|---|---|
| quadriceps | primary | 60 |
| gluteus_maximus | primary | 20 |
| hamstrings | secondary | 10 |
| erector_spinae | stabilizer | 10 |

**Hack squat machine** · `hack_squat_machine` · *cp:* squat · compound · sagittal · bilateral · intermediate · reps_weight · equipment: machine

| Muscolo | Ruolo | % |
|---|---|---|
| quadriceps | primary | 65 |
| gluteus_maximus | primary | 20 |
| hamstrings | secondary | 10 |
| adductors | secondary | 5 |

**Leg press 45°** · `leg_press_45` · *cp:* squat · compound · sagittal · bilateral · beginner · reps_weight · equipment: machine

| Muscolo | Ruolo | % |
|---|---|---|
| quadriceps | primary | 55 |
| gluteus_maximus | primary | 25 |
| hamstrings | secondary | 15 |
| adductors | secondary | 5 |

**Affondi con bilanciere** · `barbell_lunge` · *cp:* lunge · compound · sagittal · unilateral_alternating · intermediate · reps_weight · equipment: barbell

| Muscolo | Ruolo | % |
|---|---|---|
| quadriceps | primary | 45 |
| gluteus_maximus | primary | 35 |
| hamstrings | secondary | 15 |
| adductors | secondary | 5 |

**Bulgarian split squat** · `bulgarian_split_squat` · *cp:* lunge · compound · sagittal · unilateral_isolated · intermediate · reps_weight · equipment: dumbbell, bench

| Muscolo | Ruolo | % |
|---|---|---|
| quadriceps | primary | 45 |
| gluteus_maximus | primary | 35 |
| hamstrings | secondary | 15 |
| gluteus_medius | stabilizer | 5 |

**Leg extension** · `leg_extension` · *ja:* knee_extension · isolation · sagittal · bilateral · beginner · reps_weight · equipment: machine

| Muscolo | Ruolo | % |
|---|---|---|
| quadriceps | primary | 100 |

**Leg curl sdraiato (lying)** · `lying_leg_curl` · *ja:* knee_flexion · isolation · sagittal · bilateral · beginner · reps_weight · equipment: machine

| Muscolo | Ruolo | % |
|---|---|---|
| hamstrings | primary | 100 |

**Leg curl seduto (seated)** · `seated_leg_curl` · *ja:* knee_flexion · isolation · sagittal · bilateral · beginner · reps_weight · equipment: machine

| Muscolo | Ruolo | % |
|---|---|---|
| hamstrings | primary | 100 |

**Hip thrust con bilanciere** · `barbell_hip_thrust` · *cp:* hinge · compound · sagittal · bilateral · intermediate · reps_weight · equipment: barbell, bench

| Muscolo | Ruolo | % |
|---|---|---|
| gluteus_maximus | primary | 70 |
| hamstrings | secondary | 25 |
| quadriceps | stabilizer | 5 |

**Good morning** · `good_morning` · *cp:* hinge · compound · sagittal · bilateral · intermediate · reps_weight · equipment: barbell

| Muscolo | Ruolo | % |
|---|---|---|
| hamstrings | primary | 50 |
| gluteus_maximus | primary | 25 |
| erector_spinae | primary | 25 |

**Abduzioni dei glutei alla macchina** · `glute_abduction_machine` · *ja:* hip_abduction · isolation · frontal · bilateral · beginner · reps_weight · equipment: machine

| Muscolo | Ruolo | % |
|---|---|---|
| gluteus_medius | primary | 80 |
| gluteus_maximus | secondary | 20 |

**Squat allo Smith** · `smith_squat` · *cp:* squat · compound · sagittal · bilateral · beginner · reps_weight · equipment: smith_machine

| Muscolo | Ruolo | % |
|---|---|---|
| quadriceps | primary | 55 |
| gluteus_maximus | primary | 25 |
| hamstrings | secondary | 15 |
| adductors | secondary | 5 |

**Pendulum squat** · `pendulum_squat` · *cp:* squat · compound · sagittal · bilateral · intermediate · reps_weight · equipment: plate_loaded

| Muscolo | Ruolo | % |
|---|---|---|
| quadriceps | primary | 65 |
| gluteus_maximus | primary | 20 |
| hamstrings | secondary | 10 |
| adductors | secondary | 5 |

**Sissy squat** · `sissy_squat` · *ja:* knee_extension · isolation · sagittal · bilateral · advanced · reps_weight · equipment: bodyweight

| Muscolo | Ruolo | % |
|---|---|---|
| quadriceps | primary | 95 |
| rectus_abdominis | stabilizer | 5 |

**Affondi camminati** · `walking_lunge` · *cp:* lunge · compound · sagittal · unilateral_alternating · intermediate · reps_weight · equipment: dumbbell

| Muscolo | Ruolo | % |
|---|---|---|
| quadriceps | primary | 45 |
| gluteus_maximus | primary | 35 |
| hamstrings | secondary | 15 |
| adductors | secondary | 5 |

**Hyperextension 45°** · `hyperextension_45` · *ja:* hip_extension · isolation · sagittal · bilateral · beginner · reps_weight · equipment: hyperextension

| Muscolo | Ruolo | % |
|---|---|---|
| gluteus_maximus | primary | 35 |
| hamstrings | primary | 30 |
| erector_spinae | primary | 30 |
| rectus_abdominis | stabilizer | 5 |

**Adductor machine** · `adductor_machine` · *ja:* hip_adduction · isolation · frontal · bilateral · beginner · reps_weight · equipment: machine

| Muscolo | Ruolo | % |
|---|---|---|
| adductors | primary | 100 |

---

### Polpacci (3 esercizi)

**Calf raise in piedi (standing)** · `standing_calf_raise` · *ja:* ankle_plantarflexion · isolation · sagittal · bilateral · beginner · reps_weight · equipment: machine

| Muscolo | Ruolo | % |
|---|---|---|
| gastrocnemius | primary | 75 |
| soleus | primary | 25 |

**Calf raise seduto (seated)** · `seated_calf_raise` · *ja:* ankle_plantarflexion · isolation · sagittal · bilateral · beginner · reps_weight · equipment: machine

| Muscolo | Ruolo | % |
|---|---|---|
| soleus | primary | 85 |
| gastrocnemius | secondary | 15 |

**Donkey calf raise** · `donkey_calf_raise` · *ja:* ankle_plantarflexion · isolation · sagittal · bilateral · intermediate · reps_weight · equipment: machine

| Muscolo | Ruolo | % |
|---|---|---|
| gastrocnemius | primary | 80 |
| soleus | secondary | 20 |

---

### Trapezio (2 esercizi)

**Scrollate con manubri** · `dumbbell_shrug` · *ja:* scapular_elevation · isolation · sagittal · bilateral · beginner · reps_weight · equipment: dumbbell

| Muscolo | Ruolo | % |
|---|---|---|
| trapezius_upper | primary | 90 |
| trapezius_middle | secondary | 10 |

**Face pull ai cavi** · `cable_face_pull` · *ja:* shoulder_horizontal_abduction · isolation · transverse · bilateral · intermediate · reps_weight · equipment: cable

| Muscolo | Ruolo | % |
|---|---|---|
| deltoid_posterior | primary | 50 |
| trapezius_middle | primary | 25 |
| rhomboids | secondary | 15 |
| trapezius_lower | secondary | 10 |

---

### Addome (8 esercizi)

**Crunch a terra** · `floor_crunch` · *ja:* spinal_flexion · isolation · sagittal · bilateral · beginner · reps_only · equipment: bodyweight

| Muscolo | Ruolo | % |
|---|---|---|
| rectus_abdominis | primary | 90 |
| obliques | secondary | 10 |

**Crunch ai cavi (in ginocchio)** · `cable_kneeling_crunch` · *ja:* spinal_flexion · isolation · sagittal · bilateral · intermediate · reps_weight · equipment: cable

| Muscolo | Ruolo | % |
|---|---|---|
| rectus_abdominis | primary | 90 |
| obliques | secondary | 10 |

**Plank frontale** · `front_plank` · *cp:* anti_rotation · isolation · sagittal · bilateral · beginner · isometric_hold · equipment: bodyweight

| Muscolo | Ruolo | % |
|---|---|---|
| transverse_abdominis | primary | 50 |
| rectus_abdominis | primary | 35 |
| obliques | secondary | 15 |

**Russian twist** · `russian_twist` · *cp:* rotation · isolation · transverse · bilateral · beginner · reps_weight · equipment: bodyweight

| Muscolo | Ruolo | % |
|---|---|---|
| obliques | primary | 80 |
| rectus_abdominis | secondary | 20 |

**Leg raises alla sbarra** · `hanging_leg_raise` · *ja:* hip_flexion · compound · sagittal · bilateral · advanced · reps_only · equipment: pull_up_bar, bodyweight

| Muscolo | Ruolo | % |
|---|---|---|
| rectus_abdominis | primary | 75 |
| obliques | secondary | 15 |
| forearm_flexors | stabilizer | 10 |

**Rollout con la ruota** · `ab_wheel_rollout` · *cp:* anti_rotation · compound · sagittal · bilateral · advanced · reps_only · equipment: ab_wheel, bodyweight

| Muscolo | Ruolo | % |
|---|---|---|
| rectus_abdominis | primary | 55 |
| transverse_abdominis | primary | 25 |
| obliques | secondary | 15 |
| latissimus_dorsi | stabilizer | 5 |

**Wood chopper ai cavi** · `cable_woodchopper` · *cp:* rotation · compound · transverse · unilateral_isolated · intermediate · reps_weight · equipment: cable

| Muscolo | Ruolo | % |
|---|---|---|
| obliques | primary | 65 |
| rectus_abdominis | secondary | 20 |
| transverse_abdominis | secondary | 10 |
| latissimus_dorsi | stabilizer | 5 |

**Reverse crunch** · `reverse_crunch` · *ja:* spinal_flexion · isolation · sagittal · bilateral · beginner · reps_only · equipment: bodyweight

| Muscolo | Ruolo | % |
|---|---|---|
| rectus_abdominis | primary | 80 |
| obliques | secondary | 20 |

---

## Conteggio finale

| Gruppo | Esercizi | per cp | per ja |
|---|---|---|---|
| Petto | 11 | 9 | 2 |
| Schiena | 14 | 12 | 2 |
| Spalle | 10 | 4 | 6 |
| Bicipiti | 8 | 0 | 8 |
| Tricipiti | 9 | 3 | 6 |
| Gambe | 18 | 11 | 7 |
| Polpacci | 3 | 0 | 3 |
| Trapezio | 2 | 0 | 2 |
| Addome | 8 | 4 | 4 |
| **Totale** | **83** | **43** | **40** |

---
## Seed SQL pronto all'uso

File da posizionare in `database/seeders/sql/exercises_seed.sql`. Ordine di esecuzione strict: prima i lookup (`movement_patterns`, `muscles`, `equipment`), poi `exercises` che li referenzia via slug, poi i pivot.

```sql
-- =====================================================
-- iron-gym · Seed catalogo esercizi v0.3
-- Prerequisito: tabelle movement_patterns, muscles, equipment,
-- exercises, exercise_muscle, exercise_equipment già migrate
-- (schema step-0-discovery.md v0.3)
-- =====================================================

-- ----------------------------------------------------
-- MOVEMENT PATTERNS (lookup): 12 compound + 15 joint_action
-- ----------------------------------------------------
INSERT INTO movement_patterns (slug, name_it, category, display_order) VALUES
('squat', 'Squat', 'compound_pattern', 1),
('hinge', 'Hinge (cerniera dell''anca)', 'compound_pattern', 2),
('lunge', 'Affondo', 'compound_pattern', 3),
('horizontal_push', 'Spinta orizzontale', 'compound_pattern', 10),
('vertical_push', 'Spinta verticale', 'compound_pattern', 11),
('horizontal_pull', 'Trazione orizzontale', 'compound_pattern', 12),
('vertical_pull', 'Trazione verticale', 'compound_pattern', 13),
('carry', 'Trasporto', 'compound_pattern', 20),
('rotation', 'Rotazione', 'compound_pattern', 30),
('anti_rotation', 'Anti-rotazione', 'compound_pattern', 31),
('plyometric', 'Pliometrico', 'compound_pattern', 40),
('locomotion', 'Locomozione', 'compound_pattern', 41),
('shoulder_abduction', 'Abduzione di spalla', 'joint_action', 100),
('shoulder_horizontal_abduction', 'Abduzione orizzontale di spalla', 'joint_action', 101),
('shoulder_horizontal_adduction', 'Adduzione orizzontale di spalla', 'joint_action', 102),
('shoulder_extension', 'Estensione di spalla', 'joint_action', 103),
('elbow_flexion', 'Flessione di gomito', 'joint_action', 110),
('elbow_extension', 'Estensione di gomito', 'joint_action', 111),
('scapular_elevation', 'Elevazione scapolare', 'joint_action', 120),
('ankle_plantarflexion', 'Flessione plantare di caviglia', 'joint_action', 130),
('spinal_flexion', 'Flessione del rachide', 'joint_action', 140),
('hip_flexion', 'Flessione d''anca', 'joint_action', 150),
('hip_extension', 'Estensione d''anca', 'joint_action', 151),
('hip_abduction', 'Abduzione d''anca', 'joint_action', 152),
('hip_adduction', 'Adduzione d''anca', 'joint_action', 153),
('knee_extension', 'Estensione di ginocchio', 'joint_action', 160),
('knee_flexion', 'Flessione di ginocchio', 'joint_action', 161);

-- ----------------------------------------------------
-- MUSCOLI (lookup)
-- ----------------------------------------------------
INSERT INTO muscles (slug, name_it, muscle_group, muscle_head, display_order) VALUES
('pectoralis_major_clavicular', 'Gran pettorale (clavicolare)', 'chest', 'clavicular', 1),
('pectoralis_major_sternal', 'Gran pettorale (sternale)', 'chest', 'sternal', 2),
('deltoid_anterior', 'Deltoide anteriore', 'shoulders', 'anterior', 10),
('deltoid_lateral', 'Deltoide laterale', 'shoulders', 'lateral', 11),
('deltoid_posterior', 'Deltoide posteriore', 'shoulders', 'posterior', 12),
('triceps_brachii', 'Tricipite brachiale', 'arms', NULL, 20),
('biceps_brachii', 'Bicipite brachiale', 'arms', NULL, 21),
('brachialis', 'Brachiale', 'arms', NULL, 22),
('brachioradialis', 'Brachioradiale', 'arms', NULL, 23),
('forearm_flexors', 'Flessori dell''avambraccio', 'arms', NULL, 24),
('latissimus_dorsi', 'Gran dorsale', 'back', NULL, 30),
('trapezius_upper', 'Trapezio superiore', 'back', 'upper', 31),
('trapezius_middle', 'Trapezio medio', 'back', 'middle', 32),
('trapezius_lower', 'Trapezio inferiore', 'back', 'lower', 33),
('rhomboids', 'Romboidi', 'back', NULL, 34),
('erector_spinae', 'Erettori spinali', 'back', NULL, 35),
('quadriceps', 'Quadricipite', 'legs', NULL, 40),
('hamstrings', 'Ischiocrurali', 'legs', NULL, 41),
('gluteus_maximus', 'Grande gluteo', 'legs', NULL, 42),
('gluteus_medius', 'Medio gluteo', 'legs', NULL, 43),
('adductors', 'Adduttori', 'legs', NULL, 44),
('gastrocnemius', 'Gastrocnemio', 'legs', NULL, 45),
('soleus', 'Soleo', 'legs', NULL, 46),
('rectus_abdominis', 'Retto dell''addome', 'core', NULL, 50),
('obliques', 'Obliqui', 'core', NULL, 51),
('transverse_abdominis', 'Trasverso dell''addome', 'core', NULL, 52);

-- ----------------------------------------------------
-- EQUIPMENT (lookup)
-- ----------------------------------------------------
INSERT INTO equipment (slug, name_it) VALUES
('barbell', 'Bilanciere'),
('dumbbell', 'Manubrio'),
('cable', 'Cavi'),
('machine', 'Macchina'),
('smith_machine', 'Smith machine'),
('bodyweight', 'Corpo libero'),
('kettlebell', 'Kettlebell'),
('band', 'Elastico'),
('plate_loaded', 'Macchina a piastre'),
('bench', 'Panca'),
('pull_up_bar', 'Sbarra'),
('dip_bar', 'Parallele'),
('hyperextension', 'Panca hyperextension'),
('ab_wheel', 'Ruota addominale');

-- ----------------------------------------------------
-- ESERCIZI (anagrafica)
-- LEFT JOIN su movement_patterns per ricavare gli id via slug.
-- Il CHECK XOR garantisce esattamente un pattern valorizzato.
-- ----------------------------------------------------
INSERT INTO exercises
    (slug, name_it, compound_pattern_id, joint_action_id,
     mechanic, plane, laterality, skill_level, measurement_type)
SELECT x.exercise_slug, x.name_it, cp.id, ja.id,
       x.mechanic, x.plane, x.laterality, x.skill_level, x.measurement_type
FROM (
    -- Petto
    SELECT 'barbell_bench_press' AS exercise_slug, 'Panca piana con bilanciere' AS name_it, 'horizontal_push' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'dumbbell_bench_press' AS exercise_slug, 'Panca piana con manubri' AS name_it, 'horizontal_push' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'incline_barbell_bench_press' AS exercise_slug, 'Panca inclinata con bilanciere' AS name_it, 'horizontal_push' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'incline_dumbbell_bench_press' AS exercise_slug, 'Panca inclinata con manubri' AS name_it, 'horizontal_push' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'decline_barbell_bench_press' AS exercise_slug, 'Panca declinata con bilanciere' AS name_it, 'horizontal_push' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'cable_chest_fly' AS exercise_slug, 'Croci ai cavi' AS name_it, NULL AS cp_slug, 'shoulder_horizontal_adduction' AS ja_slug, 'isolation' AS mechanic, 'transverse' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'pec_deck_machine' AS exercise_slug, 'Pectoral machine (peck deck)' AS name_it, NULL AS cp_slug, 'shoulder_horizontal_adduction' AS ja_slug, 'isolation' AS mechanic, 'transverse' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'chest_dips' AS exercise_slug, 'Dip alle parallele per pettorali' AS name_it, 'vertical_push' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'advanced' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'machine_chest_press' AS exercise_slug, 'Chest press alla macchina' AS name_it, 'horizontal_push' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'smith_incline_press' AS exercise_slug, 'Spinte inclinate allo Smith' AS name_it, 'horizontal_push' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'push_up' AS exercise_slug, 'Piegamenti sulle braccia' AS name_it, 'horizontal_push' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_only' AS measurement_type UNION ALL
    -- Schiena
    SELECT 'conventional_deadlift' AS exercise_slug, 'Stacco da terra convenzionale' AS name_it, 'hinge' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'advanced' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'romanian_deadlift' AS exercise_slug, 'Stacco rumeno (RDL)' AS name_it, 'hinge' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'pull_up_pronated' AS exercise_slug, 'Trazioni alla sbarra prone' AS name_it, 'vertical_pull' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'advanced' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'chin_up_supinated' AS exercise_slug, 'Trazioni supinate (chin-up)' AS name_it, 'vertical_pull' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'lat_pulldown_front' AS exercise_slug, 'Lat machine avanti' AS name_it, 'vertical_pull' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'seated_cable_row' AS exercise_slug, 'Pulley basso (seated cable row)' AS name_it, 'horizontal_pull' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'barbell_row' AS exercise_slug, 'Rematore con bilanciere' AS name_it, 'horizontal_pull' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'one_arm_dumbbell_row' AS exercise_slug, 'Rematore con manubrio (one-arm)' AS name_it, 'horizontal_pull' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'unilateral_isolated' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 't_bar_row' AS exercise_slug, 'T-bar row' AS name_it, 'horizontal_pull' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'cable_pullover' AS exercise_slug, 'Pullover ai cavi (alto)' AS name_it, NULL AS cp_slug, 'shoulder_extension' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'chest_supported_row' AS exercise_slug, 'Rematore con appoggio al petto' AS name_it, 'horizontal_pull' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'machine_row' AS exercise_slug, 'Rematore alla macchina' AS name_it, 'horizontal_pull' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'straight_arm_pulldown' AS exercise_slug, 'Pulldown a braccia tese' AS name_it, NULL AS cp_slug, 'shoulder_extension' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'rack_pull' AS exercise_slug, 'Stacco dai blocchi (rack pull)' AS name_it, 'hinge' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'advanced' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    -- Spalle
    SELECT 'overhead_press_standing' AS exercise_slug, 'Military press in piedi (OHP)' AS name_it, 'vertical_push' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'advanced' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'seated_dumbbell_press' AS exercise_slug, 'Lento avanti con manubri (seduto)' AS name_it, 'vertical_push' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'arnold_press' AS exercise_slug, 'Arnold press' AS name_it, 'vertical_push' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'multiplanar' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'dumbbell_lateral_raise' AS exercise_slug, 'Alzate laterali con manubri' AS name_it, NULL AS cp_slug, 'shoulder_abduction' AS ja_slug, 'isolation' AS mechanic, 'frontal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'cable_lateral_raise' AS exercise_slug, 'Alzate laterali ai cavi' AS name_it, NULL AS cp_slug, 'shoulder_abduction' AS ja_slug, 'isolation' AS mechanic, 'frontal' AS plane, 'unilateral_isolated' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'reverse_pec_deck' AS exercise_slug, 'Reverse pec deck' AS name_it, NULL AS cp_slug, 'shoulder_horizontal_abduction' AS ja_slug, 'isolation' AS mechanic, 'transverse' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'bent_over_rear_delt_raise' AS exercise_slug, 'Alzate posteriori a busto in avanti' AS name_it, NULL AS cp_slug, 'shoulder_horizontal_abduction' AS ja_slug, 'isolation' AS mechanic, 'transverse' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'upright_row' AS exercise_slug, 'Tirata al mento (upright row)' AS name_it, 'vertical_pull' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'frontal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'cable_rear_delt_fly' AS exercise_slug, 'Croci posteriori ai cavi' AS name_it, NULL AS cp_slug, 'shoulder_horizontal_abduction' AS ja_slug, 'isolation' AS mechanic, 'transverse' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'machine_lateral_raise' AS exercise_slug, 'Alzate laterali alla macchina' AS name_it, NULL AS cp_slug, 'shoulder_abduction' AS ja_slug, 'isolation' AS mechanic, 'frontal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    -- Bicipiti
    SELECT 'barbell_curl' AS exercise_slug, 'Curl con bilanciere' AS name_it, NULL AS cp_slug, 'elbow_flexion' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'alternating_dumbbell_curl' AS exercise_slug, 'Curl con manubri alternato' AS name_it, NULL AS cp_slug, 'elbow_flexion' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'unilateral_alternating' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'scott_curl' AS exercise_slug, 'Curl alla panca Scott' AS name_it, NULL AS cp_slug, 'elbow_flexion' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'hammer_curl' AS exercise_slug, 'Hammer curl' AS name_it, NULL AS cp_slug, 'elbow_flexion' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'unilateral_alternating' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'cable_curl' AS exercise_slug, 'Curl ai cavi' AS name_it, NULL AS cp_slug, 'elbow_flexion' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'concentration_curl' AS exercise_slug, 'Curl concentrato' AS name_it, NULL AS cp_slug, 'elbow_flexion' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'unilateral_isolated' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'incline_dumbbell_curl' AS exercise_slug, 'Curl con manubri su panca inclinata' AS name_it, NULL AS cp_slug, 'elbow_flexion' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'unilateral_alternating' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'bayesian_curl' AS exercise_slug, 'Bayesian curl ai cavi' AS name_it, NULL AS cp_slug, 'elbow_flexion' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'unilateral_isolated' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    -- Tricipiti
    SELECT 'ez_bar_french_press' AS exercise_slug, 'French press con bilanciere EZ' AS name_it, NULL AS cp_slug, 'elbow_extension' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'cable_pushdown_straight' AS exercise_slug, 'Push down ai cavi (sbarra dritta)' AS name_it, NULL AS cp_slug, 'elbow_extension' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'cable_pushdown_rope' AS exercise_slug, 'Push down ai cavi con corda' AS name_it, NULL AS cp_slug, 'elbow_extension' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'triceps_dips' AS exercise_slug, 'Dip alle parallele (tricipiti)' AS name_it, 'vertical_push' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'advanced' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'skullcrusher' AS exercise_slug, 'Skullcrusher' AS name_it, NULL AS cp_slug, 'elbow_extension' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'close_grip_bench_press' AS exercise_slug, 'Panca stretta' AS name_it, 'horizontal_push' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'overhead_cable_extension' AS exercise_slug, 'Estensioni tricipiti sopra la testa ai cavi' AS name_it, NULL AS cp_slug, 'elbow_extension' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'single_arm_cable_pushdown' AS exercise_slug, 'Push down ai cavi a un braccio' AS name_it, NULL AS cp_slug, 'elbow_extension' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'unilateral_isolated' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'jm_press' AS exercise_slug, 'JM press' AS name_it, 'horizontal_push' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'advanced' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    -- Gambe
    SELECT 'back_squat_high_bar' AS exercise_slug, 'Squat con bilanciere (high-bar)' AS name_it, 'squat' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'advanced' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'front_squat' AS exercise_slug, 'Front squat' AS name_it, 'squat' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'advanced' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'hack_squat_machine' AS exercise_slug, 'Hack squat machine' AS name_it, 'squat' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'leg_press_45' AS exercise_slug, 'Leg press 45°' AS name_it, 'squat' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'barbell_lunge' AS exercise_slug, 'Affondi con bilanciere' AS name_it, 'lunge' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'unilateral_alternating' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'bulgarian_split_squat' AS exercise_slug, 'Bulgarian split squat' AS name_it, 'lunge' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'unilateral_isolated' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'leg_extension' AS exercise_slug, 'Leg extension' AS name_it, NULL AS cp_slug, 'knee_extension' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'lying_leg_curl' AS exercise_slug, 'Leg curl sdraiato (lying)' AS name_it, NULL AS cp_slug, 'knee_flexion' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'seated_leg_curl' AS exercise_slug, 'Leg curl seduto (seated)' AS name_it, NULL AS cp_slug, 'knee_flexion' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'barbell_hip_thrust' AS exercise_slug, 'Hip thrust con bilanciere' AS name_it, 'hinge' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'good_morning' AS exercise_slug, 'Good morning' AS name_it, 'hinge' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'glute_abduction_machine' AS exercise_slug, 'Abduzioni dei glutei alla macchina' AS name_it, NULL AS cp_slug, 'hip_abduction' AS ja_slug, 'isolation' AS mechanic, 'frontal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'smith_squat' AS exercise_slug, 'Squat allo Smith' AS name_it, 'squat' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'pendulum_squat' AS exercise_slug, 'Pendulum squat' AS name_it, 'squat' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'sissy_squat' AS exercise_slug, 'Sissy squat' AS name_it, NULL AS cp_slug, 'knee_extension' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'advanced' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'walking_lunge' AS exercise_slug, 'Affondi camminati' AS name_it, 'lunge' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'unilateral_alternating' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'hyperextension_45' AS exercise_slug, 'Hyperextension 45°' AS name_it, NULL AS cp_slug, 'hip_extension' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'adductor_machine' AS exercise_slug, 'Adductor machine' AS name_it, NULL AS cp_slug, 'hip_adduction' AS ja_slug, 'isolation' AS mechanic, 'frontal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    -- Polpacci
    SELECT 'standing_calf_raise' AS exercise_slug, 'Calf raise in piedi (standing)' AS name_it, NULL AS cp_slug, 'ankle_plantarflexion' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'seated_calf_raise' AS exercise_slug, 'Calf raise seduto (seated)' AS name_it, NULL AS cp_slug, 'ankle_plantarflexion' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'donkey_calf_raise' AS exercise_slug, 'Donkey calf raise' AS name_it, NULL AS cp_slug, 'ankle_plantarflexion' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    -- Trapezio
    SELECT 'dumbbell_shrug' AS exercise_slug, 'Scrollate con manubri' AS name_it, NULL AS cp_slug, 'scapular_elevation' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'cable_face_pull' AS exercise_slug, 'Face pull ai cavi' AS name_it, NULL AS cp_slug, 'shoulder_horizontal_abduction' AS ja_slug, 'isolation' AS mechanic, 'transverse' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    -- Addome
    SELECT 'floor_crunch' AS exercise_slug, 'Crunch a terra' AS name_it, NULL AS cp_slug, 'spinal_flexion' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_only' AS measurement_type UNION ALL
    SELECT 'cable_kneeling_crunch' AS exercise_slug, 'Crunch ai cavi (in ginocchio)' AS name_it, NULL AS cp_slug, 'spinal_flexion' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'front_plank' AS exercise_slug, 'Plank frontale' AS name_it, 'anti_rotation' AS cp_slug, NULL AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'isometric_hold' AS measurement_type UNION ALL
    SELECT 'russian_twist' AS exercise_slug, 'Russian twist' AS name_it, 'rotation' AS cp_slug, NULL AS ja_slug, 'isolation' AS mechanic, 'transverse' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'hanging_leg_raise' AS exercise_slug, 'Leg raises alla sbarra' AS name_it, NULL AS cp_slug, 'hip_flexion' AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'advanced' AS skill_level, 'reps_only' AS measurement_type UNION ALL
    SELECT 'ab_wheel_rollout' AS exercise_slug, 'Rollout con la ruota' AS name_it, 'anti_rotation' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'advanced' AS skill_level, 'reps_only' AS measurement_type UNION ALL
    SELECT 'cable_woodchopper' AS exercise_slug, 'Wood chopper ai cavi' AS name_it, 'rotation' AS cp_slug, NULL AS ja_slug, 'compound' AS mechanic, 'transverse' AS plane, 'unilateral_isolated' AS laterality, 'intermediate' AS skill_level, 'reps_weight' AS measurement_type UNION ALL
    SELECT 'reverse_crunch' AS exercise_slug, 'Reverse crunch' AS name_it, NULL AS cp_slug, 'spinal_flexion' AS ja_slug, 'isolation' AS mechanic, 'sagittal' AS plane, 'bilateral' AS laterality, 'beginner' AS skill_level, 'reps_only' AS measurement_type
) x
LEFT JOIN movement_patterns cp ON cp.slug = x.cp_slug AND cp.category = 'compound_pattern'
LEFT JOIN movement_patterns ja ON ja.slug = x.ja_slug AND ja.category = 'joint_action';

-- ----------------------------------------------------
-- EXERCISE_MUSCLE (via JOIN su slug)
-- ----------------------------------------------------
-- Petto
INSERT INTO exercise_muscle (exercise_id, muscle_id, role, contribution_pct)
SELECT e.id, m.id, x.role, x.contribution_pct FROM (
    SELECT 'barbell_bench_press' AS exercise_slug, 'pectoralis_major_sternal' AS muscle_slug, 'primary' AS role, 60 AS contribution_pct UNION ALL
    SELECT 'barbell_bench_press', 'pectoralis_major_clavicular', 'primary', 15 UNION ALL
    SELECT 'barbell_bench_press', 'triceps_brachii', 'secondary', 20 UNION ALL
    SELECT 'barbell_bench_press', 'deltoid_anterior', 'secondary', 15 UNION ALL
    SELECT 'dumbbell_bench_press', 'pectoralis_major_sternal', 'primary', 65 UNION ALL
    SELECT 'dumbbell_bench_press', 'pectoralis_major_clavicular', 'primary', 15 UNION ALL
    SELECT 'dumbbell_bench_press', 'triceps_brachii', 'secondary', 15 UNION ALL
    SELECT 'dumbbell_bench_press', 'deltoid_anterior', 'secondary', 15 UNION ALL
    SELECT 'incline_barbell_bench_press', 'pectoralis_major_clavicular', 'primary', 60 UNION ALL
    SELECT 'incline_barbell_bench_press', 'pectoralis_major_sternal', 'secondary', 20 UNION ALL
    SELECT 'incline_barbell_bench_press', 'deltoid_anterior', 'secondary', 25 UNION ALL
    SELECT 'incline_barbell_bench_press', 'triceps_brachii', 'secondary', 15 UNION ALL
    SELECT 'incline_dumbbell_bench_press', 'pectoralis_major_clavicular', 'primary', 60 UNION ALL
    SELECT 'incline_dumbbell_bench_press', 'pectoralis_major_sternal', 'secondary', 20 UNION ALL
    SELECT 'incline_dumbbell_bench_press', 'deltoid_anterior', 'secondary', 25 UNION ALL
    SELECT 'incline_dumbbell_bench_press', 'triceps_brachii', 'secondary', 15 UNION ALL
    SELECT 'decline_barbell_bench_press', 'pectoralis_major_sternal', 'primary', 75 UNION ALL
    SELECT 'decline_barbell_bench_press', 'triceps_brachii', 'secondary', 20 UNION ALL
    SELECT 'decline_barbell_bench_press', 'deltoid_anterior', 'secondary', 10 UNION ALL
    SELECT 'cable_chest_fly', 'pectoralis_major_sternal', 'primary', 80 UNION ALL
    SELECT 'cable_chest_fly', 'pectoralis_major_clavicular', 'primary', 15 UNION ALL
    SELECT 'cable_chest_fly', 'deltoid_anterior', 'stabilizer', 5 UNION ALL
    SELECT 'pec_deck_machine', 'pectoralis_major_sternal', 'primary', 85 UNION ALL
    SELECT 'pec_deck_machine', 'pectoralis_major_clavicular', 'primary', 10 UNION ALL
    SELECT 'pec_deck_machine', 'deltoid_anterior', 'stabilizer', 5 UNION ALL
    SELECT 'chest_dips', 'pectoralis_major_sternal', 'primary', 55 UNION ALL
    SELECT 'chest_dips', 'triceps_brachii', 'primary', 30 UNION ALL
    SELECT 'chest_dips', 'deltoid_anterior', 'secondary', 15 UNION ALL
    SELECT 'machine_chest_press', 'pectoralis_major_sternal', 'primary', 70 UNION ALL
    SELECT 'machine_chest_press', 'pectoralis_major_clavicular', 'primary', 15 UNION ALL
    SELECT 'machine_chest_press', 'triceps_brachii', 'secondary', 15 UNION ALL
    SELECT 'machine_chest_press', 'deltoid_anterior', 'secondary', 10 UNION ALL
    SELECT 'smith_incline_press', 'pectoralis_major_clavicular', 'primary', 60 UNION ALL
    SELECT 'smith_incline_press', 'pectoralis_major_sternal', 'secondary', 20 UNION ALL
    SELECT 'smith_incline_press', 'deltoid_anterior', 'secondary', 25 UNION ALL
    SELECT 'smith_incline_press', 'triceps_brachii', 'secondary', 15 UNION ALL
    SELECT 'push_up', 'pectoralis_major_sternal', 'primary', 50 UNION ALL
    SELECT 'push_up', 'pectoralis_major_clavicular', 'primary', 15 UNION ALL
    SELECT 'push_up', 'triceps_brachii', 'secondary', 20 UNION ALL
    SELECT 'push_up', 'deltoid_anterior', 'secondary', 15 UNION ALL
    SELECT 'push_up', 'transverse_abdominis', 'stabilizer', 5
) x
JOIN exercises e ON e.slug = x.exercise_slug
JOIN muscles   m ON m.slug = x.muscle_slug;

-- Schiena
INSERT INTO exercise_muscle (exercise_id, muscle_id, role, contribution_pct)
SELECT e.id, m.id, x.role, x.contribution_pct FROM (
    SELECT 'conventional_deadlift' AS exercise_slug, 'hamstrings' AS muscle_slug, 'primary' AS role, 30 AS contribution_pct UNION ALL
    SELECT 'conventional_deadlift', 'gluteus_maximus', 'primary', 25 UNION ALL
    SELECT 'conventional_deadlift', 'erector_spinae', 'primary', 25 UNION ALL
    SELECT 'conventional_deadlift', 'latissimus_dorsi', 'secondary', 10 UNION ALL
    SELECT 'conventional_deadlift', 'trapezius_middle', 'secondary', 5 UNION ALL
    SELECT 'conventional_deadlift', 'forearm_flexors', 'stabilizer', 5 UNION ALL
    SELECT 'romanian_deadlift', 'hamstrings', 'primary', 55 UNION ALL
    SELECT 'romanian_deadlift', 'gluteus_maximus', 'primary', 30 UNION ALL
    SELECT 'romanian_deadlift', 'erector_spinae', 'secondary', 15 UNION ALL
    SELECT 'pull_up_pronated', 'latissimus_dorsi', 'primary', 65 UNION ALL
    SELECT 'pull_up_pronated', 'biceps_brachii', 'secondary', 15 UNION ALL
    SELECT 'pull_up_pronated', 'trapezius_middle', 'secondary', 10 UNION ALL
    SELECT 'pull_up_pronated', 'rhomboids', 'secondary', 10 UNION ALL
    SELECT 'pull_up_pronated', 'brachialis', 'secondary', 5 UNION ALL
    SELECT 'chin_up_supinated', 'latissimus_dorsi', 'primary', 55 UNION ALL
    SELECT 'chin_up_supinated', 'biceps_brachii', 'primary', 25 UNION ALL
    SELECT 'chin_up_supinated', 'brachialis', 'secondary', 10 UNION ALL
    SELECT 'chin_up_supinated', 'trapezius_middle', 'secondary', 10 UNION ALL
    SELECT 'lat_pulldown_front', 'latissimus_dorsi', 'primary', 65 UNION ALL
    SELECT 'lat_pulldown_front', 'biceps_brachii', 'secondary', 15 UNION ALL
    SELECT 'lat_pulldown_front', 'trapezius_middle', 'secondary', 10 UNION ALL
    SELECT 'lat_pulldown_front', 'rhomboids', 'secondary', 10 UNION ALL
    SELECT 'seated_cable_row', 'latissimus_dorsi', 'primary', 35 UNION ALL
    SELECT 'seated_cable_row', 'trapezius_middle', 'primary', 25 UNION ALL
    SELECT 'seated_cable_row', 'rhomboids', 'primary', 25 UNION ALL
    SELECT 'seated_cable_row', 'biceps_brachii', 'secondary', 15 UNION ALL
    SELECT 'barbell_row', 'latissimus_dorsi', 'primary', 40 UNION ALL
    SELECT 'barbell_row', 'trapezius_middle', 'primary', 25 UNION ALL
    SELECT 'barbell_row', 'rhomboids', 'primary', 20 UNION ALL
    SELECT 'barbell_row', 'biceps_brachii', 'secondary', 15 UNION ALL
    SELECT 'barbell_row', 'erector_spinae', 'stabilizer', 10 UNION ALL
    SELECT 'one_arm_dumbbell_row', 'latissimus_dorsi', 'primary', 50 UNION ALL
    SELECT 'one_arm_dumbbell_row', 'trapezius_middle', 'primary', 20 UNION ALL
    SELECT 'one_arm_dumbbell_row', 'rhomboids', 'secondary', 15 UNION ALL
    SELECT 'one_arm_dumbbell_row', 'biceps_brachii', 'secondary', 15 UNION ALL
    SELECT 't_bar_row', 'latissimus_dorsi', 'primary', 40 UNION ALL
    SELECT 't_bar_row', 'trapezius_middle', 'primary', 25 UNION ALL
    SELECT 't_bar_row', 'rhomboids', 'primary', 20 UNION ALL
    SELECT 't_bar_row', 'biceps_brachii', 'secondary', 15 UNION ALL
    SELECT 'cable_pullover', 'latissimus_dorsi', 'primary', 85 UNION ALL
    SELECT 'cable_pullover', 'trapezius_lower', 'secondary', 10 UNION ALL
    SELECT 'cable_pullover', 'triceps_brachii', 'stabilizer', 5 UNION ALL
    SELECT 'chest_supported_row', 'latissimus_dorsi', 'primary', 40 UNION ALL
    SELECT 'chest_supported_row', 'trapezius_middle', 'primary', 25 UNION ALL
    SELECT 'chest_supported_row', 'rhomboids', 'primary', 20 UNION ALL
    SELECT 'chest_supported_row', 'biceps_brachii', 'secondary', 15 UNION ALL
    SELECT 'machine_row', 'latissimus_dorsi', 'primary', 40 UNION ALL
    SELECT 'machine_row', 'trapezius_middle', 'primary', 25 UNION ALL
    SELECT 'machine_row', 'rhomboids', 'primary', 20 UNION ALL
    SELECT 'machine_row', 'biceps_brachii', 'secondary', 15 UNION ALL
    SELECT 'straight_arm_pulldown', 'latissimus_dorsi', 'primary', 85 UNION ALL
    SELECT 'straight_arm_pulldown', 'trapezius_lower', 'secondary', 10 UNION ALL
    SELECT 'straight_arm_pulldown', 'rectus_abdominis', 'stabilizer', 5 UNION ALL
    SELECT 'rack_pull', 'erector_spinae', 'primary', 35 UNION ALL
    SELECT 'rack_pull', 'gluteus_maximus', 'primary', 25 UNION ALL
    SELECT 'rack_pull', 'hamstrings', 'primary', 20 UNION ALL
    SELECT 'rack_pull', 'trapezius_middle', 'secondary', 10 UNION ALL
    SELECT 'rack_pull', 'latissimus_dorsi', 'secondary', 10 UNION ALL
    SELECT 'rack_pull', 'forearm_flexors', 'stabilizer', 5
) x
JOIN exercises e ON e.slug = x.exercise_slug
JOIN muscles   m ON m.slug = x.muscle_slug;

-- Spalle
INSERT INTO exercise_muscle (exercise_id, muscle_id, role, contribution_pct)
SELECT e.id, m.id, x.role, x.contribution_pct FROM (
    SELECT 'overhead_press_standing' AS exercise_slug, 'deltoid_anterior' AS muscle_slug, 'primary' AS role, 55 AS contribution_pct UNION ALL
    SELECT 'overhead_press_standing', 'deltoid_lateral', 'primary', 20 UNION ALL
    SELECT 'overhead_press_standing', 'triceps_brachii', 'secondary', 20 UNION ALL
    SELECT 'overhead_press_standing', 'trapezius_upper', 'stabilizer', 10 UNION ALL
    SELECT 'overhead_press_standing', 'erector_spinae', 'stabilizer', 10 UNION ALL
    SELECT 'seated_dumbbell_press', 'deltoid_anterior', 'primary', 50 UNION ALL
    SELECT 'seated_dumbbell_press', 'deltoid_lateral', 'primary', 25 UNION ALL
    SELECT 'seated_dumbbell_press', 'triceps_brachii', 'secondary', 20 UNION ALL
    SELECT 'seated_dumbbell_press', 'trapezius_upper', 'stabilizer', 10 UNION ALL
    SELECT 'arnold_press', 'deltoid_anterior', 'primary', 50 UNION ALL
    SELECT 'arnold_press', 'deltoid_lateral', 'primary', 30 UNION ALL
    SELECT 'arnold_press', 'triceps_brachii', 'secondary', 15 UNION ALL
    SELECT 'arnold_press', 'trapezius_upper', 'stabilizer', 10 UNION ALL
    SELECT 'dumbbell_lateral_raise', 'deltoid_lateral', 'primary', 90 UNION ALL
    SELECT 'dumbbell_lateral_raise', 'deltoid_anterior', 'secondary', 10 UNION ALL
    SELECT 'cable_lateral_raise', 'deltoid_lateral', 'primary', 95 UNION ALL
    SELECT 'cable_lateral_raise', 'deltoid_anterior', 'secondary', 5 UNION ALL
    SELECT 'reverse_pec_deck', 'deltoid_posterior', 'primary', 75 UNION ALL
    SELECT 'reverse_pec_deck', 'rhomboids', 'secondary', 15 UNION ALL
    SELECT 'reverse_pec_deck', 'trapezius_middle', 'secondary', 10 UNION ALL
    SELECT 'bent_over_rear_delt_raise', 'deltoid_posterior', 'primary', 75 UNION ALL
    SELECT 'bent_over_rear_delt_raise', 'rhomboids', 'secondary', 15 UNION ALL
    SELECT 'bent_over_rear_delt_raise', 'trapezius_middle', 'secondary', 10 UNION ALL
    SELECT 'upright_row', 'deltoid_lateral', 'primary', 50 UNION ALL
    SELECT 'upright_row', 'trapezius_upper', 'primary', 25 UNION ALL
    SELECT 'upright_row', 'deltoid_anterior', 'secondary', 15 UNION ALL
    SELECT 'upright_row', 'biceps_brachii', 'secondary', 10 UNION ALL
    SELECT 'cable_rear_delt_fly', 'deltoid_posterior', 'primary', 75 UNION ALL
    SELECT 'cable_rear_delt_fly', 'rhomboids', 'secondary', 15 UNION ALL
    SELECT 'cable_rear_delt_fly', 'trapezius_middle', 'secondary', 10 UNION ALL
    SELECT 'machine_lateral_raise', 'deltoid_lateral', 'primary', 90 UNION ALL
    SELECT 'machine_lateral_raise', 'deltoid_anterior', 'secondary', 10
) x
JOIN exercises e ON e.slug = x.exercise_slug
JOIN muscles   m ON m.slug = x.muscle_slug;

-- Bicipiti
INSERT INTO exercise_muscle (exercise_id, muscle_id, role, contribution_pct)
SELECT e.id, m.id, x.role, x.contribution_pct FROM (
    SELECT 'barbell_curl' AS exercise_slug, 'biceps_brachii' AS muscle_slug, 'primary' AS role, 80 AS contribution_pct UNION ALL
    SELECT 'barbell_curl', 'brachialis', 'secondary', 15 UNION ALL
    SELECT 'barbell_curl', 'brachioradialis', 'secondary', 5 UNION ALL
    SELECT 'alternating_dumbbell_curl', 'biceps_brachii', 'primary', 80 UNION ALL
    SELECT 'alternating_dumbbell_curl', 'brachialis', 'secondary', 15 UNION ALL
    SELECT 'alternating_dumbbell_curl', 'brachioradialis', 'secondary', 5 UNION ALL
    SELECT 'scott_curl', 'biceps_brachii', 'primary', 85 UNION ALL
    SELECT 'scott_curl', 'brachialis', 'secondary', 15 UNION ALL
    SELECT 'hammer_curl', 'brachialis', 'primary', 50 UNION ALL
    SELECT 'hammer_curl', 'brachioradialis', 'primary', 30 UNION ALL
    SELECT 'hammer_curl', 'biceps_brachii', 'secondary', 20 UNION ALL
    SELECT 'cable_curl', 'biceps_brachii', 'primary', 85 UNION ALL
    SELECT 'cable_curl', 'brachialis', 'secondary', 10 UNION ALL
    SELECT 'cable_curl', 'brachioradialis', 'secondary', 5 UNION ALL
    SELECT 'concentration_curl', 'biceps_brachii', 'primary', 90 UNION ALL
    SELECT 'concentration_curl', 'brachialis', 'secondary', 10 UNION ALL
    SELECT 'incline_dumbbell_curl', 'biceps_brachii', 'primary', 85 UNION ALL
    SELECT 'incline_dumbbell_curl', 'brachialis', 'secondary', 10 UNION ALL
    SELECT 'incline_dumbbell_curl', 'brachioradialis', 'secondary', 5 UNION ALL
    SELECT 'bayesian_curl', 'biceps_brachii', 'primary', 90 UNION ALL
    SELECT 'bayesian_curl', 'brachialis', 'secondary', 10
) x
JOIN exercises e ON e.slug = x.exercise_slug
JOIN muscles   m ON m.slug = x.muscle_slug;

-- Tricipiti
INSERT INTO exercise_muscle (exercise_id, muscle_id, role, contribution_pct)
SELECT e.id, m.id, x.role, x.contribution_pct FROM (
    SELECT 'ez_bar_french_press' AS exercise_slug, 'triceps_brachii' AS muscle_slug, 'primary' AS role, 95 AS contribution_pct UNION ALL
    SELECT 'ez_bar_french_press', 'deltoid_posterior', 'stabilizer', 5 UNION ALL
    SELECT 'cable_pushdown_straight', 'triceps_brachii', 'primary', 100 UNION ALL
    SELECT 'cable_pushdown_rope', 'triceps_brachii', 'primary', 100 UNION ALL
    SELECT 'triceps_dips', 'triceps_brachii', 'primary', 55 UNION ALL
    SELECT 'triceps_dips', 'pectoralis_major_sternal', 'secondary', 30 UNION ALL
    SELECT 'triceps_dips', 'deltoid_anterior', 'secondary', 15 UNION ALL
    SELECT 'skullcrusher', 'triceps_brachii', 'primary', 95 UNION ALL
    SELECT 'skullcrusher', 'deltoid_posterior', 'stabilizer', 5 UNION ALL
    SELECT 'close_grip_bench_press', 'triceps_brachii', 'primary', 55 UNION ALL
    SELECT 'close_grip_bench_press', 'pectoralis_major_sternal', 'primary', 25 UNION ALL
    SELECT 'close_grip_bench_press', 'deltoid_anterior', 'secondary', 20 UNION ALL
    SELECT 'overhead_cable_extension', 'triceps_brachii', 'primary', 100 UNION ALL
    SELECT 'single_arm_cable_pushdown', 'triceps_brachii', 'primary', 100 UNION ALL
    SELECT 'jm_press', 'triceps_brachii', 'primary', 75 UNION ALL
    SELECT 'jm_press', 'pectoralis_major_sternal', 'secondary', 15 UNION ALL
    SELECT 'jm_press', 'deltoid_anterior', 'secondary', 10
) x
JOIN exercises e ON e.slug = x.exercise_slug
JOIN muscles   m ON m.slug = x.muscle_slug;

-- Gambe
INSERT INTO exercise_muscle (exercise_id, muscle_id, role, contribution_pct)
SELECT e.id, m.id, x.role, x.contribution_pct FROM (
    SELECT 'back_squat_high_bar' AS exercise_slug, 'quadriceps' AS muscle_slug, 'primary' AS role, 50 AS contribution_pct UNION ALL
    SELECT 'back_squat_high_bar', 'gluteus_maximus', 'primary', 25 UNION ALL
    SELECT 'back_squat_high_bar', 'hamstrings', 'secondary', 15 UNION ALL
    SELECT 'back_squat_high_bar', 'erector_spinae', 'stabilizer', 10 UNION ALL
    SELECT 'back_squat_high_bar', 'adductors', 'secondary', 10 UNION ALL
    SELECT 'front_squat', 'quadriceps', 'primary', 60 UNION ALL
    SELECT 'front_squat', 'gluteus_maximus', 'primary', 20 UNION ALL
    SELECT 'front_squat', 'hamstrings', 'secondary', 10 UNION ALL
    SELECT 'front_squat', 'erector_spinae', 'stabilizer', 10 UNION ALL
    SELECT 'hack_squat_machine', 'quadriceps', 'primary', 65 UNION ALL
    SELECT 'hack_squat_machine', 'gluteus_maximus', 'primary', 20 UNION ALL
    SELECT 'hack_squat_machine', 'hamstrings', 'secondary', 10 UNION ALL
    SELECT 'hack_squat_machine', 'adductors', 'secondary', 5 UNION ALL
    SELECT 'leg_press_45', 'quadriceps', 'primary', 55 UNION ALL
    SELECT 'leg_press_45', 'gluteus_maximus', 'primary', 25 UNION ALL
    SELECT 'leg_press_45', 'hamstrings', 'secondary', 15 UNION ALL
    SELECT 'leg_press_45', 'adductors', 'secondary', 5 UNION ALL
    SELECT 'barbell_lunge', 'quadriceps', 'primary', 45 UNION ALL
    SELECT 'barbell_lunge', 'gluteus_maximus', 'primary', 35 UNION ALL
    SELECT 'barbell_lunge', 'hamstrings', 'secondary', 15 UNION ALL
    SELECT 'barbell_lunge', 'adductors', 'secondary', 5 UNION ALL
    SELECT 'bulgarian_split_squat', 'quadriceps', 'primary', 45 UNION ALL
    SELECT 'bulgarian_split_squat', 'gluteus_maximus', 'primary', 35 UNION ALL
    SELECT 'bulgarian_split_squat', 'hamstrings', 'secondary', 15 UNION ALL
    SELECT 'bulgarian_split_squat', 'gluteus_medius', 'stabilizer', 5 UNION ALL
    SELECT 'leg_extension', 'quadriceps', 'primary', 100 UNION ALL
    SELECT 'lying_leg_curl', 'hamstrings', 'primary', 100 UNION ALL
    SELECT 'seated_leg_curl', 'hamstrings', 'primary', 100 UNION ALL
    SELECT 'barbell_hip_thrust', 'gluteus_maximus', 'primary', 70 UNION ALL
    SELECT 'barbell_hip_thrust', 'hamstrings', 'secondary', 25 UNION ALL
    SELECT 'barbell_hip_thrust', 'quadriceps', 'stabilizer', 5 UNION ALL
    SELECT 'good_morning', 'hamstrings', 'primary', 50 UNION ALL
    SELECT 'good_morning', 'gluteus_maximus', 'primary', 25 UNION ALL
    SELECT 'good_morning', 'erector_spinae', 'primary', 25 UNION ALL
    SELECT 'glute_abduction_machine', 'gluteus_medius', 'primary', 80 UNION ALL
    SELECT 'glute_abduction_machine', 'gluteus_maximus', 'secondary', 20 UNION ALL
    SELECT 'smith_squat', 'quadriceps', 'primary', 55 UNION ALL
    SELECT 'smith_squat', 'gluteus_maximus', 'primary', 25 UNION ALL
    SELECT 'smith_squat', 'hamstrings', 'secondary', 15 UNION ALL
    SELECT 'smith_squat', 'adductors', 'secondary', 5 UNION ALL
    SELECT 'pendulum_squat', 'quadriceps', 'primary', 65 UNION ALL
    SELECT 'pendulum_squat', 'gluteus_maximus', 'primary', 20 UNION ALL
    SELECT 'pendulum_squat', 'hamstrings', 'secondary', 10 UNION ALL
    SELECT 'pendulum_squat', 'adductors', 'secondary', 5 UNION ALL
    SELECT 'sissy_squat', 'quadriceps', 'primary', 95 UNION ALL
    SELECT 'sissy_squat', 'rectus_abdominis', 'stabilizer', 5 UNION ALL
    SELECT 'walking_lunge', 'quadriceps', 'primary', 45 UNION ALL
    SELECT 'walking_lunge', 'gluteus_maximus', 'primary', 35 UNION ALL
    SELECT 'walking_lunge', 'hamstrings', 'secondary', 15 UNION ALL
    SELECT 'walking_lunge', 'adductors', 'secondary', 5 UNION ALL
    SELECT 'hyperextension_45', 'gluteus_maximus', 'primary', 35 UNION ALL
    SELECT 'hyperextension_45', 'hamstrings', 'primary', 30 UNION ALL
    SELECT 'hyperextension_45', 'erector_spinae', 'primary', 30 UNION ALL
    SELECT 'hyperextension_45', 'rectus_abdominis', 'stabilizer', 5 UNION ALL
    SELECT 'adductor_machine', 'adductors', 'primary', 100
) x
JOIN exercises e ON e.slug = x.exercise_slug
JOIN muscles   m ON m.slug = x.muscle_slug;

-- Polpacci, trapezio, addome
INSERT INTO exercise_muscle (exercise_id, muscle_id, role, contribution_pct)
SELECT e.id, m.id, x.role, x.contribution_pct FROM (
    SELECT 'standing_calf_raise' AS exercise_slug, 'gastrocnemius' AS muscle_slug, 'primary' AS role, 75 AS contribution_pct UNION ALL
    SELECT 'standing_calf_raise', 'soleus', 'primary', 25 UNION ALL
    SELECT 'seated_calf_raise', 'soleus', 'primary', 85 UNION ALL
    SELECT 'seated_calf_raise', 'gastrocnemius', 'secondary', 15 UNION ALL
    SELECT 'donkey_calf_raise', 'gastrocnemius', 'primary', 80 UNION ALL
    SELECT 'donkey_calf_raise', 'soleus', 'secondary', 20 UNION ALL
    SELECT 'dumbbell_shrug', 'trapezius_upper', 'primary', 90 UNION ALL
    SELECT 'dumbbell_shrug', 'trapezius_middle', 'secondary', 10 UNION ALL
    SELECT 'cable_face_pull', 'deltoid_posterior', 'primary', 50 UNION ALL
    SELECT 'cable_face_pull', 'trapezius_middle', 'primary', 25 UNION ALL
    SELECT 'cable_face_pull', 'rhomboids', 'secondary', 15 UNION ALL
    SELECT 'cable_face_pull', 'trapezius_lower', 'secondary', 10 UNION ALL
    SELECT 'floor_crunch', 'rectus_abdominis', 'primary', 90 UNION ALL
    SELECT 'floor_crunch', 'obliques', 'secondary', 10 UNION ALL
    SELECT 'cable_kneeling_crunch', 'rectus_abdominis', 'primary', 90 UNION ALL
    SELECT 'cable_kneeling_crunch', 'obliques', 'secondary', 10 UNION ALL
    SELECT 'front_plank', 'transverse_abdominis', 'primary', 50 UNION ALL
    SELECT 'front_plank', 'rectus_abdominis', 'primary', 35 UNION ALL
    SELECT 'front_plank', 'obliques', 'secondary', 15 UNION ALL
    SELECT 'russian_twist', 'obliques', 'primary', 80 UNION ALL
    SELECT 'russian_twist', 'rectus_abdominis', 'secondary', 20 UNION ALL
    SELECT 'hanging_leg_raise', 'rectus_abdominis', 'primary', 75 UNION ALL
    SELECT 'hanging_leg_raise', 'obliques', 'secondary', 15 UNION ALL
    SELECT 'hanging_leg_raise', 'forearm_flexors', 'stabilizer', 10 UNION ALL
    SELECT 'ab_wheel_rollout', 'rectus_abdominis', 'primary', 55 UNION ALL
    SELECT 'ab_wheel_rollout', 'transverse_abdominis', 'primary', 25 UNION ALL
    SELECT 'ab_wheel_rollout', 'obliques', 'secondary', 15 UNION ALL
    SELECT 'ab_wheel_rollout', 'latissimus_dorsi', 'stabilizer', 5 UNION ALL
    SELECT 'cable_woodchopper', 'obliques', 'primary', 65 UNION ALL
    SELECT 'cable_woodchopper', 'rectus_abdominis', 'secondary', 20 UNION ALL
    SELECT 'cable_woodchopper', 'transverse_abdominis', 'secondary', 10 UNION ALL
    SELECT 'cable_woodchopper', 'latissimus_dorsi', 'stabilizer', 5 UNION ALL
    SELECT 'reverse_crunch', 'rectus_abdominis', 'primary', 80 UNION ALL
    SELECT 'reverse_crunch', 'obliques', 'secondary', 20
) x
JOIN exercises e ON e.slug = x.exercise_slug
JOIN muscles   m ON m.slug = x.muscle_slug;

-- ----------------------------------------------------
-- EXERCISE_EQUIPMENT (via JOIN su slug)
-- ----------------------------------------------------
INSERT INTO exercise_equipment (exercise_id, equipment_id)
SELECT e.id, eq.id FROM (
    -- Petto
    SELECT 'barbell_bench_press' AS exercise_slug, 'barbell' AS equipment_slug UNION ALL
    SELECT 'barbell_bench_press', 'bench' UNION ALL
    SELECT 'dumbbell_bench_press', 'dumbbell' UNION ALL
    SELECT 'dumbbell_bench_press', 'bench' UNION ALL
    SELECT 'incline_barbell_bench_press', 'barbell' UNION ALL
    SELECT 'incline_barbell_bench_press', 'bench' UNION ALL
    SELECT 'incline_dumbbell_bench_press', 'dumbbell' UNION ALL
    SELECT 'incline_dumbbell_bench_press', 'bench' UNION ALL
    SELECT 'decline_barbell_bench_press', 'barbell' UNION ALL
    SELECT 'decline_barbell_bench_press', 'bench' UNION ALL
    SELECT 'cable_chest_fly', 'cable' UNION ALL
    SELECT 'pec_deck_machine', 'machine' UNION ALL
    SELECT 'chest_dips', 'dip_bar' UNION ALL
    SELECT 'chest_dips', 'bodyweight' UNION ALL
    SELECT 'machine_chest_press', 'machine' UNION ALL
    SELECT 'smith_incline_press', 'smith_machine' UNION ALL
    SELECT 'smith_incline_press', 'bench' UNION ALL
    SELECT 'push_up', 'bodyweight' UNION ALL
    -- Schiena
    SELECT 'conventional_deadlift', 'barbell' UNION ALL
    SELECT 'romanian_deadlift', 'barbell' UNION ALL
    SELECT 'pull_up_pronated', 'pull_up_bar' UNION ALL
    SELECT 'pull_up_pronated', 'bodyweight' UNION ALL
    SELECT 'chin_up_supinated', 'pull_up_bar' UNION ALL
    SELECT 'chin_up_supinated', 'bodyweight' UNION ALL
    SELECT 'lat_pulldown_front', 'cable' UNION ALL
    SELECT 'seated_cable_row', 'cable' UNION ALL
    SELECT 'barbell_row', 'barbell' UNION ALL
    SELECT 'one_arm_dumbbell_row', 'dumbbell' UNION ALL
    SELECT 'one_arm_dumbbell_row', 'bench' UNION ALL
    SELECT 't_bar_row', 'plate_loaded' UNION ALL
    SELECT 'cable_pullover', 'cable' UNION ALL
    SELECT 'chest_supported_row', 'dumbbell' UNION ALL
    SELECT 'chest_supported_row', 'bench' UNION ALL
    SELECT 'machine_row', 'machine' UNION ALL
    SELECT 'straight_arm_pulldown', 'cable' UNION ALL
    SELECT 'rack_pull', 'barbell' UNION ALL
    -- Spalle
    SELECT 'overhead_press_standing', 'barbell' UNION ALL
    SELECT 'seated_dumbbell_press', 'dumbbell' UNION ALL
    SELECT 'seated_dumbbell_press', 'bench' UNION ALL
    SELECT 'arnold_press', 'dumbbell' UNION ALL
    SELECT 'arnold_press', 'bench' UNION ALL
    SELECT 'dumbbell_lateral_raise', 'dumbbell' UNION ALL
    SELECT 'cable_lateral_raise', 'cable' UNION ALL
    SELECT 'reverse_pec_deck', 'machine' UNION ALL
    SELECT 'bent_over_rear_delt_raise', 'dumbbell' UNION ALL
    SELECT 'upright_row', 'barbell' UNION ALL
    SELECT 'cable_rear_delt_fly', 'cable' UNION ALL
    SELECT 'machine_lateral_raise', 'machine' UNION ALL
    -- Bicipiti
    SELECT 'barbell_curl', 'barbell' UNION ALL
    SELECT 'alternating_dumbbell_curl', 'dumbbell' UNION ALL
    SELECT 'scott_curl', 'barbell' UNION ALL
    SELECT 'scott_curl', 'bench' UNION ALL
    SELECT 'hammer_curl', 'dumbbell' UNION ALL
    SELECT 'cable_curl', 'cable' UNION ALL
    SELECT 'concentration_curl', 'dumbbell' UNION ALL
    SELECT 'concentration_curl', 'bench' UNION ALL
    SELECT 'incline_dumbbell_curl', 'dumbbell' UNION ALL
    SELECT 'incline_dumbbell_curl', 'bench' UNION ALL
    SELECT 'bayesian_curl', 'cable' UNION ALL
    -- Tricipiti
    SELECT 'ez_bar_french_press', 'barbell' UNION ALL
    SELECT 'ez_bar_french_press', 'bench' UNION ALL
    SELECT 'cable_pushdown_straight', 'cable' UNION ALL
    SELECT 'cable_pushdown_rope', 'cable' UNION ALL
    SELECT 'triceps_dips', 'dip_bar' UNION ALL
    SELECT 'triceps_dips', 'bodyweight' UNION ALL
    SELECT 'skullcrusher', 'barbell' UNION ALL
    SELECT 'skullcrusher', 'bench' UNION ALL
    SELECT 'close_grip_bench_press', 'barbell' UNION ALL
    SELECT 'close_grip_bench_press', 'bench' UNION ALL
    SELECT 'overhead_cable_extension', 'cable' UNION ALL
    SELECT 'single_arm_cable_pushdown', 'cable' UNION ALL
    SELECT 'jm_press', 'barbell' UNION ALL
    SELECT 'jm_press', 'bench' UNION ALL
    -- Gambe
    SELECT 'back_squat_high_bar', 'barbell' UNION ALL
    SELECT 'front_squat', 'barbell' UNION ALL
    SELECT 'hack_squat_machine', 'machine' UNION ALL
    SELECT 'leg_press_45', 'machine' UNION ALL
    SELECT 'barbell_lunge', 'barbell' UNION ALL
    SELECT 'bulgarian_split_squat', 'dumbbell' UNION ALL
    SELECT 'bulgarian_split_squat', 'bench' UNION ALL
    SELECT 'leg_extension', 'machine' UNION ALL
    SELECT 'lying_leg_curl', 'machine' UNION ALL
    SELECT 'seated_leg_curl', 'machine' UNION ALL
    SELECT 'barbell_hip_thrust', 'barbell' UNION ALL
    SELECT 'barbell_hip_thrust', 'bench' UNION ALL
    SELECT 'good_morning', 'barbell' UNION ALL
    SELECT 'glute_abduction_machine', 'machine' UNION ALL
    SELECT 'smith_squat', 'smith_machine' UNION ALL
    SELECT 'pendulum_squat', 'plate_loaded' UNION ALL
    SELECT 'sissy_squat', 'bodyweight' UNION ALL
    SELECT 'walking_lunge', 'dumbbell' UNION ALL
    SELECT 'hyperextension_45', 'hyperextension' UNION ALL
    SELECT 'adductor_machine', 'machine' UNION ALL
    -- Polpacci
    SELECT 'standing_calf_raise', 'machine' UNION ALL
    SELECT 'seated_calf_raise', 'machine' UNION ALL
    SELECT 'donkey_calf_raise', 'machine' UNION ALL
    -- Trapezio
    SELECT 'dumbbell_shrug', 'dumbbell' UNION ALL
    SELECT 'cable_face_pull', 'cable' UNION ALL
    -- Addome
    SELECT 'floor_crunch', 'bodyweight' UNION ALL
    SELECT 'cable_kneeling_crunch', 'cable' UNION ALL
    SELECT 'front_plank', 'bodyweight' UNION ALL
    SELECT 'russian_twist', 'bodyweight' UNION ALL
    SELECT 'hanging_leg_raise', 'pull_up_bar' UNION ALL
    SELECT 'hanging_leg_raise', 'bodyweight' UNION ALL
    SELECT 'ab_wheel_rollout', 'ab_wheel' UNION ALL
    SELECT 'ab_wheel_rollout', 'bodyweight' UNION ALL
    SELECT 'cable_woodchopper', 'cable' UNION ALL
    SELECT 'reverse_crunch', 'bodyweight'
) x
JOIN exercises e  ON e.slug  = x.exercise_slug
JOIN equipment eq ON eq.slug = x.equipment_slug;

-- ----------------------------------------------------
-- Verifica conteggi attesi:
--   movement_patterns:  27 righe
--   muscles:            26 righe
--   equipment:          14 righe
--   exercises:          83 righe
--   exercise_muscle:    259 righe
--   exercise_equipment: 108 righe
-- ----------------------------------------------------
```

---

## Note finali

L'uso degli `INSERT ... SELECT` con JOIN su slug evita di hardcodare gli id auto-increment, che cambiano se rigeneri il DB. Lo stesso file SQL può essere rieseguito dopo `truncate` senza fix manuali, a patto di rispettare l'ordine (lookup prima, pivot dopo).

I `contribution_pct` sono volutamente conservativi rispetto a quelli che potresti trovare in singoli studi EMG. Sono pensati per essere usati come pesi nel computo del volume settimanale per muscolo: se metti un curl bilanciere come 80% bicipite + 15% brachiale + 5% brachioradiale, un set effettivo conta come ~0.8 hard sets per il bicipite.

Quando arriveremo allo Step 1 e poi al workout builder dello Step 2, il trainer avrà un'interfaccia per gestire questo catalogo (aggiungere esercizi, modificare contributi, marcare esercizi come "favoriti per il box"). Il seed qui sopra è solo lo state iniziale, non la fonte definitiva.
