<?php

use App\Models\Exercise;
use App\Services\ExerciseSubstitutionFinder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->finder = app(ExerciseSubstitutionFinder::class);
});

// ---- Test su catalogo reale ----

it('le alternative di barbell_bench_press includono dumbbell_bench_press in testa', function () {
    $original = Exercise::where('slug', 'barbell_bench_press')->firstOrFail();
    $candidates = $this->finder->find($original);

    $slugs = $candidates->pluck('exercise.slug')->all();

    expect($slugs)->toContain('dumbbell_bench_press');
    // dumbbell_bench_press deve essere nelle prime posizioni (alto overlap)
    $pos = array_search('dumbbell_bench_press', $slugs);
    expect($pos)->toBeLessThan(3);
});

it('le alternative di barbell_bench_press includono machine_chest_press', function () {
    $original = Exercise::where('slug', 'barbell_bench_press')->firstOrFail();
    $candidates = $this->finder->find($original);

    $slugs = $candidates->pluck('exercise.slug')->all();
    expect($slugs)->toContain('machine_chest_press');
});

it('le alternative di leg_extension restano nel pattern knee_extension', function () {
    $original = Exercise::where('slug', 'leg_extension')->firstOrFail();
    $candidates = $this->finder->find($original);

    $jointActionId = $original->joint_action_id;

    foreach ($candidates as $c) {
        expect($c['exercise']->joint_action_id)->toBe($jointActionId);
    }
});

it('barbell_bench_press non è tra i propri candidati', function () {
    $original = Exercise::where('slug', 'barbell_bench_press')->firstOrFail();
    $candidates = $this->finder->find($original);

    $slugs = $candidates->pluck('exercise.slug')->all();
    expect($slugs)->not->toContain('barbell_bench_press');
});

it('restituisce al massimo 5 candidati', function () {
    $original = Exercise::where('slug', 'barbell_bench_press')->firstOrFail();
    $candidates = $this->finder->find($original);

    expect($candidates)->toHaveCount(5);
});

it('ogni candidato ha equipment_slugs e primary_muscles come array', function () {
    $original = Exercise::where('slug', 'barbell_bench_press')->firstOrFail();
    $candidates = $this->finder->find($original);

    foreach ($candidates as $c) {
        expect($c['equipment_slugs'])->toBeArray();
        expect($c['primary_muscles'])->toBeArray();
    }
});

// ---- Test su measurement_type ----

it('esclude candidati con measurement_type diverso dall\'originale', function () {
    // Crea un esercizio con stesso joint_action ma measurement_type diverso
    $original = Exercise::where('slug', 'leg_extension')->firstOrFail();

    Exercise::create([
        'slug' => 'test-leg-ext-time',
        'name_it' => 'Leg extension isometrica',
        'mechanic' => 'isolation',
        'plane' => 'sagittal',
        'laterality' => 'bilateral',
        'skill_level' => 'beginner',
        'measurement_type' => 'isometric_hold',
        'joint_action_id' => $original->joint_action_id,
        'compound_pattern_id' => null,
    ]);

    $candidates = $this->finder->find($original);
    $slugs = $candidates->pluck('exercise.slug')->all();

    expect($slugs)->not->toContain('test-leg-ext-time');
});

// ---- Test su soft-delete ----

it('esclude esercizi soft-deleted', function () {
    $original = Exercise::where('slug', 'barbell_bench_press')->firstOrFail();

    // Soft-delete dumbbell_bench_press
    $target = Exercise::where('slug', 'dumbbell_bench_press')->firstOrFail();
    $target->delete();

    $candidates = $this->finder->find($original);
    $slugs = $candidates->pluck('exercise.slug')->all();

    expect($slugs)->not->toContain('dumbbell_bench_press');
});

// ---- Test overlap ordinamento ----

it('i candidati sono ordinati per overlap decrescente', function () {
    $original = Exercise::where('slug', 'barbell_bench_press')->firstOrFail();
    $candidates = $this->finder->find($original);

    $overlaps = $candidates->pluck('overlap')->all();
    $sorted = collect($overlaps)->sortDesc()->values()->all();

    expect($overlaps)->toBe($sorted);
});
