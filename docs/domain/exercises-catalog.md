# iron-gym · Catalogo esercizi iniziale

> **Stato:** draft v0.3
> **Riferimento dominio:** vedi `step-0-discovery.md` (v0.3)
> **Scope:** catalogo seed di 83 esercizi BB-oriented da caricare al boot del sistema. Include classificazione `compound_pattern` / `joint_action` (mutuamente esclusivi via CHECK XOR a livello DB) e `contribution_pct` per `exercise_muscle`.

## Changelog

**v0.4** — Aggiunta colonna `execution_description` (TEXT nullable) a tutti i 83 esercizi. Testi aggiunti in `.claude/docs/domain/exercises-catalog.md` e come blocco UPDATE in `database/seeders/sql/exercises_seed.sql`. Seeder PHP `ExerciseDescriptionSeeder` applica gli stessi testi al DB MySQL. File `iron_gym_esercizi_descrizioni.xlsx` rimosso (ridondante). Script `build_exercises_sqlite.py` aggiornato: legge solo il SQL, nessuna dipendenza esterna.

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

**Esecuzione**: Sdraiato sulla panca, impugnatura leggermente più larga delle spalle. Abbassa il bilanciere al petto toccan­og la zona sternale inferiore, poi spingi verso l'alto in traiettoria leggermente obliqua verso il rack. Scapole retratte e depresse per tutto il movimento.

| Muscolo | Ruolo | % |
|---|---|---|
| pectoralis_major_sternal | primary | 55 |
| pectoralis_major_clavicular | primary | 14 |
| triceps_brachii | secondary | 17 |
| deltoid_anterior | secondary | 14 |

**Panca piana con manubri** · `dumbbell_bench_press` · *cp:* horizontal_push · compound · sagittal · bilateral · intermediate · reps_weight · equipment: dumbbell, bench

**Esecuzione**: Sdraiato sulla panca, manubri ai lati del petto con i gomiti a circa 75°. Abbassa controllando la discesa fino a sentire stiramento pettorale, poi spingi verso l'alto e leggermente verso il centro senza bloccare i gomiti in cima.

| Muscolo | Ruolo | % |
|---|---|---|
| pectoralis_major_sternal | primary | 59 |
| pectoralis_major_clavicular | primary | 14 |
| triceps_brachii | secondary | 14 |
| deltoid_anterior | secondary | 13 |

**Panca inclinata con bilanciere** · `incline_barbell_bench_press` · *cp:* horizontal_push · compound · sagittal · bilateral · intermediate · reps_weight · equipment: barbell, bench

**Esecuzione**: Panca inclinata a 30-45°. Impugnatura come per la piana. Abbassa il bilanciere verso la parte alta del petto/clavicola, spingi verso l'alto e leggermente dietro la testa. Il deltoide anteriore lavora più della versione piana.

| Muscolo | Ruolo | % |
|---|---|---|
| pectoralis_major_clavicular | primary | 50 |
| pectoralis_major_sternal | secondary | 17 |
| deltoid_anterior | secondary | 21 |
| triceps_brachii | secondary | 12 |

**Panca inclinata con manubri** · `incline_dumbbell_bench_press` · *cp:* horizontal_push · compound · sagittal · bilateral · intermediate · reps_weight · equipment: dumbbell, bench

**Esecuzione**: Panca inclinata a 30-45°. Partenza con manubri in alto, abbassa aprendo i gomiti fino a sentire stiramento nella parte alta del petto, poi spingi verso l'alto convergendo leggermente le mani.

| Muscolo | Ruolo | % |
|---|---|---|
| pectoralis_major_clavicular | primary | 50 |
| pectoralis_major_sternal | secondary | 17 |
| deltoid_anterior | secondary | 21 |
| triceps_brachii | secondary | 12 |

**Panca declinata con bilanciere** · `decline_barbell_bench_press` · *cp:* horizontal_push · compound · sagittal · bilateral · intermediate · reps_weight · equipment: barbell, bench

**Esecuzione**: Panca declinata (piedi in alto). Abbassa il bilanciere verso la parte bassa del petto/addome superiore. Traiettoria più verticale rispetto alla piana. Isola maggiormente il pettorale sternale inferiore.

| Muscolo | Ruolo | % |
|---|---|---|
| pectoralis_major_sternal | primary | 71 |
| triceps_brachii | secondary | 19 |
| deltoid_anterior | secondary | 10 |

**Croci ai cavi** · `cable_chest_fly` · *ja:* shoulder_horizontal_adduction · isolation · transverse · bilateral · intermediate · reps_weight · equipment: cable

**Esecuzione**: In piedi tra i due cavi alti. Braccia leggermente flesse con gomiti fissi. Porta le mani verso il basso e al centro in un arco ampio, contraendo il pettorale in chiusura. Ritorna lentamente al punto di partenza controllando lo stiramento.

| Muscolo | Ruolo | % |
|---|---|---|
| pectoralis_major_sternal | primary | 80 |
| pectoralis_major_clavicular | primary | 15 |
| deltoid_anterior | stabilizer | 5 |

**Pectoral machine (peck deck)** · `pec_deck_machine` · *ja:* shoulder_horizontal_adduction · isolation · transverse · bilateral · beginner · reps_weight · equipment: machine

**Esecuzione**: Seduto alla macchina, gomiti appoggiati ai cuscinetti all'altezza delle spalle. Chiudi le braccia verso il centro senza strappare, contraendo il petto al massimo. Ritorna lentamente senza perdere tensione.

| Muscolo | Ruolo | % |
|---|---|---|
| pectoralis_major_sternal | primary | 85 |
| pectoralis_major_clavicular | primary | 10 |
| deltoid_anterior | stabilizer | 5 |

**Dip alle parallele per pettorali** · `chest_dips` · *cp:* vertical_push · compound · sagittal · bilateral · advanced · reps_weight · equipment: dip_bar, bodyweight

**Esecuzione**: Alle parallele, busto inclinato in avanti di circa 30°. Abbassa il corpo fino a sentire stiramento nel petto (gomiti verso l'esterno), poi spingi verso l'alto mantenendo l'inclinazione. La verticalità del busto sposta il lavoro sui tricipiti.

| Muscolo | Ruolo | % |
|---|---|---|
| pectoralis_major_sternal | primary | 55 |
| triceps_brachii | primary | 30 |
| deltoid_anterior | secondary | 15 |

**Chest press alla macchina** · `machine_chest_press` · *cp:* horizontal_push · compound · sagittal · bilateral · beginner · reps_weight · equipment: machine

**Esecuzione**: Seduto alla macchina con schiena appoggiata allo schienale. Spingi le maniglie in avanti in modo controllato, estendi quasi completamente le braccia, poi ritorna lentamente senza far tornare il peso a battuta.

| Muscolo | Ruolo | % |
|---|---|---|
| pectoralis_major_sternal | primary | 64 |
| pectoralis_major_clavicular | primary | 14 |
| triceps_brachii | secondary | 13 |
| deltoid_anterior | secondary | 9 |

**Spinte inclinate allo Smith** · `smith_incline_press` · *cp:* horizontal_push · compound · sagittal · bilateral · intermediate · reps_weight · equipment: smith_machine, bench

**Esecuzione**: Panca inclinata posizionata sotto lo Smith Machine. Sblocca il bilanciere e abbassalo verso la parte alta del petto. Lo Smith elimina la componente di equilibrio ma fissa la traiettoria: assicurati che corrisponda alla tua biomeccanica.

| Muscolo | Ruolo | % |
|---|---|---|
| pectoralis_major_clavicular | primary | 50 |
| pectoralis_major_sternal | secondary | 17 |
| deltoid_anterior | secondary | 21 |
| triceps_brachii | secondary | 12 |

**Piegamenti sulle braccia** · `push_up` · *cp:* horizontal_push · compound · sagittal · bilateral · beginner · reps_only · equipment: bodyweight

**Esecuzione**: Mani a terra leggermente più larghe delle spalle, corpo in linea retta dai talloni alla testa. Abbassa il petto verso il suolo mantenendo il core contratto, poi spingi verso l'alto. La larghezza dell'impugnatura determina l'enfasi su petto o tricipiti.

| Muscolo | Ruolo | % |
|---|---|---|
| pectoralis_major_sternal | primary | 48 |
| pectoralis_major_clavicular | primary | 14 |
| triceps_brachii | secondary | 19 |
| deltoid_anterior | secondary | 14 |
| transverse_abdominis | stabilizer | 5 |

---

### Schiena (14 esercizi)

**Stacco da terra convenzionale** · `conventional_deadlift` · *cp:* hinge · compound · sagittal · bilateral · advanced · reps_weight · equipment: barbell

**Esecuzione**: Piedi alla larghezza dei fianchi, bilanciere sui piedi. Prendi la presa con mani fuori le gambe. Schiena neutra, petto alto. Spingi il pavimento via dai piedi mantenendo il bilanciere aderente alle gambe durante tutta la salita. In cima, anca e ginocchio completamente estesi.

| Muscolo | Ruolo | % |
|---|---|---|
| hamstrings | primary | 30 |
| gluteus_maximus | primary | 25 |
| erector_spinae | primary | 25 |
| latissimus_dorsi | secondary | 10 |
| trapezius_middle | secondary | 5 |
| forearm_flexors | stabilizer | 5 |

**Stacco rumeno (RDL)** · `romanian_deadlift` · *cp:* hinge · compound · sagittal · bilateral · intermediate · reps_weight · equipment: barbell

**Esecuzione**: Partenza in piedi con bilanciere in mano. Cerniera sull'anca mantenendo la schiena neutra: abbassa il bilanciere lungo le gambe fino a sentire forte stiramento negli ischiocrurali (tipicamente sotto il ginocchio), poi torna su spingendo l'anca in avanti.

| Muscolo | Ruolo | % |
|---|---|---|
| hamstrings | primary | 55 |
| gluteus_maximus | primary | 30 |
| erector_spinae | secondary | 15 |

**Trazioni alla sbarra prone** · `pull_up_pronated` · *cp:* vertical_pull · compound · sagittal · bilateral · advanced · reps_weight · equipment: pull_up_bar, bodyweight

**Esecuzione**: Presa prona larga, braccia tese come punto di partenza. Tira il petto verso la sbarra portando i gomiti verso il basso e indietro. Contraendo il dorsale in cima, poi scendi lentamente fino a distensione completa.

| Muscolo | Ruolo | % |
|---|---|---|
| latissimus_dorsi | primary | 62 |
| biceps_brachii | secondary | 14 |
| trapezius_middle | secondary | 10 |
| rhomboids | secondary | 9 |
| brachialis | secondary | 5 |

**Trazioni supinate (chin-up)** · `chin_up_supinated` · *cp:* vertical_pull · compound · sagittal · bilateral · intermediate · reps_weight · equipment: pull_up_bar, bodyweight

**Esecuzione**: Presa supina alla larghezza delle spalle. Tira verso l'alto portando il mento sopra la sbarra. La presa supina coinvolge maggiormente il bicipite rispetto alla presa prona. Scendi lentamente a braccia quasi tese.

| Muscolo | Ruolo | % |
|---|---|---|
| latissimus_dorsi | primary | 55 |
| biceps_brachii | primary | 25 |
| brachialis | secondary | 10 |
| trapezius_middle | secondary | 10 |

**Lat machine avanti** · `lat_pulldown_front` · *cp:* vertical_pull · compound · sagittal · bilateral · beginner · reps_weight · equipment: cable

**Esecuzione**: Seduto alla lat machine, presa larga prona. Tira la barra verso il petto superiore portando i gomiti verso il basso e dietro. Non oscillare col busto. Contraendo il dorsale in basso, poi risali lentamente controllando.

| Muscolo | Ruolo | % |
|---|---|---|
| latissimus_dorsi | primary | 65 |
| biceps_brachii | secondary | 15 |
| trapezius_middle | secondary | 10 |
| rhomboids | secondary | 10 |

**Pulley basso (seated cable row)** · `seated_cable_row` · *cp:* horizontal_pull · compound · sagittal · bilateral · beginner · reps_weight · equipment: cable

**Esecuzione**: Seduto con piedi sui poggiapiedi, schiena neutra. Tira la maniglia verso l'addome portando i gomiti indietro e le scapole a stringersi. Petto fuori per tutto il movimento. Ritorna lentamente con distensione controllata delle braccia.

| Muscolo | Ruolo | % |
|---|---|---|
| latissimus_dorsi | primary | 35 |
| trapezius_middle | primary | 25 |
| rhomboids | primary | 25 |
| biceps_brachii | secondary | 15 |

**Rematore con bilanciere** · `barbell_row` · *cp:* horizontal_pull · compound · sagittal · bilateral · intermediate · reps_weight · equipment: barbell

**Esecuzione**: Busto inclinato a circa 45° con schiena neutra, bilanciere in presa prona. Tira verso l'ombelico portando i gomiti dietro il tronco. Non usare il rimbalzo della schiena per sollevare. Scapole che si stringono al top del movimento.

| Muscolo | Ruolo | % |
|---|---|---|
| latissimus_dorsi | primary | 36 |
| trapezius_middle | primary | 23 |
| rhomboids | primary | 18 |
| biceps_brachii | secondary | 14 |
| erector_spinae | stabilizer | 9 |

**Rematore con manubrio (one-arm)** · `one_arm_dumbbell_row` · *cp:* horizontal_pull · compound · sagittal · unilateral_isolated · intermediate · reps_weight · equipment: dumbbell, bench

**Esecuzione**: Un ginocchio e una mano appoggiate sulla panca. Tira il manubrio verso l'anca (non verso la spalla) portando il gomito indietro e alto. Mantieni il tronco parallelo al suolo. Un set per lato.

| Muscolo | Ruolo | % |
|---|---|---|
| latissimus_dorsi | primary | 50 |
| trapezius_middle | primary | 20 |
| rhomboids | secondary | 15 |
| biceps_brachii | secondary | 15 |

**T-bar row** · `t_bar_row` · *cp:* horizontal_pull · compound · sagittal · bilateral · intermediate · reps_weight · equipment: plate_loaded

**Esecuzione**: Busto inclinato, impugnatura sulla barra T o triangolo al cavo. Tira verso il petto portando i gomiti indietro. Il range di movimento spesso è minore del rematore con bilanciere ma permette carichi elevati con buona tenuta lombare.

| Muscolo | Ruolo | % |
|---|---|---|
| latissimus_dorsi | primary | 40 |
| trapezius_middle | primary | 25 |
| rhomboids | primary | 20 |
| biceps_brachii | secondary | 15 |

**Pullover ai cavi (alto)** · `cable_pullover` · *ja:* shoulder_extension · isolation · sagittal · bilateral · intermediate · reps_weight · equipment: cable

**Esecuzione**: In piedi di fronte al cavo alto, maniglie prese sopra la testa con gomiti leggermente flessi. Porta le mani verso i fianchi in un arco, mantenendo i gomiti fissi. Isola il dorsale in modo eccellente; utile come esercizio di connessione mente-muscolo.

| Muscolo | Ruolo | % |
|---|---|---|
| latissimus_dorsi | primary | 85 |
| trapezius_lower | secondary | 10 |
| triceps_brachii | stabilizer | 5 |

**Rematore con appoggio al petto** · `chest_supported_row` · *cp:* horizontal_pull · compound · sagittal · bilateral · beginner · reps_weight · equipment: dumbbell, bench

**Esecuzione**: Busto appoggiato su una panca inclinata, manubri che pendono. Tira i manubri verso i fianchi portando i gomiti indietro. L'appoggio al petto elimina la compensazione lombare e isola meglio la schiena alta.

| Muscolo | Ruolo | % |
|---|---|---|
| latissimus_dorsi | primary | 40 |
| trapezius_middle | primary | 25 |
| rhomboids | primary | 20 |
| biceps_brachii | secondary | 15 |

**Rematore alla macchina** · `machine_row` · *cp:* horizontal_pull · compound · sagittal · bilateral · beginner · reps_weight · equipment: machine

**Esecuzione**: Seduto alla macchina con petto sul supporto. Tira le maniglie verso di te portando i gomiti indietro. Macchina guidata: ideale per principianti o come finisher ad alta rep con stretto controllo.

| Muscolo | Ruolo | % |
|---|---|---|
| latissimus_dorsi | primary | 40 |
| trapezius_middle | primary | 25 |
| rhomboids | primary | 20 |
| biceps_brachii | secondary | 15 |

**Pulldown a braccia tese** · `straight_arm_pulldown` · *ja:* shoulder_extension · isolation · sagittal · bilateral · intermediate · reps_weight · equipment: cable

**Esecuzione**: In piedi di fronte al cavo alto, barra presa con braccia quasi tese. Mantieni i gomiti fissi e porta la barra verso le cosce descrivendo un arco ampio. Isola il dorsale quasi esclusivamente, ottimo per la connessione mente-muscolo.

| Muscolo | Ruolo | % |
|---|---|---|
| latissimus_dorsi | primary | 85 |
| trapezius_lower | secondary | 10 |
| rectus_abdominis | stabilizer | 5 |

**Stacco dai blocchi (rack pull)** · `rack_pull` · *cp:* hinge · compound · sagittal · bilateral · advanced · reps_weight · equipment: barbell

**Esecuzione**: Bilanciere su blocchi o rack a metà stinco. Esegui come lo stacco convenzionale ma con range ridotto. Permette carichi superiori allo stacco completo; enfatizza erettori spinali, glutei e upper back nella fase di lock-out.

| Muscolo | Ruolo | % |
|---|---|---|
| erector_spinae | primary | 33 |
| gluteus_maximus | primary | 24 |
| hamstrings | primary | 19 |
| trapezius_middle | secondary | 10 |
| latissimus_dorsi | secondary | 9 |
| forearm_flexors | stabilizer | 5 |

---

### Spalle (10 esercizi)

**Military press in piedi (OHP)** · `overhead_press_standing` · *cp:* vertical_push · compound · sagittal · bilateral · advanced · reps_weight · equipment: barbell

**Esecuzione**: In piedi, bilanciere a livello del mento presa leggermente più larga delle spalle. Spingi verticalmente sopra la testa estendendo le braccia, poi abbassa controllato al mento. Core contratto per proteggere la lombare. La traiettoria è leggermente dietro la testa in cima.

| Muscolo | Ruolo | % |
|---|---|---|
| deltoid_anterior | primary | 48 |
| deltoid_lateral | primary | 17 |
| triceps_brachii | secondary | 17 |
| trapezius_upper | stabilizer | 9 |
| erector_spinae | stabilizer | 9 |

**Lento avanti con manubri (seduto)** · `seated_dumbbell_press` · *cp:* vertical_push · compound · sagittal · bilateral · intermediate · reps_weight · equipment: dumbbell, bench

**Esecuzione**: Seduto con schiena supportata, manubri all'altezza delle orecchie. Spingi verso l'alto fino a quasi estendere le braccia, poi abbassa lentamente. Rispetto al bilanciere permette un range più libero e meno stress sull'articolazione AC.

| Muscolo | Ruolo | % |
|---|---|---|
| deltoid_anterior | primary | 48 |
| deltoid_lateral | primary | 24 |
| triceps_brachii | secondary | 19 |
| trapezius_upper | stabilizer | 9 |

**Arnold press** · `arnold_press` · *cp:* vertical_push · compound · multiplanar · bilateral · intermediate · reps_weight · equipment: dumbbell, bench

**Esecuzione**: Partenza con manubri davanti al viso, palme verso di te (come la cima di un curl). Ruota le mani verso l'esterno man mano che sali, fino ad arrivare con palme in avanti in cima. Coinvolge tutto il deltoide grazie alla rotazione.

| Muscolo | Ruolo | % |
|---|---|---|
| deltoid_anterior | primary | 48 |
| deltoid_lateral | primary | 29 |
| triceps_brachii | secondary | 14 |
| trapezius_upper | stabilizer | 9 |

**Alzate laterali con manubri** · `dumbbell_lateral_raise` · *ja:* shoulder_abduction · isolation · frontal · bilateral · beginner · reps_weight · equipment: dumbbell

**Esecuzione**: In piedi o seduto, manubri ai fianchi. Alza le braccia lateralmente con gomiti leggermente flessi fino all'altezza delle spalle (non oltre). Il mignolo può essere leggermente più alto del pollice ('versa il bicchiere'). Abbassa lentamente.

| Muscolo | Ruolo | % |
|---|---|---|
| deltoid_lateral | primary | 90 |
| deltoid_anterior | secondary | 10 |

**Alzate laterali ai cavi** · `cable_lateral_raise` · *ja:* shoulder_abduction · isolation · frontal · unilateral_isolated · intermediate · reps_weight · equipment: cable

**Esecuzione**: Cavo basso sul lato opposto al braccio di lavoro. Alza il braccio lateralmente fino all'altezza della spalla. Il cavo mantiene tensione costante anche nella fase bassa, a differenza del manubrio. Un lato alla volta.

| Muscolo | Ruolo | % |
|---|---|---|
| deltoid_lateral | primary | 95 |
| deltoid_anterior | secondary | 5 |

**Reverse pec deck** · `reverse_pec_deck` · *ja:* shoulder_horizontal_abduction · isolation · transverse · bilateral · beginner · reps_weight · equipment: machine

**Esecuzione**: Seduto alla pec deck al contrario (petto verso il supporto) o con le braccia che aprono invece di chiudersi. Apri le braccia verso l'esterno mantenendo i gomiti leggermente flessi. Contrai il deltoide posteriore e i romboidi in apertura.

| Muscolo | Ruolo | % |
|---|---|---|
| deltoid_posterior | primary | 75 |
| rhomboids | secondary | 15 |
| trapezius_middle | secondary | 10 |

**Alzate posteriori a busto in avanti** · `bent_over_rear_delt_raise` · *ja:* shoulder_horizontal_abduction · isolation · transverse · bilateral · intermediate · reps_weight · equipment: dumbbell

**Esecuzione**: Busto inclinato in avanti di circa 70-90°, manubri che pendono. Alza le braccia lateralmente verso il soffitto con gomiti leggermente flessi. Evita di ruotare le spalle o usare il trapezio superiore.

| Muscolo | Ruolo | % |
|---|---|---|
| deltoid_posterior | primary | 75 |
| rhomboids | secondary | 15 |
| trapezius_middle | secondary | 10 |

**Tirata al mento (upright row)** · `upright_row` · *cp:* vertical_pull · compound · frontal · bilateral · intermediate · reps_weight · equipment: barbell

**Esecuzione**: Bilanciere in presa prona stretta davanti alle cosce. Tira verso il mento portando i gomiti in alto e verso l'esterno. Attenzione: presa troppo stretta può causare impingement di spalla. Fermati quando i gomiti raggiungono l'altezza delle spalle.

| Muscolo | Ruolo | % |
|---|---|---|
| deltoid_lateral | primary | 50 |
| trapezius_upper | primary | 25 |
| deltoid_anterior | secondary | 15 |
| biceps_brachii | secondary | 10 |

**Croci posteriori ai cavi** · `cable_rear_delt_fly` · *ja:* shoulder_horizontal_abduction · isolation · transverse · bilateral · intermediate · reps_weight · equipment: cable

**Esecuzione**: Due cavi alti incrociati. Parti con le mani incrociate al centro e apri le braccia verso l'esterno e leggermente il basso, come per abbracciare un cerchio grande. Isola il deltoide posteriore con tensione costante dal cavo.

| Muscolo | Ruolo | % |
|---|---|---|
| deltoid_posterior | primary | 75 |
| rhomboids | secondary | 15 |
| trapezius_middle | secondary | 10 |

**Alzate laterali alla macchina** · `machine_lateral_raise` · *ja:* shoulder_abduction · isolation · frontal · bilateral · beginner · reps_weight · equipment: machine

**Esecuzione**: Seduto alla macchina, braccia appoggiate sui cuscinetti. Spingi lateralmente verso l'alto fino all'altezza della spalla. La macchina guida il movimento e mantiene tensione anche nella fase bassa del ROM.

| Muscolo | Ruolo | % |
|---|---|---|
| deltoid_lateral | primary | 90 |
| deltoid_anterior | secondary | 10 |

---

### Bicipiti (8 esercizi)

**Curl con bilanciere** · `barbell_curl` · *ja:* elbow_flexion · isolation · sagittal · bilateral · beginner · reps_weight · equipment: barbell

**Esecuzione**: In piedi, bilanciere in presa supina alla larghezza delle spalle. Tieni i gomiti fissi ai fianchi e porta il bilanciere verso le spalle flettendo solo l'avambraccio. Abbassa lentamente fino a quasi estensione completa.

| Muscolo | Ruolo | % |
|---|---|---|
| biceps_brachii | primary | 80 |
| brachialis | secondary | 15 |
| brachioradialis | secondary | 5 |

**Curl con manubri alternato** · `alternating_dumbbell_curl` · *ja:* elbow_flexion · isolation · sagittal · unilateral_alternating · beginner · reps_weight · equipment: dumbbell

**Esecuzione**: In piedi, manubri ai fianchi. Alza un braccio alla volta portando il manubrio verso la spalla con eventuale supinazione del polso. L'alternanza permette di concentrarsi su un braccio per volta. Gomiti fissi.

| Muscolo | Ruolo | % |
|---|---|---|
| biceps_brachii | primary | 80 |
| brachialis | secondary | 15 |
| brachioradialis | secondary | 5 |

**Curl alla panca Scott** · `scott_curl` · *ja:* elbow_flexion · isolation · sagittal · bilateral · intermediate · reps_weight · equipment: barbell, bench

**Esecuzione**: Gomiti appoggiati sul bancale inclinato della panca Scott, bilanciere o EZ-bar in mano. Estendi quasi completamente le braccia nella fase bassa, poi porta il bilanciere verso le spalle. Il supporto impedisce di usare l'inerzia.

| Muscolo | Ruolo | % |
|---|---|---|
| biceps_brachii | primary | 85 |
| brachialis | secondary | 15 |

**Hammer curl** · `hammer_curl` · *ja:* elbow_flexion · isolation · sagittal · unilateral_alternating · beginner · reps_weight · equipment: dumbbell

**Esecuzione**: In piedi, manubri ai fianchi con presa neutra (pollice in alto). Porta il manubrio verso la spalla senza ruotare il polso. Questa presa enfatizza il brachiale e il brachioradiale rispetto al bicipite.

| Muscolo | Ruolo | % |
|---|---|---|
| brachialis | primary | 50 |
| brachioradialis | primary | 30 |
| biceps_brachii | secondary | 20 |

**Curl ai cavi** · `cable_curl` · *ja:* elbow_flexion · isolation · sagittal · bilateral · beginner · reps_weight · equipment: cable

**Esecuzione**: In piedi di fronte al cavo basso, barra o maniglie in presa supina. Porta le mani verso le spalle mantenendo i gomiti fermi. Il cavo mantiene tensione nella fase bassa e alta del movimento a differenza del bilanciere.

| Muscolo | Ruolo | % |
|---|---|---|
| biceps_brachii | primary | 85 |
| brachialis | secondary | 10 |
| brachioradialis | secondary | 5 |

**Curl concentrato** · `concentration_curl` · *ja:* elbow_flexion · isolation · sagittal · unilateral_isolated · beginner · reps_weight · equipment: dumbbell, bench

**Esecuzione**: Seduto, gomito appoggiato all'interno della coscia. Porta il manubrio verso la spalla con movimento lento e controllato. Ottima connessione mente-muscolo. Non ruotare il tronco per aiutare il sollevamento.

| Muscolo | Ruolo | % |
|---|---|---|
| biceps_brachii | primary | 90 |
| brachialis | secondary | 10 |

**Curl con manubri su panca inclinata** · `incline_dumbbell_curl` · *ja:* elbow_flexion · isolation · sagittal · unilateral_alternating · intermediate · reps_weight · equipment: dumbbell, bench

**Esecuzione**: Sdraiato su panca inclinata a 45-60°, manubri che pendono liberamente. Il gomito è dietro il tronco nella fase bassa, creando maggiore stiramento del bicipite rispetto al curl standard. Porta i manubri verso le spalle.

| Muscolo | Ruolo | % |
|---|---|---|
| biceps_brachii | primary | 85 |
| brachialis | secondary | 10 |
| brachioradialis | secondary | 5 |

**Bayesian curl ai cavi** · `bayesian_curl` · *ja:* elbow_flexion · isolation · sagittal · unilateral_isolated · intermediate · reps_weight · equipment: cable

**Esecuzione**: In piedi di fronte al cavo basso con il cavo che parte da dietro. Il braccio è esteso dietro il tronco nella partenza, massimizzando lo stiramento del bicipite. Porta la mano verso la spalla mantenendo il gomito nella stessa posizione.

| Muscolo | Ruolo | % |
|---|---|---|
| biceps_brachii | primary | 90 |
| brachialis | secondary | 10 |

---

### Tricipiti (9 esercizi)

**French press con bilanciere EZ** · `ez_bar_french_press` · *ja:* elbow_extension · isolation · sagittal · bilateral · intermediate · reps_weight · equipment: barbell, bench

**Esecuzione**: Sdraiato sulla panca, bilanciere EZ sopra la testa con braccia quasi tese. Abbassa l'EZ verso la fronte o sopra la testa piegando solo i gomiti (che rimangono fissi). Estendi poi verso l'alto. Il grip EZ riduce lo stress sui polsi.

| Muscolo | Ruolo | % |
|---|---|---|
| triceps_brachii | primary | 95 |
| deltoid_posterior | stabilizer | 5 |

**Push down ai cavi (sbarra dritta)** · `cable_pushdown_straight` · *ja:* elbow_extension · isolation · sagittal · bilateral · beginner · reps_weight · equipment: cable

**Esecuzione**: In piedi di fronte al cavo alto, barra diritta in presa prona. Gomiti ai fianchi e fissi. Spingi la barra verso il basso fino a estensione completa delle braccia, poi risali lentamente fino a circa 90°. Non portare i gomiti in avanti.

| Muscolo | Ruolo | % |
|---|---|---|
| triceps_brachii | primary | 100 |

**Push down ai cavi con corda** · `cable_pushdown_rope` · *ja:* elbow_extension · isolation · sagittal · bilateral · beginner · reps_weight · equipment: cable

**Esecuzione**: Come il push down con sbarra ma con la corda. In cima puoi aprire le mani verso l'esterno per aumentare il range e la contrazione finale del tricipite. La corda permette un movimento più naturale per i polsi.

| Muscolo | Ruolo | % |
|---|---|---|
| triceps_brachii | primary | 100 |

**Dip alle parallele (tricipiti)** · `triceps_dips` · *cp:* vertical_push · compound · sagittal · bilateral · advanced · reps_weight · equipment: dip_bar, bodyweight

**Esecuzione**: Alle parallele con busto verticale (non inclinato). Abbassa il corpo piegando i gomiti verso l'esterno fino a circa 90°, poi spingi verso l'alto. La posizione verticale del busto sposta il lavoro principalmente sui tricipiti.

| Muscolo | Ruolo | % |
|---|---|---|
| triceps_brachii | primary | 55 |
| pectoralis_major_sternal | secondary | 30 |
| deltoid_anterior | secondary | 15 |

**Skullcrusher** · `skullcrusher` · *ja:* elbow_extension · isolation · sagittal · bilateral · intermediate · reps_weight · equipment: barbell, bench

**Esecuzione**: Sdraiato sulla panca, bilanciere o EZ-bar sopra la testa con braccia quasi tese. Abbassa il peso verso la fronte o sopra la testa piegando i gomiti. I gomiti devono rimanere fermi e verticali. Estendi verso l'alto in modo esplosivo.

| Muscolo | Ruolo | % |
|---|---|---|
| triceps_brachii | primary | 95 |
| deltoid_posterior | stabilizer | 5 |

**Panca stretta** · `close_grip_bench_press` · *cp:* horizontal_push · compound · sagittal · bilateral · intermediate · reps_weight · equipment: barbell, bench

**Esecuzione**: Panca piana con presa stretta (pollici a circa 30-40 cm di distanza). Abbassa il bilanciere verso il basso del petto/addome superiore, poi spingi verso l'alto. Gomiti più vicini al corpo rispetto alla panca classica per enfatizzare il tricipite.

| Muscolo | Ruolo | % |
|---|---|---|
| triceps_brachii | primary | 55 |
| pectoralis_major_sternal | primary | 25 |
| deltoid_anterior | secondary | 20 |

**Estensioni tricipiti sopra la testa ai cavi** · `overhead_cable_extension` · *ja:* elbow_extension · isolation · sagittal · bilateral · intermediate · reps_weight · equipment: cable

**Esecuzione**: Schiena al cavo alto, corda impugnata dietro la testa. Estendi i gomiti verso il basso e in avanti portando la corda davanti a te. La posizione overhead massimizza lo stiramento della testa lunga del tricipite.

| Muscolo | Ruolo | % |
|---|---|---|
| triceps_brachii | primary | 100 |

**Push down ai cavi a un braccio** · `single_arm_cable_pushdown` · *ja:* elbow_extension · isolation · sagittal · unilateral_isolated · beginner · reps_weight · equipment: cable

**Esecuzione**: Come il push down standard ma con un solo braccio e maniglia singola. Permette di correggere squilibri tra i lati e di lavorare con una presa più naturale. Un set per braccio.

| Muscolo | Ruolo | % |
|---|---|---|
| triceps_brachii | primary | 100 |

**JM press** · `jm_press` · *cp:* horizontal_push · compound · sagittal · bilateral · advanced · reps_weight · equipment: barbell, bench

**Esecuzione**: Incrocio tra panca stretta e skullcrusher. Sdraiato sulla panca, abbassa il bilanciere verso il basso del collo/clavicole lasciando che i gomiti si muovano in avanti. Movimento ibrido che enfatizza molto i tricipiti con alto carico.

| Muscolo | Ruolo | % |
|---|---|---|
| triceps_brachii | primary | 75 |
| pectoralis_major_sternal | secondary | 15 |
| deltoid_anterior | secondary | 10 |

---

### Gambe (18 esercizi)

**Squat con bilanciere (high-bar)** · `back_squat_high_bar` · *cp:* squat · compound · sagittal · bilateral · advanced · reps_weight · equipment: barbell

**Esecuzione**: Bilanciere appoggiato in posizione alta sui trapezi (non sul collo). Piedi alla larghezza delle spalle, punte leggermente aperte. Scendi mantenendo il petto alto e le ginocchia in linea con le punte fino a parallelismo o sotto. Risali spingendo il pavimento via.

| Muscolo | Ruolo | % |
|---|---|---|
| quadriceps | primary | 45 |
| gluteus_maximus | primary | 23 |
| hamstrings | secondary | 14 |
| erector_spinae | stabilizer | 9 |
| adductors | secondary | 9 |

**Front squat** · `front_squat` · *cp:* squat · compound · sagittal · bilateral · advanced · reps_weight · equipment: barbell

**Esecuzione**: Bilanciere in presa frontale (grip olimpico o cross-grip) appoggiato sulle clavicole. Gomiti alti per tenerlo in posizione. Più torso verticale rispetto al back squat, maggiore enfasi sul quadricipite. Richiede ottima mobilità di polso e caviglia.

| Muscolo | Ruolo | % |
|---|---|---|
| quadriceps | primary | 60 |
| gluteus_maximus | primary | 20 |
| hamstrings | secondary | 10 |
| erector_spinae | stabilizer | 10 |

**Hack squat machine** · `hack_squat_machine` · *cp:* squat · compound · sagittal · bilateral · intermediate · reps_weight · equipment: machine

**Esecuzione**: Schiena appoggiata alla macchina inclinata, piedi sulla pedana. Scendi flettendo le ginocchia portandole verso il petto, poi spingi verso l'alto. La pedana può essere posizionata più o meno in alto per cambiare l'enfasi muscolare.

| Muscolo | Ruolo | % |
|---|---|---|
| quadriceps | primary | 65 |
| gluteus_maximus | primary | 20 |
| hamstrings | secondary | 10 |
| adductors | secondary | 5 |

**Leg press 45°** · `leg_press_45` · *cp:* squat · compound · sagittal · bilateral · beginner · reps_weight · equipment: machine

**Esecuzione**: Seduto nella macchina, piedi sulla pedana alla larghezza delle spalle. Abbassa il peso flettendo le ginocchia verso il petto (non oltre 90°), poi spingi via. Non bloccare mai completamente le ginocchia in cima e non lasciare che il basso schiena si sollevi dalla seduta.

| Muscolo | Ruolo | % |
|---|---|---|
| quadriceps | primary | 55 |
| gluteus_maximus | primary | 25 |
| hamstrings | secondary | 15 |
| adductors | secondary | 5 |

**Affondi con bilanciere** · `barbell_lunge` · *cp:* lunge · compound · sagittal · unilateral_alternating · intermediate · reps_weight · equipment: barbell

**Esecuzione**: Bilanciere sul dorso, fai un passo avanti lungo. Abbassa il ginocchio posteriore quasi a terra mantenendo il busto verticale, poi spingi col piede anteriore per tornare alla posizione di partenza. Alterna le gambe a ogni rep.

| Muscolo | Ruolo | % |
|---|---|---|
| quadriceps | primary | 45 |
| gluteus_maximus | primary | 35 |
| hamstrings | secondary | 15 |
| adductors | secondary | 5 |

**Bulgarian split squat** · `bulgarian_split_squat` · *cp:* lunge · compound · sagittal · unilateral_isolated · intermediate · reps_weight · equipment: dumbbell, bench

**Esecuzione**: Piede posteriore appoggiato su una panca, piede anteriore avanzato. Abbassa il ginocchio posteriore verso il suolo mantenendo il busto verticale o leggermente inclinato. Poi spingi verso l'alto col piede anteriore. Un set per gamba.

| Muscolo | Ruolo | % |
|---|---|---|
| quadriceps | primary | 45 |
| gluteus_maximus | primary | 35 |
| hamstrings | secondary | 15 |
| gluteus_medius | stabilizer | 5 |

**Leg extension** · `leg_extension` · *ja:* knee_extension · isolation · sagittal · bilateral · beginner · reps_weight · equipment: machine

**Esecuzione**: Seduto alla macchina, caviglie appoggiate ai cuscinetti. Estendi le ginocchia fino a quasi completare il ROM, contraendo il quadricipite in cima. Abbassa lentamente. Evita di strappare verso l'alto o di iperestendere il ginocchio.

| Muscolo | Ruolo | % |
|---|---|---|
| quadriceps | primary | 100 |

**Leg curl sdraiato (lying)** · `lying_leg_curl` · *ja:* knee_flexion · isolation · sagittal · bilateral · beginner · reps_weight · equipment: machine

**Esecuzione**: Sdraiato a pancia in giù sulla macchina, caviglie sotto i cuscinetti. Porta i talloni verso i glutei flettendo i ginocchi, contraendo gli ischiocrurali. Abbassa lentamente. Tieni il bacino a contatto con il cuscinetto.

| Muscolo | Ruolo | % |
|---|---|---|
| hamstrings | primary | 100 |

**Leg curl seduto (seated)** · `seated_leg_curl` · *ja:* knee_flexion · isolation · sagittal · bilateral · beginner · reps_weight · equipment: machine

**Esecuzione**: Seduto alla macchina, cosce appoggiate sul cuscinetto anteriore. Porta i talloni verso il basso e indietro flettendo i ginocchi. La posizione seduta allunga l'ischiocrurale anche a livello dell'anca (origine), aumentando l'attivazione rispetto al lying.

| Muscolo | Ruolo | % |
|---|---|---|
| hamstrings | primary | 100 |

**Hip thrust con bilanciere** · `barbell_hip_thrust` · *cp:* hinge · compound · sagittal · bilateral · intermediate · reps_weight · equipment: barbell, bench

**Esecuzione**: Schiena appoggiata alla panca all'altezza delle scapole, bilanciere sul bacino con cuscinetto. Piedi piatti a terra. Spingi il bacino verso l'alto estendendo l'anca completamente. Gluteo massimamente contratto in cima. Abbassa controllato.

| Muscolo | Ruolo | % |
|---|---|---|
| gluteus_maximus | primary | 70 |
| hamstrings | secondary | 25 |
| quadriceps | stabilizer | 5 |

**Good morning** · `good_morning` · *cp:* hinge · compound · sagittal · bilateral · intermediate · reps_weight · equipment: barbell

**Esecuzione**: Bilanciere sul dorso come nello squat. Fai cerniera sull'anca abbassando il busto mantenendo la schiena neutra e le ginocchia leggermente flesse. Scendi fino a quasi parallelo al suolo, poi torna su spingendo i fianchi in avanti.

| Muscolo | Ruolo | % |
|---|---|---|
| hamstrings | primary | 50 |
| gluteus_maximus | primary | 25 |
| erector_spinae | primary | 25 |

**Abduzioni dei glutei alla macchina** · `glute_abduction_machine` · *ja:* hip_abduction · isolation · frontal · bilateral · beginner · reps_weight · equipment: machine

**Esecuzione**: Seduto alla macchina con cosce all'interno dei cuscinetti. Apri le gambe verso l'esterno contro la resistenza, poi riporta lentamente al centro. Isola il gluteo medio. Il busto può essere leggermente inclinato in avanti per aumentare l'attivazione.

| Muscolo | Ruolo | % |
|---|---|---|
| gluteus_medius | primary | 80 |
| gluteus_maximus | secondary | 20 |

**Squat allo Smith** · `smith_squat` · *cp:* squat · compound · sagittal · bilateral · beginner · reps_weight · equipment: smith_machine

**Esecuzione**: Come lo squat con bilanciere ma con la guida dello Smith Machine. La barra segue una traiettoria fissa, quindi è importante posizionare correttamente i piedi in anticipo. Adatto ai principianti o per isolare meglio il quadricipite con piedi avanzati.

| Muscolo | Ruolo | % |
|---|---|---|
| quadriceps | primary | 55 |
| gluteus_maximus | primary | 25 |
| hamstrings | secondary | 15 |
| adductors | secondary | 5 |

**Pendulum squat** · `pendulum_squat` · *cp:* squat · compound · sagittal · bilateral · intermediate · reps_weight · equipment: plate_loaded

**Esecuzione**: Macchina a piastre con baricentro oscillante (pendolo). Schiena appoggiata, piedi sulla pedana. Permette una profondità di squat eccellente con busto verticale e forte enfasi sul quadricipite. Range di movimento ampio con basso stress lombare.

| Muscolo | Ruolo | % |
|---|---|---|
| quadriceps | primary | 65 |
| gluteus_maximus | primary | 20 |
| hamstrings | secondary | 10 |
| adductors | secondary | 5 |

**Sissy squat** · `sissy_squat` · *ja:* knee_extension · isolation · sagittal · bilateral · advanced · reps_weight · equipment: bodyweight

**Esecuzione**: In piedi (con supporto), inclina il busto indietro e fletti solo le ginocchia portandole in avanti mentre sali in punta di piedi. Isola il quadricipite in modo estremo. Richiede buona mobilità e forza del core. Progressione verso versioni con carico.

| Muscolo | Ruolo | % |
|---|---|---|
| quadriceps | primary | 95 |
| rectus_abdominis | stabilizer | 5 |

**Affondi camminati** · `walking_lunge` · *cp:* lunge · compound · sagittal · unilateral_alternating · intermediate · reps_weight · equipment: dumbbell

**Esecuzione**: Come gli affondi statici ma invece di tornare indietro si avanza alternando le gambe in sequenza camminando. Ottimo per coordinazione e lavoro metabolico. Bilanciere sul dorso o manubri ai fianchi.

| Muscolo | Ruolo | % |
|---|---|---|
| quadriceps | primary | 45 |
| gluteus_maximus | primary | 35 |
| hamstrings | secondary | 15 |
| adductors | secondary | 5 |

**Hyperextension 45°** · `hyperextension_45` · *ja:* hip_extension · isolation · sagittal · bilateral · beginner · reps_weight · equipment: hyperextension

**Esecuzione**: Busto sulla panca hyperextension a 45°, piedi bloccati. Parti con il busto quasi verticale, abbassa fino ad avere la schiena parallela o leggermente sotto, poi risali contraendo glutei e ischiocrurali. Mantieni la schiena neutra (non iperestendere in cima).

| Muscolo | Ruolo | % |
|---|---|---|
| gluteus_maximus | primary | 35 |
| hamstrings | primary | 30 |
| erector_spinae | primary | 30 |
| rectus_abdominis | stabilizer | 5 |

**Adductor machine** · `adductor_machine` · *ja:* hip_adduction · isolation · frontal · bilateral · beginner · reps_weight · equipment: machine

**Esecuzione**: Seduto alla macchina con le gambe aperte sui cuscinetti laterali. Chiudi le gambe verso il centro contro la resistenza, poi riapri lentamente. Isola gli adduttori. Regola la larghezza di partenza in base alla mobilità dell'anca.

| Muscolo | Ruolo | % |
|---|---|---|
| adductors | primary | 100 |

---

### Polpacci (3 esercizi)

**Calf raise in piedi (standing)** · `standing_calf_raise` · *ja:* ankle_plantarflexion · isolation · sagittal · bilateral · beginner · reps_weight · equipment: machine

**Esecuzione**: In piedi sulla pedana della macchina, spalle sotto i cuscinetti. Scendi il tallone più in basso possibile (dorsiflexion) poi spingi sulle punte il più in alto possibile (plantarflexion). Pausa di un secondo in cima per la contrazione. Enfatizza il gastrocnemio.

| Muscolo | Ruolo | % |
|---|---|---|
| gastrocnemius | primary | 75 |
| soleus | primary | 25 |

**Calf raise seduto (seated)** · `seated_calf_raise` · *ja:* ankle_plantarflexion · isolation · sagittal · bilateral · beginner · reps_weight · equipment: machine

**Esecuzione**: Seduto alla macchina con ginocchia a 90° e cuscinetti sopra le cosce. Stessa esecuzione: scendi il tallone e poi spingi sulle punte. La posizione seduta flette il ginocchio, mettendo il gastrocnemio in posizione più corta e quindi enfatizzando il soleo.

| Muscolo | Ruolo | % |
|---|---|---|
| soleus | primary | 85 |
| gastrocnemius | secondary | 15 |

**Donkey calf raise** · `donkey_calf_raise` · *ja:* ankle_plantarflexion · isolation · sagittal · bilateral · intermediate · reps_weight · equipment: machine

**Esecuzione**: Busto inclinato in avanti (circa 90°) appoggiato al supporto, piedi sulla pedana. Esegui la plantarflessione come per il calf raise in piedi. La posizione del busto inclina il gastrocnemio verso una tensione diversa rispetto alla variante diritta.

| Muscolo | Ruolo | % |
|---|---|---|
| gastrocnemius | primary | 80 |
| soleus | secondary | 20 |

---

### Trapezio (2 esercizi)

**Scrollate con manubri** · `dumbbell_shrug` · *ja:* scapular_elevation · isolation · sagittal · bilateral · beginner · reps_weight · equipment: dumbbell

**Esecuzione**: In piedi, manubri ai fianchi con braccia tese. Alza le spalle verso le orecchie il più possibile (scrollata), poi abbassa lentamente. Non ruotare le spalle né piegare i gomiti. La fase eccentrica lenta massimizza lo stimolo sul trapezio superiore.

| Muscolo | Ruolo | % |
|---|---|---|
| trapezius_upper | primary | 90 |
| trapezius_middle | secondary | 10 |

**Face pull ai cavi** · `cable_face_pull` · *ja:* shoulder_horizontal_abduction · isolation · transverse · bilateral · intermediate · reps_weight · equipment: cable

**Esecuzione**: Cavo all'altezza degli occhi con corda. Tira verso il viso aprendo le mani verso l'esterno all'altezza della testa. I gomiti devono salire all'altezza delle spalle o più in alto. Ottimo per il deltoide posteriore, salute della cuffia dei rotatori e postura.

| Muscolo | Ruolo | % |
|---|---|---|
| deltoid_posterior | primary | 50 |
| trapezius_middle | primary | 25 |
| rhomboids | secondary | 15 |
| trapezius_lower | secondary | 10 |

---

### Addome (8 esercizi)

**Crunch a terra** · `floor_crunch` · *ja:* spinal_flexion · isolation · sagittal · bilateral · beginner · reps_only · equipment: bodyweight

**Esecuzione**: Sdraiato, ginocchia flesse, mani dietro la testa o sul petto. Solleva solo le scapole dal suolo contraendo il retto addominale, non tirare il collo con le mani. Abbassa lentamente senza posare completamente la testa.

| Muscolo | Ruolo | % |
|---|---|---|
| rectus_abdominis | primary | 90 |
| obliques | secondary | 10 |

**Crunch ai cavi (in ginocchio)** · `cable_kneeling_crunch` · *ja:* spinal_flexion · isolation · sagittal · bilateral · intermediate · reps_weight · equipment: cable

**Esecuzione**: In ginocchio di fronte al cavo alto con corda. Fletti il busto verso il basso come per un crunch, portando i gomiti verso le cosce. Non tirare con le braccia: il movimento è della schiena/addome. Torna su lentamente.

| Muscolo | Ruolo | % |
|---|---|---|
| rectus_abdominis | primary | 90 |
| obliques | secondary | 10 |

**Plank frontale** · `front_plank` · *cp:* anti_rotation · isolation · sagittal · bilateral · beginner · isometric_hold · equipment: bodyweight

**Esecuzione**: Appoggio su avambracci e punte dei piedi, corpo in linea retta. Contrai addome, glutei e quadricipiti per mantenere la posizione. Non lasciare che il bacino cada o si alzi. Respira normalmente. Mantieni la posizione per il tempo prescritto.

| Muscolo | Ruolo | % |
|---|---|---|
| transverse_abdominis | primary | 50 |
| rectus_abdominis | primary | 35 |
| obliques | secondary | 15 |

**Russian twist** · `russian_twist` · *cp:* rotation · isolation · transverse · bilateral · beginner · reps_weight · equipment: bodyweight

**Esecuzione**: Seduto con busto inclinato a circa 45°, ginocchia flesse e piedi sollevati (o a terra per variante più facile). Ruota il busto da un lato all'altro toccando il suolo (o portando il peso) ad ogni rotazione. Mantieni il core contratto.

| Muscolo | Ruolo | % |
|---|---|---|
| obliques | primary | 80 |
| rectus_abdominis | secondary | 20 |

**Leg raises alla sbarra** · `hanging_leg_raise` · *ja:* hip_flexion · compound · sagittal · bilateral · advanced · reps_only · equipment: pull_up_bar, bodyweight

**Esecuzione**: Appeso alla sbarra con presa prona. Porta le gambe tese verso l'alto fino a parallelismo o oltre senza usare l'inerzia. Abbassa lentamente. Per principianti: gambe piegate. Il controllo discendente è fondamentale per attivare il retto addominale.

| Muscolo | Ruolo | % |
|---|---|---|
| rectus_abdominis | primary | 75 |
| obliques | secondary | 15 |
| forearm_flexors | stabilizer | 10 |

**Rollout con la ruota** · `ab_wheel_rollout` · *cp:* anti_rotation · compound · sagittal · bilateral · advanced · reps_only · equipment: ab_wheel, bodyweight

**Esecuzione**: In ginocchio con la ruota a terra, braccia tese. Fai scorrere la ruota in avanti abbassando il busto verso il suolo mantenendo la schiena neutra. Torna su contraendo il core e il dorsale. Non iperestendere la lombare nella fase di allungamento.

| Muscolo | Ruolo | % |
|---|---|---|
| rectus_abdominis | primary | 55 |
| transverse_abdominis | primary | 25 |
| obliques | secondary | 15 |
| latissimus_dorsi | stabilizer | 5 |

**Wood chopper ai cavi** · `cable_woodchopper` · *cp:* rotation · compound · transverse · unilateral_isolated · intermediate · reps_weight · equipment: cable

**Esecuzione**: Cavo in alto su un lato. Tira la maniglia diagonalmente verso il basso e il lato opposto ruotando il busto. Movimento dal fianco alto al fianco basso. Le braccia restano quasi tese. Isola gli obliqui. Un set per lato.

| Muscolo | Ruolo | % |
|---|---|---|
| obliques | primary | 65 |
| rectus_abdominis | secondary | 20 |
| transverse_abdominis | secondary | 10 |
| latissimus_dorsi | stabilizer | 5 |

**Reverse crunch** · `reverse_crunch` · *ja:* spinal_flexion · isolation · sagittal · bilateral · beginner · reps_only · equipment: bodyweight

**Esecuzione**: Sdraiato, gambe a 90°. Porta il bacino verso il petto sollevando i glutei dal suolo tramite la contrazione del basso addome. Non usare l'inerzia delle gambe. Abbassa lentamente. Variante dei crunch che enfatizza la porzione inferiore del retto.

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

