<?php

use App\Models\Exercise;
use App\Models\MovementPattern;
use App\Models\Muscle;
use App\Models\Equipment;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

it('seed popola le tabelle lookup con i conteggi corretti', function () {
    expect(MovementPattern::count())->toBe(27)
        ->and(Muscle::count())->toBe(26)
        ->and(Equipment::count())->toBe(14)
        ->and(Exercise::count())->toBe(83)
        ->and(DB::table('exercise_muscle')->count())->toBe(259)
        ->and(DB::table('exercise_equipment')->count())->toBe(108);
});

it('ogni esercizio rispetta il vincolo XOR sui pattern', function () {
    $invalid = Exercise::where(function ($q) {
        $q->whereNull('compound_pattern_id')->whereNull('joint_action_id');
    })->orWhere(function ($q) {
        $q->whereNotNull('compound_pattern_id')->whereNotNull('joint_action_id');
    })->count();

    expect($invalid)->toBe(0);
});

it('i pattern FK puntano alla category corretta', function () {
    $wrongCompound = Exercise::join('movement_patterns as mp', 'exercises.compound_pattern_id', '=', 'mp.id')
        ->where('mp.category', '!=', 'compound_pattern')
        ->whereNotNull('exercises.compound_pattern_id')
        ->count();

    $wrongJointAction = Exercise::join('movement_patterns as mp', 'exercises.joint_action_id', '=', 'mp.id')
        ->where('mp.category', '!=', 'joint_action')
        ->whereNotNull('exercises.joint_action_id')
        ->count();

    expect($wrongCompound)->toBe(0)
        ->and($wrongJointAction)->toBe(0);
});
