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
## Dati di riferimento (SQLite)

I dati completi (lookup, esercizi, pivot muscle/equipment, descrizioni esecuzione) sono in `database/database.sqlite`. Tabelle: `movement_patterns` (27), `muscles` (26), `equipment` (14), `exercises` (83 + `execution_description`), `exercise_muscle` (259), `exercise_equipment` (108).

Query di esempio:
```sql
-- Tutti gli esercizi con muscoli primari
SELECT e.slug, e.name_it, m.slug AS muscle, em.contribution_pct
FROM exercises e
JOIN exercise_muscle em ON em.exercise_id = e.id
JOIN muscles m ON m.id = em.muscle_id
WHERE em.role = 'primary'
ORDER BY e.name_it;
```

Il file SQLite è generato dallo script `.claude/scripts/build_exercises_sqlite.py` e può essere rigenerato dopo modifiche al catalogo.

---
## Note storiche Seed SQL

Il seed originale usava `INSERT ... SELECT` con JOIN su slug per evitare id hardcodati. Stessa logica applicata al seeder PHP in `database/seeders/`. La versione SQLite è l'unica fonte di verità per il catalogo esercizi.

