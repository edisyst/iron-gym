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
-- EXECUTION_DESCRIPTION (83 esercizi)
-- ----------------------------------------------------
UPDATE exercises SET execution_description = 'Sdraiato sulla panca, impugnatura leggermente più larga delle spalle. Abbassa il bilanciere al petto toccando la zona sternale inferiore, poi spingi verso l''alto in traiettoria leggermente obliqua verso il rack. Scapole retratte e depresse per tutto il movimento.' WHERE slug = 'barbell_bench_press';
UPDATE exercises SET execution_description = 'Sdraiato sulla panca, manubri ai lati del petto con i gomiti a circa 75°. Abbassa controllando la discesa fino a sentire stiramento pettorale, poi spingi verso l''alto e leggermente verso il centro senza bloccare i gomiti in cima.' WHERE slug = 'dumbbell_bench_press';
UPDATE exercises SET execution_description = 'Panca inclinata a 30-45°. Impugnatura come per la piana. Abbassa il bilanciere verso la parte alta del petto/clavicola, spingi verso l''alto e leggermente dietro la testa. Il deltoide anteriore lavora più della versione piana.' WHERE slug = 'incline_barbell_bench_press';
UPDATE exercises SET execution_description = 'Panca inclinata a 30-45°. Partenza con manubri in alto, abbassa aprendo i gomiti fino a sentire stiramento nella parte alta del petto, poi spingi verso l''alto convergendo leggermente le mani.' WHERE slug = 'incline_dumbbell_bench_press';
UPDATE exercises SET execution_description = 'Panca declinata (piedi in alto). Abbassa il bilanciere verso la parte bassa del petto/addome superiore. Traiettoria più verticale rispetto alla piana. Isola maggiormente il pettorale sternale inferiore.' WHERE slug = 'decline_barbell_bench_press';
UPDATE exercises SET execution_description = 'In piedi tra i due cavi alti. Braccia leggermente flesse con gomiti fissi. Porta le mani verso il basso e al centro in un arco ampio, contraendo il pettorale in chiusura. Ritorna lentamente al punto di partenza controllando lo stiramento.' WHERE slug = 'cable_chest_fly';
UPDATE exercises SET execution_description = 'Seduto alla macchina, gomiti appoggiati ai cuscinetti all''altezza delle spalle. Chiudi le braccia verso il centro senza strappare, contraendo il petto al massimo. Ritorna lentamente senza perdere tensione.' WHERE slug = 'pec_deck_machine';
UPDATE exercises SET execution_description = 'Alle parallele, busto inclinato in avanti di circa 30°. Abbassa il corpo fino a sentire stiramento nel petto (gomiti verso l''esterno), poi spingi verso l''alto mantenendo l''inclinazione. La verticalità del busto sposta il lavoro sui tricipiti.' WHERE slug = 'chest_dips';
UPDATE exercises SET execution_description = 'Seduto alla macchina con schiena appoggiata allo schienale. Spingi le maniglie in avanti in modo controllato, estendi quasi completamente le braccia, poi ritorna lentamente senza far tornare il peso a battuta.' WHERE slug = 'machine_chest_press';
UPDATE exercises SET execution_description = 'Panca inclinata posizionata sotto lo Smith Machine. Sblocca il bilanciere e abbassalo verso la parte alta del petto. Lo Smith elimina la componente di equilibrio ma fissa la traiettoria: assicurati che corrisponda alla tua biomeccanica.' WHERE slug = 'smith_incline_press';
UPDATE exercises SET execution_description = 'Mani a terra leggermente più larghe delle spalle, corpo in linea retta dai talloni alla testa. Abbassa il petto verso il suolo mantenendo il core contratto, poi spingi verso l''alto. La larghezza dell''impugnatura determina l''enfasi su petto o tricipiti.' WHERE slug = 'push_up';
UPDATE exercises SET execution_description = 'Piedi alla larghezza dei fianchi, bilanciere sui piedi. Prendi la presa con mani fuori le gambe. Schiena neutra, petto alto. Spingi il pavimento via dai piedi mantenendo il bilanciere aderente alle gambe durante tutta la salita. In cima, anca e ginocchio completamente estesi.' WHERE slug = 'conventional_deadlift';
UPDATE exercises SET execution_description = 'Partenza in piedi con bilanciere in mano. Cerniera sull''anca mantenendo la schiena neutra: abbassa il bilanciere lungo le gambe fino a sentire forte stiramento negli ischiocrurali (tipicamente sotto il ginocchio), poi torna su spingendo l''anca in avanti.' WHERE slug = 'romanian_deadlift';
UPDATE exercises SET execution_description = 'Presa prona larga, braccia tese come punto di partenza. Tira il petto verso la sbarra portando i gomiti verso il basso e indietro. Contrai il dorsale in cima, poi scendi lentamente fino a distensione completa.' WHERE slug = 'pull_up_pronated';
UPDATE exercises SET execution_description = 'Presa supina alla larghezza delle spalle. Tira verso l''alto portando il mento sopra la sbarra. La presa supina coinvolge maggiormente il bicipite rispetto alla presa prona. Scendi lentamente a braccia quasi tese.' WHERE slug = 'chin_up_supinated';
UPDATE exercises SET execution_description = 'Seduto alla lat machine, presa larga prona. Tira la barra verso il petto superiore portando i gomiti verso il basso e dietro. Non oscillare col busto. Contrai il dorsale in basso, poi risali lentamente controllando.' WHERE slug = 'lat_pulldown_front';
UPDATE exercises SET execution_description = 'Seduto con piedi sui poggiapiedi, schiena neutra. Tira la maniglia verso l''addome portando i gomiti indietro e le scapole a stringersi. Petto fuori per tutto il movimento. Ritorna lentamente con distensione controllata delle braccia.' WHERE slug = 'seated_cable_row';
UPDATE exercises SET execution_description = 'Busto inclinato a circa 45° con schiena neutra, bilanciere in presa prona. Tira verso l''ombelico portando i gomiti dietro il tronco. Non usare il rimbalzo della schiena per sollevare. Scapole che si stringono al top del movimento.' WHERE slug = 'barbell_row';
UPDATE exercises SET execution_description = 'Un ginocchio e una mano appoggiate sulla panca. Tira il manubrio verso l''anca (non verso la spalla) portando il gomito indietro e alto. Mantieni il tronco parallelo al suolo. Un set per lato.' WHERE slug = 'one_arm_dumbbell_row';
UPDATE exercises SET execution_description = 'Busto inclinato, impugnatura sulla barra T o triangolo al cavo. Tira verso il petto portando i gomiti indietro. Il range di movimento spesso è minore del rematore con bilanciere ma permette carichi elevati con buona tenuta lombare.' WHERE slug = 't_bar_row';
UPDATE exercises SET execution_description = 'In piedi di fronte al cavo alto, maniglie prese sopra la testa con gomiti leggermente flessi. Porta le mani verso i fianchi in un arco, mantenendo i gomiti fissi. Isola il dorsale in modo eccellente; utile come esercizio di connessione mente-muscolo.' WHERE slug = 'cable_pullover';
UPDATE exercises SET execution_description = 'Busto appoggiato su una panca inclinata, manubri che pendono. Tira i manubri verso i fianchi portando i gomiti indietro. L''appoggio al petto elimina la compensazione lombare e isola meglio la schiena alta.' WHERE slug = 'chest_supported_row';
UPDATE exercises SET execution_description = 'Seduto alla macchina con petto sul supporto. Tira le maniglie verso di te portando i gomiti indietro. Macchina guidata: ideale per principianti o come finisher ad alta rep con stretto controllo.' WHERE slug = 'machine_row';
UPDATE exercises SET execution_description = 'In piedi di fronte al cavo alto, barra presa con braccia quasi tese. Mantieni i gomiti fissi e porta la barra verso le cosce descrivendo un arco ampio. Isola il dorsale quasi esclusivamente, ottimo per la connessione mente-muscolo.' WHERE slug = 'straight_arm_pulldown';
UPDATE exercises SET execution_description = 'Bilanciere su blocchi o rack a metà stinco. Esegui come lo stacco convenzionale ma con range ridotto. Permette carichi superiori allo stacco completo; enfatizza erettori spinali, glutei e upper back nella fase di lock-out.' WHERE slug = 'rack_pull';
UPDATE exercises SET execution_description = 'In piedi, bilanciere a livello del mento presa leggermente più larga delle spalle. Spingi verticalmente sopra la testa estendendo le braccia, poi abbassa controllato al mento. Core contratto per proteggere la lombare. La traiettoria è leggermente dietro la testa in cima.' WHERE slug = 'overhead_press_standing';
UPDATE exercises SET execution_description = 'Seduto con schiena supportata, manubri all''altezza delle orecchie. Spingi verso l''alto fino a quasi estendere le braccia, poi abbassa lentamente. Rispetto al bilanciere permette un range più libero e meno stress sull''articolazione AC.' WHERE slug = 'seated_dumbbell_press';
UPDATE exercises SET execution_description = 'Partenza con manubri davanti al viso, palme verso di te (come la cima di un curl). Ruota le mani verso l''esterno man mano che sali, fino ad arrivare con palme in avanti in cima. Coinvolge tutto il deltoide grazie alla rotazione.' WHERE slug = 'arnold_press';
UPDATE exercises SET execution_description = 'In piedi o seduto, manubri ai fianchi. Alza le braccia lateralmente con gomiti leggermente flessi fino all''altezza delle spalle (non oltre). Il mignolo può essere leggermente più alto del pollice (''versa il bicchiere''). Abbassa lentamente.' WHERE slug = 'dumbbell_lateral_raise';
UPDATE exercises SET execution_description = 'Cavo basso sul lato opposto al braccio di lavoro. Alza il braccio lateralmente fino all''altezza della spalla. Il cavo mantiene tensione costante anche nella fase bassa, a differenza del manubrio. Un lato alla volta.' WHERE slug = 'cable_lateral_raise';
UPDATE exercises SET execution_description = 'Seduto alla pec deck al contrario (petto verso il supporto) o con le braccia che aprono invece di chiudersi. Apri le braccia verso l''esterno mantenendo i gomiti leggermente flessi. Contrai il deltoide posteriore e i romboidi in apertura.' WHERE slug = 'reverse_pec_deck';
UPDATE exercises SET execution_description = 'Busto inclinato in avanti di circa 70-90°, manubri che pendono. Alza le braccia lateralmente verso il soffitto con gomiti leggermente flessi. Evita di ruotare le spalle o usare il trapezio superiore.' WHERE slug = 'bent_over_rear_delt_raise';
UPDATE exercises SET execution_description = 'Bilanciere in presa prona stretta davanti alle cosce. Tira verso il mento portando i gomiti in alto e verso l''esterno. Attenzione: presa troppo stretta può causare impingement di spalla. Fermati quando i gomiti raggiungono l''altezza delle spalle.' WHERE slug = 'upright_row';
UPDATE exercises SET execution_description = 'Due cavi alti incrociati. Parti con le mani incrociate al centro e apri le braccia verso l''esterno e leggermente il basso, come per abbracciare un cerchio grande. Isola il deltoide posteriore con tensione costante dal cavo.' WHERE slug = 'cable_rear_delt_fly';
UPDATE exercises SET execution_description = 'Seduto alla macchina, braccia appoggiate sui cuscinetti. Spingi lateralmente verso l''alto fino all''altezza della spalla. La macchina guida il movimento e mantiene tensione anche nella fase bassa del ROM.' WHERE slug = 'machine_lateral_raise';
UPDATE exercises SET execution_description = 'In piedi, bilanciere in presa supina alla larghezza delle spalle. Tieni i gomiti fissi ai fianchi e porta il bilanciere verso le spalle flettendo solo l''avambraccio. Abbassa lentamente fino a quasi estensione completa.' WHERE slug = 'barbell_curl';
UPDATE exercises SET execution_description = 'In piedi, manubri ai fianchi. Alza un braccio alla volta portando il manubrio verso la spalla con eventuale supinazione del polso. L''alternanza permette di concentrarsi su un braccio per volta. Gomiti fissi.' WHERE slug = 'alternating_dumbbell_curl';
UPDATE exercises SET execution_description = 'Gomiti appoggiati sul bancale inclinato della panca Scott, bilanciere o EZ-bar in mano. Estendi quasi completamente le braccia nella fase bassa, poi porta il bilanciere verso le spalle. Il supporto impedisce di usare l''inerzia.' WHERE slug = 'scott_curl';
UPDATE exercises SET execution_description = 'In piedi, manubri ai fianchi con presa neutra (pollice in alto). Porta il manubrio verso la spalla senza ruotare il polso. Questa presa enfatizza il brachiale e il brachioradiale rispetto al bicipite.' WHERE slug = 'hammer_curl';
UPDATE exercises SET execution_description = 'In piedi di fronte al cavo basso, barra o maniglie in presa supina. Porta le mani verso le spalle mantenendo i gomiti fermi. Il cavo mantiene tensione nella fase bassa e alta del movimento a differenza del bilanciere.' WHERE slug = 'cable_curl';
UPDATE exercises SET execution_description = 'Seduto, gomito appoggiato all''interno della coscia. Porta il manubrio verso la spalla con movimento lento e controllato. Ottima connessione mente-muscolo. Non ruotare il tronco per aiutare il sollevamento.' WHERE slug = 'concentration_curl';
UPDATE exercises SET execution_description = 'Sdraiato su panca inclinata a 45-60°, manubri che pendono liberamente. Il gomito è dietro il tronco nella fase bassa, creando maggiore stiramento del bicipite rispetto al curl standard. Porta i manubri verso le spalle.' WHERE slug = 'incline_dumbbell_curl';
UPDATE exercises SET execution_description = 'In piedi di fronte al cavo basso con il cavo che parte da dietro. Il braccio è esteso dietro il tronco nella partenza, massimizzando lo stiramento del bicipite. Porta la mano verso la spalla mantenendo il gomito nella stessa posizione.' WHERE slug = 'bayesian_curl';
UPDATE exercises SET execution_description = 'Sdraiato sulla panca, bilanciere EZ sopra la testa con braccia quasi tese. Abbassa l''EZ verso la fronte o sopra la testa piegando solo i gomiti (che rimangono fissi). Estendi poi verso l''alto. Il grip EZ riduce lo stress sui polsi.' WHERE slug = 'ez_bar_french_press';
UPDATE exercises SET execution_description = 'In piedi di fronte al cavo alto, barra diritta in presa prona. Gomiti ai fianchi e fissi. Spingi la barra verso il basso fino a estensione completa delle braccia, poi risali lentamente fino a circa 90°. Non portare i gomiti in avanti.' WHERE slug = 'cable_pushdown_straight';
UPDATE exercises SET execution_description = 'Come il push down con sbarra ma con la corda. In cima puoi aprire le mani verso l''esterno per aumentare il range e la contrazione finale del tricipite. La corda permette un movimento più naturale per i polsi.' WHERE slug = 'cable_pushdown_rope';
UPDATE exercises SET execution_description = 'Alle parallele con busto verticale (non inclinato). Abbassa il corpo piegando i gomiti verso l''esterno fino a circa 90°, poi spingi verso l''alto. La posizione verticale del busto sposta il lavoro principalmente sui tricipiti.' WHERE slug = 'triceps_dips';
UPDATE exercises SET execution_description = 'Sdraiato sulla panca, bilanciere o EZ-bar sopra la testa con braccia quasi tese. Abbassa il peso verso la fronte o sopra la testa piegando i gomiti. I gomiti devono rimanere fermi e verticali. Estendi verso l''alto in modo esplosivo.' WHERE slug = 'skullcrusher';
UPDATE exercises SET execution_description = 'Panca piana con presa stretta (pollici a circa 30-40 cm di distanza). Abbassa il bilanciere verso il basso del petto/addome superiore, poi spingi verso l''alto. Gomiti più vicini al corpo rispetto alla panca classica per enfatizzare il tricipite.' WHERE slug = 'close_grip_bench_press';
UPDATE exercises SET execution_description = 'Schiena al cavo alto, corda impugnata dietro la testa. Estendi i gomiti verso il basso e in avanti portando la corda davanti a te. La posizione overhead massimizza lo stiramento della testa lunga del tricipite.' WHERE slug = 'overhead_cable_extension';
UPDATE exercises SET execution_description = 'Come il push down standard ma con un solo braccio e maniglia singola. Permette di correggere squilibri tra i lati e di lavorare con una presa più naturale. Un set per braccio.' WHERE slug = 'single_arm_cable_pushdown';
UPDATE exercises SET execution_description = 'Incrocio tra panca stretta e skullcrusher. Sdraiato sulla panca, abbassa il bilanciere verso il basso del collo/clavicole lasciando che i gomiti si muovano in avanti. Movimento ibrido che enfatizza molto i tricipiti con alto carico.' WHERE slug = 'jm_press';
UPDATE exercises SET execution_description = 'Bilanciere appoggiato in posizione alta sui trapezi (non sul collo). Piedi alla larghezza delle spalle, punte leggermente aperte. Scendi mantenendo il petto alto e le ginocchia in linea con le punte fino a parallelismo o sotto. Risali spingendo il pavimento via.' WHERE slug = 'back_squat_high_bar';
UPDATE exercises SET execution_description = 'Bilanciere in presa frontale (grip olimpico o cross-grip) appoggiato sulle clavicole. Gomiti alti per tenerlo in posizione. Più torso verticale rispetto al back squat, maggiore enfasi sul quadricipite. Richiede ottima mobilità di polso e caviglia.' WHERE slug = 'front_squat';
UPDATE exercises SET execution_description = 'Schiena appoggiata alla macchina inclinata, piedi sulla pedana. Scendi flettendo le ginocchia portandole verso il petto, poi spingi verso l''alto. La pedana può essere posizionata più o meno in alto per cambiare l''enfasi muscolare.' WHERE slug = 'hack_squat_machine';
UPDATE exercises SET execution_description = 'Seduto nella macchina, piedi sulla pedana alla larghezza delle spalle. Abbassa il peso flettendo le ginocchia verso il petto (non oltre 90°), poi spingi via. Non bloccare mai completamente le ginocchia in cima e non lasciare che il basso schiena si sollevi dalla seduta.' WHERE slug = 'leg_press_45';
UPDATE exercises SET execution_description = 'Bilanciere sul dorso, fai un passo avanti lungo. Abbassa il ginocchio posteriore quasi a terra mantenendo il busto verticale, poi spingi col piede anteriore per tornare alla posizione di partenza. Alterna le gambe a ogni rep.' WHERE slug = 'barbell_lunge';
UPDATE exercises SET execution_description = 'Piede posteriore appoggiato su una panca, piede anteriore avanzato. Abbassa il ginocchio posteriore verso il suolo mantenendo il busto verticale o leggermente inclinato. Poi spingi verso l''alto col piede anteriore. Un set per gamba.' WHERE slug = 'bulgarian_split_squat';
UPDATE exercises SET execution_description = 'Seduto alla macchina, caviglie appoggiate ai cuscinetti. Estendi le ginocchia fino a quasi completare il ROM, contraendo il quadricipite in cima. Abbassa lentamente. Evita di strappare verso l''alto o di iperestendere il ginocchio.' WHERE slug = 'leg_extension';
UPDATE exercises SET execution_description = 'Sdraiato a pancia in giù sulla macchina, caviglie sotto i cuscinetti. Porta i talloni verso i glutei flettendo i ginocchi, contraendo gli ischiocrurali. Abbassa lentamente. Tieni il bacino a contatto con il cuscinetto.' WHERE slug = 'lying_leg_curl';
UPDATE exercises SET execution_description = 'Seduto alla macchina, cosce appoggiate sul cuscinetto anteriore. Porta i talloni verso il basso e indietro flettendo i ginocchi. La posizione seduta allunga l''ischiocrurale anche a livello dell''anca (origine), aumentando l''attivazione rispetto al lying.' WHERE slug = 'seated_leg_curl';
UPDATE exercises SET execution_description = 'Schiena appoggiata alla panca all''altezza delle scapole, bilanciere sul bacino con cuscinetto. Piedi piatti a terra. Spingi il bacino verso l''alto estendendo l''anca completamente. Gluteo massimamente contratto in cima. Abbassa controllato.' WHERE slug = 'barbell_hip_thrust';
UPDATE exercises SET execution_description = 'Bilanciere sul dorso come nello squat. Fai cerniera sull''anca abbassando il busto mantenendo la schiena neutra e le ginocchia leggermente flesse. Scendi fino a quasi parallelo al suolo, poi torna su spingendo i fianchi in avanti.' WHERE slug = 'good_morning';
UPDATE exercises SET execution_description = 'Seduto alla macchina con cosce all''interno dei cuscinetti. Apri le gambe verso l''esterno contro la resistenza, poi riporta lentamente al centro. Isola il gluteo medio. Il busto può essere leggermente inclinato in avanti per aumentare l''attivazione.' WHERE slug = 'glute_abduction_machine';
UPDATE exercises SET execution_description = 'Come lo squat con bilanciere ma con la guida dello Smith Machine. La barra segue una traiettoria fissa, quindi è importante posizionare correttamente i piedi in anticipo. Adatto ai principianti o per isolare meglio il quadricipite con piedi avanzati.' WHERE slug = 'smith_squat';
UPDATE exercises SET execution_description = 'Macchina a piastre con baricentro oscillante (pendolo). Schiena appoggiata, piedi sulla pedana. Permette una profondità di squat eccellente con busto verticale e forte enfasi sul quadricipite. Range di movimento ampio con basso stress lombare.' WHERE slug = 'pendulum_squat';
UPDATE exercises SET execution_description = 'In piedi (con supporto), inclina il busto indietro e fletti solo le ginocchia portandole in avanti mentre sali in punta di piedi. Isola il quadricipite in modo estremo. Richiede buona mobilità e forza del core. Progressione verso versioni con carico.' WHERE slug = 'sissy_squat';
UPDATE exercises SET execution_description = 'Come gli affondi statici ma invece di tornare indietro si avanza alternando le gambe in sequenza camminando. Ottimo per coordinazione e lavoro metabolico. Bilanciere sul dorso o manubri ai fianchi.' WHERE slug = 'walking_lunge';
UPDATE exercises SET execution_description = 'Busto sulla panca hyperextension a 45°, piedi bloccati. Parti con il busto quasi verticale, abbassa fino ad avere la schiena parallela o leggermente sotto, poi risali contraendo glutei e ischiocrurali. Mantieni la schiena neutra (non iperestendere in cima).' WHERE slug = 'hyperextension_45';
UPDATE exercises SET execution_description = 'Seduto alla macchina con le gambe aperte sui cuscinetti laterali. Chiudi le gambe verso il centro contro la resistenza, poi riapri lentamente. Isola gli adduttori. Regola la larghezza di partenza in base alla mobilità dell''anca.' WHERE slug = 'adductor_machine';
UPDATE exercises SET execution_description = 'In piedi sulla pedana della macchina, spalle sotto i cuscinetti. Scendi il tallone più in basso possibile (dorsiflexion) poi spingi sulle punte il più in alto possibile (plantarflexion). Pausa di un secondo in cima per la contrazione. Enfatizza il gastrocnemio.' WHERE slug = 'standing_calf_raise';
UPDATE exercises SET execution_description = 'Seduto alla macchina con ginocchia a 90° e cuscinetti sopra le cosce. Stessa esecuzione: scendi il tallone e poi spingi sulle punte. La posizione seduta flette il ginocchio, mettendo il gastrocnemio in posizione più corta e quindi enfatizzando il soleo.' WHERE slug = 'seated_calf_raise';
UPDATE exercises SET execution_description = 'Busto inclinato in avanti (circa 90°) appoggiato al supporto, piedi sulla pedana. Esegui la plantarflessione come per il calf raise in piedi. La posizione del busto inclina il gastrocnemio verso una tensione diversa rispetto alla variante diritta.' WHERE slug = 'donkey_calf_raise';
UPDATE exercises SET execution_description = 'In piedi, manubri ai fianchi con braccia tese. Alza le spalle verso le orecchie il più possibile (scrollata), poi abbassa lentamente. Non ruotare le spalle né piegare i gomiti. La fase eccentrica lenta massimizza lo stimolo sul trapezio superiore.' WHERE slug = 'dumbbell_shrug';
UPDATE exercises SET execution_description = 'Cavo all''altezza degli occhi con corda. Tira verso il viso aprendo le mani verso l''esterno all''altezza della testa. I gomiti devono salire all''altezza delle spalle o più in alto. Ottimo per il deltoide posteriore, salute della cuffia dei rotatori e postura.' WHERE slug = 'cable_face_pull';
UPDATE exercises SET execution_description = 'Sdraiato, ginocchia flesse, mani dietro la testa o sul petto. Solleva solo le scapole dal suolo contraendo il retto addominale, non tirare il collo con le mani. Abbassa lentamente senza posare completamente la testa.' WHERE slug = 'floor_crunch';
UPDATE exercises SET execution_description = 'In ginocchio di fronte al cavo alto con corda. Fletti il busto verso il basso come per un crunch, portando i gomiti verso le cosce. Non tirare con le braccia: il movimento è della schiena/addome. Torna su lentamente.' WHERE slug = 'cable_kneeling_crunch';
UPDATE exercises SET execution_description = 'Appoggio su avambracci e punte dei piedi, corpo in linea retta. Contrai addome, glutei e quadricipiti per mantenere la posizione. Non lasciare che il bacino cada o si alzi. Respira normalmente. Mantieni la posizione per il tempo prescritto.' WHERE slug = 'front_plank';
UPDATE exercises SET execution_description = 'Seduto con busto inclinato a circa 45°, ginocchia flesse e piedi sollevati (o a terra per variante più facile). Ruota il busto da un lato all''altro toccando il suolo (o portando il peso) ad ogni rotazione. Mantieni il core contratto.' WHERE slug = 'russian_twist';
UPDATE exercises SET execution_description = 'Appeso alla sbarra con presa prona. Porta le gambe tese verso l''alto fino a parallelismo o oltre senza usare l''inerzia. Abbassa lentamente. Per principianti: gambe piegate. Il controllo discendente è fondamentale per attivare il retto addominale.' WHERE slug = 'hanging_leg_raise';
UPDATE exercises SET execution_description = 'In ginocchio con la ruota a terra, braccia tese. Fai scorrere la ruota in avanti abbassando il busto verso il suolo mantenendo la schiena neutra. Torna su contraendo il core e il dorsale. Non iperestendere la lombare nella fase di allungamento.' WHERE slug = 'ab_wheel_rollout';
UPDATE exercises SET execution_description = 'Cavo in alto su un lato. Tira la maniglia diagonalmente verso il basso e il lato opposto ruotando il busto. Movimento dal fianco alto al fianco basso. Le braccia restano quasi tese. Isola gli obliqui. Un set per lato.' WHERE slug = 'cable_woodchopper';
UPDATE exercises SET execution_description = 'Sdraiato, gambe a 90°. Porta il bacino verso il petto sollevando i glutei dal suolo tramite la contrazione del basso addome. Non usare l''inerzia delle gambe. Abbassa lentamente. Variante dei crunch che enfatizza la porzione inferiore del retto.' WHERE slug = 'reverse_crunch';

-- ----------------------------------------------------
-- Verifica conteggi attesi:
--   movement_patterns:  27 righe
--   muscles:            26 righe
--   equipment:          14 righe
--   exercises:          83 righe (execution_description: 83)
--   exercise_muscle:    259 righe
--   exercise_equipment: 108 righe
-- ----------------------------------------------------
