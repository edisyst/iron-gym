<?php

use App\Models\Exercise;
use App\Models\TemplateSession;
use App\Models\TemplateSessionExercise;
use App\Models\User;
use App\Models\WorkoutTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Crea il ruolo se non esiste (RefreshDatabase non esegue i seeder)
    Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);

    $this->user = User::factory()->create();
    $this->user->assignRole('trainer');
});

it('un template viene creato con le settimane corrette', function () {
    $template = WorkoutTemplate::factory()->create([
        'weeks_count' => 5,
        'created_by' => $this->user->id,
    ]);

    expect($template->weeks_count)->toBe(5)
        ->and($template->created_by)->toBe($this->user->id);
});

it('aggiungere un esercizio a una template session lo persiste', function () {
    $template = WorkoutTemplate::factory()->create(['created_by' => $this->user->id]);
    $session = TemplateSession::factory()->create(['template_id' => $template->id, 'week_number' => 1]);
    $exercise = Exercise::first() ?? Exercise::factory()->create();

    $tse = TemplateSessionExercise::create([
        'template_session_id' => $session->id,
        'exercise_id' => $exercise->id,
        'order_in_session' => 1,
        'technique_type' => 'straight',
        'planned_sets_count' => 3,
        'planned_reps' => 10,
        'planned_rir' => 2,
        'planned_rest_sec' => 90,
    ]);

    expect(TemplateSessionExercise::where('template_session_id', $session->id)->count())->toBe(1)
        ->and($tse->exercise_id)->toBe($exercise->id);
});

it('il riordino degli esercizi aggiorna order_in_session', function () {
    $template = WorkoutTemplate::factory()->create(['created_by' => $this->user->id]);
    $session = TemplateSession::factory()->create(['template_id' => $template->id]);
    $exercise1 = Exercise::factory()->create();
    $exercise2 = Exercise::factory()->create();

    $tse1 = TemplateSessionExercise::factory()->create([
        'template_session_id' => $session->id,
        'exercise_id' => $exercise1->id,
        'order_in_session' => 1,
    ]);
    $tse2 = TemplateSessionExercise::factory()->create([
        'template_session_id' => $session->id,
        'exercise_id' => $exercise2->id,
        'order_in_session' => 2,
    ]);

    // Simula riordino: tse2 diventa primo, tse1 secondo
    foreach ([$tse2->id, $tse1->id] as $index => $id) {
        TemplateSessionExercise::where('id', $id)->update(['order_in_session' => $index + 1]);
    }

    expect(TemplateSessionExercise::find($tse2->id)->order_in_session)->toBe(1)
        ->and(TemplateSessionExercise::find($tse1->id)->order_in_session)->toBe(2);
});

it('due esercizi possono essere raggruppati in superset', function () {
    $template = WorkoutTemplate::factory()->create(['created_by' => $this->user->id]);
    $session = TemplateSession::factory()->create(['template_id' => $template->id]);
    $exercise1 = Exercise::factory()->create();
    $exercise2 = Exercise::factory()->create();

    $groupKey = Str::uuid()->toString();

    TemplateSessionExercise::factory()->create([
        'template_session_id' => $session->id,
        'exercise_id' => $exercise1->id,
        'order_in_session' => 1,
        'group_key' => $groupKey,
        'group_type' => 'superset',
    ]);
    TemplateSessionExercise::factory()->create([
        'template_session_id' => $session->id,
        'exercise_id' => $exercise2->id,
        'order_in_session' => 2,
        'group_key' => $groupKey,
        'group_type' => 'superset',
    ]);

    $grouped = TemplateSessionExercise::where('group_key', $groupKey)->get();

    expect($grouped)->toHaveCount(2)
        ->and($grouped->first()->group_type)->toBe('superset');
});
