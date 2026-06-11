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
    SELECT 'dumbbell_bench_press', 'Panca piana con manubri', 'horizontal_push', NULL, 'compound', 'sagittal', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'incline_barbell_bench_press', 'Panca inclinata con bilanciere', 'horizontal_push', NULL, 'compound', 'sagittal', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'incline_dumbbell_bench_press', 'Panca inclinata con manubri', 'horizontal_push', NULL, 'compound', 'sagittal', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'decline_barbell_bench_press', 'Panca declinata con bilanciere', 'horizontal_push', NULL, 'compound', 'sagittal', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'cable_chest_fly', 'Croci ai cavi', NULL, 'shoulder_horizontal_adduction', 'isolation', 'transverse', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'pec_deck_machine', 'Pectoral machine (peck deck)', NULL, 'shoulder_horizontal_adduction', 'isolation', 'transverse', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    SELECT 'chest_dips', 'Dip alle parallele per pettorali', 'vertical_push', NULL, 'compound', 'sagittal', 'bilateral', 'advanced', 'reps_weight' UNION ALL
    SELECT 'machine_chest_press', 'Chest press alla macchina', 'horizontal_push', NULL, 'compound', 'sagittal', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    SELECT 'smith_incline_press', 'Spinte inclinate allo Smith', 'horizontal_push', NULL, 'compound', 'sagittal', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'push_up', 'Piegamenti sulle braccia', 'horizontal_push', NULL, 'compound', 'sagittal', 'bilateral', 'beginner', 'reps_only' UNION ALL
    -- Schiena
    SELECT 'conventional_deadlift', 'Stacco da terra convenzionale', 'hinge', NULL, 'compound', 'sagittal', 'bilateral', 'advanced', 'reps_weight' UNION ALL
    SELECT 'romanian_deadlift', 'Stacco rumeno (RDL)', 'hinge', NULL, 'compound', 'sagittal', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'pull_up_pronated', 'Trazioni alla sbarra prone', 'vertical_pull', NULL, 'compound', 'sagittal', 'bilateral', 'advanced', 'reps_weight' UNION ALL
    SELECT 'chin_up_supinated', 'Trazioni supinate (chin-up)', 'vertical_pull', NULL, 'compound', 'sagittal', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'lat_pulldown_front', 'Lat machine avanti', 'vertical_pull', NULL, 'compound', 'sagittal', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    SELECT 'seated_cable_row', 'Pulley basso (seated cable row)', 'horizontal_pull', NULL, 'compound', 'sagittal', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    SELECT 'barbell_row', 'Rematore con bilanciere', 'horizontal_pull', NULL, 'compound', 'sagittal', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'one_arm_dumbbell_row', 'Rematore con manubrio (one-arm)', 'horizontal_pull', NULL, 'compound', 'sagittal', 'unilateral_isolated', 'intermediate', 'reps_weight' UNION ALL
    SELECT 't_bar_row', 'T-bar row', 'horizontal_pull', NULL, 'compound', 'sagittal', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'cable_pullover', 'Pullover ai cavi (alto)', NULL, 'shoulder_extension', 'isolation', 'sagittal', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'chest_supported_row', 'Rematore con appoggio al petto', 'horizontal_pull', NULL, 'compound', 'sagittal', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    SELECT 'machine_row', 'Rematore alla macchina', 'horizontal_pull', NULL, 'compound', 'sagittal', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    SELECT 'straight_arm_pulldown', 'Pulldown a braccia tese', NULL, 'shoulder_extension', 'isolation', 'sagittal', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'rack_pull', 'Stacco dai blocchi (rack pull)', 'hinge', NULL, 'compound', 'sagittal', 'bilateral', 'advanced', 'reps_weight' UNION ALL
    -- Spalle
    SELECT 'overhead_press_standing', 'Military press in piedi (OHP)', 'vertical_push', NULL, 'compound', 'sagittal', 'bilateral', 'advanced', 'reps_weight' UNION ALL
    SELECT 'seated_dumbbell_press', 'Lento avanti con manubri (seduto)', 'vertical_push', NULL, 'compound', 'sagittal', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'arnold_press', 'Arnold press', 'vertical_push', NULL, 'compound', 'multiplanar', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'dumbbell_lateral_raise', 'Alzate laterali con manubri', NULL, 'shoulder_abduction', 'isolation', 'frontal', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    SELECT 'cable_lateral_raise', 'Alzate laterali ai cavi', NULL, 'shoulder_abduction', 'isolation', 'frontal', 'unilateral_isolated', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'reverse_pec_deck', 'Reverse pec deck', NULL, 'shoulder_horizontal_abduction', 'isolation', 'transverse', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    SELECT 'bent_over_rear_delt_raise', 'Alzate posteriori a busto in avanti', NULL, 'shoulder_horizontal_abduction', 'isolation', 'transverse', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'upright_row', 'Tirata al mento (upright row)', 'vertical_pull', NULL, 'compound', 'frontal', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'cable_rear_delt_fly', 'Croci posteriori ai cavi', NULL, 'shoulder_horizontal_abduction', 'isolation', 'transverse', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'machine_lateral_raise', 'Alzate laterali alla macchina', NULL, 'shoulder_abduction', 'isolation', 'frontal', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    -- Bicipiti
    SELECT 'barbell_curl', 'Curl con bilanciere', NULL, 'elbow_flexion', 'isolation', 'sagittal', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    SELECT 'alternating_dumbbell_curl', 'Curl con manubri alternato', NULL, 'elbow_flexion', 'isolation', 'sagittal', 'unilateral_alternating', 'beginner', 'reps_weight' UNION ALL
    SELECT 'scott_curl', 'Curl alla panca Scott', NULL, 'elbow_flexion', 'isolation', 'sagittal', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'hammer_curl', 'Hammer curl', NULL, 'elbow_flexion', 'isolation', 'sagittal', 'unilateral_alternating', 'beginner', 'reps_weight' UNION ALL
    SELECT 'cable_curl', 'Curl ai cavi', NULL, 'elbow_flexion', 'isolation', 'sagittal', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    SELECT 'concentration_curl', 'Curl concentrato', NULL, 'elbow_flexion', 'isolation', 'sagittal', 'unilateral_isolated', 'beginner', 'reps_weight' UNION ALL
    SELECT 'incline_dumbbell_curl', 'Curl con manubri su panca inclinata', NULL, 'elbow_flexion', 'isolation', 'sagittal', 'unilateral_alternating', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'bayesian_curl', 'Bayesian curl ai cavi', NULL, 'elbow_flexion', 'isolation', 'sagittal', 'unilateral_isolated', 'intermediate', 'reps_weight' UNION ALL
    -- Tricipiti
    SELECT 'ez_bar_french_press', 'French press con bilanciere EZ', NULL, 'elbow_extension', 'isolation', 'sagittal', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'cable_pushdown_straight', 'Push down ai cavi (sbarra dritta)', NULL, 'elbow_extension', 'isolation', 'sagittal', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    SELECT 'cable_pushdown_rope', 'Push down ai cavi con corda', NULL, 'elbow_extension', 'isolation', 'sagittal', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    SELECT 'triceps_dips', 'Dip alle parallele (tricipiti)', 'vertical_push', NULL, 'compound', 'sagittal', 'bilateral', 'advanced', 'reps_weight' UNION ALL
    SELECT 'skullcrusher', 'Skullcrusher', NULL, 'elbow_extension', 'isolation', 'sagittal', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'close_grip_bench_press', 'Panca stretta', 'horizontal_push', NULL, 'compound', 'sagittal', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'overhead_cable_extension', 'Estensioni tricipiti sopra la testa ai cavi', NULL, 'elbow_extension', 'isolation', 'sagittal', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'single_arm_cable_pushdown', 'Push down ai cavi a un braccio', NULL, 'elbow_extension', 'isolation', 'sagittal', 'unilateral_isolated', 'beginner', 'reps_weight' UNION ALL
    SELECT 'jm_press', 'JM press', 'horizontal_push', NULL, 'compound', 'sagittal', 'bilateral', 'advanced', 'reps_weight' UNION ALL
    -- Gambe
    SELECT 'back_squat_high_bar', 'Squat con bilanciere (high-bar)', 'squat', NULL, 'compound', 'sagittal', 'bilateral', 'advanced', 'reps_weight' UNION ALL
    SELECT 'front_squat', 'Front squat', 'squat', NULL, 'compound', 'sagittal', 'bilateral', 'advanced', 'reps_weight' UNION ALL
    SELECT 'hack_squat_machine', 'Hack squat machine', 'squat', NULL, 'compound', 'sagittal', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'leg_press_45', 'Leg press 45°', 'squat', NULL, 'compound', 'sagittal', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    SELECT 'barbell_lunge', 'Affondi con bilanciere', 'lunge', NULL, 'compound', 'sagittal', 'unilateral_alternating', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'bulgarian_split_squat', 'Bulgarian split squat', 'lunge', NULL, 'compound', 'sagittal', 'unilateral_isolated', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'leg_extension', 'Leg extension', NULL, 'knee_extension', 'isolation', 'sagittal', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    SELECT 'lying_leg_curl', 'Leg curl sdraiato (lying)', NULL, 'knee_flexion', 'isolation', 'sagittal', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    SELECT 'seated_leg_curl', 'Leg curl seduto (seated)', NULL, 'knee_flexion', 'isolation', 'sagittal', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    SELECT 'barbell_hip_thrust', 'Hip thrust con bilanciere', 'hinge', NULL, 'compound', 'sagittal', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'good_morning', 'Good morning', 'hinge', NULL, 'compound', 'sagittal', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'glute_abduction_machine', 'Abduzioni dei glutei alla macchina', NULL, 'hip_abduction', 'isolation', 'frontal', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    SELECT 'smith_squat', 'Squat allo Smith', 'squat', NULL, 'compound', 'sagittal', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    SELECT 'pendulum_squat', 'Pendulum squat', 'squat', NULL, 'compound', 'sagittal', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'sissy_squat', 'Sissy squat', NULL, 'knee_extension', 'isolation', 'sagittal', 'bilateral', 'advanced', 'reps_weight' UNION ALL
    SELECT 'walking_lunge', 'Affondi camminati', 'lunge', NULL, 'compound', 'sagittal', 'unilateral_alternating', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'hyperextension_45', 'Hyperextension 45°', NULL, 'hip_extension', 'isolation', 'sagittal', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    SELECT 'adductor_machine', 'Adductor machine', NULL, 'hip_adduction', 'isolation', 'frontal', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    -- Polpacci
    SELECT 'standing_calf_raise', 'Calf raise in piedi (standing)', NULL, 'ankle_plantarflexion', 'isolation', 'sagittal', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    SELECT 'seated_calf_raise', 'Calf raise seduto (seated)', NULL, 'ankle_plantarflexion', 'isolation', 'sagittal', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    SELECT 'donkey_calf_raise', 'Donkey calf raise', NULL, 'ankle_plantarflexion', 'isolation', 'sagittal', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    -- Trapezio
    SELECT 'dumbbell_shrug', 'Scrollate con manubri', NULL, 'scapular_elevation', 'isolation', 'sagittal', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    SELECT 'cable_face_pull', 'Face pull ai cavi', NULL, 'shoulder_horizontal_abduction', 'isolation', 'transverse', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    -- Addome
    SELECT 'floor_crunch', 'Crunch a terra', NULL, 'spinal_flexion', 'isolation', 'sagittal', 'bilateral', 'beginner', 'reps_only' UNION ALL
    SELECT 'cable_kneeling_crunch', 'Crunch ai cavi (in ginocchio)', NULL, 'spinal_flexion', 'isolation', 'sagittal', 'bilateral', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'front_plank', 'Plank frontale', 'anti_rotation', NULL, 'isolation', 'sagittal', 'bilateral', 'beginner', 'isometric_hold' UNION ALL
    SELECT 'russian_twist', 'Russian twist', 'rotation', NULL, 'isolation', 'transverse', 'bilateral', 'beginner', 'reps_weight' UNION ALL
    SELECT 'hanging_leg_raise', 'Leg raises alla sbarra', NULL, 'hip_flexion', 'compound', 'sagittal', 'bilateral', 'advanced', 'reps_only' UNION ALL
    SELECT 'ab_wheel_rollout', 'Rollout con la ruota', 'anti_rotation', NULL, 'compound', 'sagittal', 'bilateral', 'advanced', 'reps_only' UNION ALL
    SELECT 'cable_woodchopper', 'Wood chopper ai cavi', 'rotation', NULL, 'compound', 'transverse', 'unilateral_isolated', 'intermediate', 'reps_weight' UNION ALL
    SELECT 'reverse_crunch', 'Reverse crunch', NULL, 'spinal_flexion', 'isolation', 'sagittal', 'bilateral', 'beginner', 'reps_only'
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
