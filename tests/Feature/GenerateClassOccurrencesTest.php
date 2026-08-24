<?php

use App\Models\ClassOccurrence;
use App\Models\ClassSchedule;
use App\Models\GroupClass;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Helper: crea ClassSchedule con weekday corrispondente a $date
function scheduleForDate(Carbon $date, array $overrides = []): ClassSchedule
{
    $weekday = $date->dayOfWeekIso - 1; // 0=lun..6=dom

    return ClassSchedule::factory()->create(array_merge([
        'weekday' => $weekday,
        'start_time' => '10:00:00',
        'valid_from' => today()->toDateString(),
        'valid_until' => null,
        'is_active' => true,
    ], $overrides));
}

it('genera occorrenze per il palinsesto attivo entro l\'orizzonte', function () {
    $target = today()->addDays(3);
    $schedule = scheduleForDate($target);
    $groupClass = $schedule->groupClass;

    $this->artisan('classes:generate-occurrences', ['--horizon' => 7])
        ->assertExitCode(0);

    $occurrence = ClassOccurrence::where('class_schedule_id', $schedule->id)
        ->whereDate('date', $target->toDateString())
        ->first();

    expect($occurrence)->not->toBeNull();
    expect($occurrence->group_class_id)->toBe($groupClass->id);
    expect($occurrence->status)->toBe('planned');
    expect($occurrence->trainer_id)->toBe($schedule->trainer_id);
});

it('è idempotente: non duplica occorrenze su doppia esecuzione', function () {
    $target = today()->addDays(2);
    scheduleForDate($target);

    $this->artisan('classes:generate-occurrences', ['--horizon' => 5])->assertExitCode(0);
    $this->artisan('classes:generate-occurrences', ['--horizon' => 5])->assertExitCode(0);

    expect(ClassOccurrence::count())->toBe(1);
});

it('rispetta valid_from: non genera prima della data di inizio validità', function () {
    $future = today()->addDays(10);
    $weekday = $future->dayOfWeekIso - 1;

    // Palinsesto valido da domani; orizzonte 3 giorni → non dovrebbe generare nulla se valid_from è tra 4 e 9 giorni
    $validFrom = today()->addDays(5);
    $target = today()->addDays(3); // stesso weekday, ma prima di valid_from

    // Assicura weekday corrispondente per entrambe le date (cerca il prossimo giorno con quel weekday entro 3 gg)
    // Semplificato: usa weekday di $validFrom e orizzonte <= validFrom - today
    $schedule = ClassSchedule::factory()->create([
        'weekday' => $validFrom->dayOfWeekIso - 1,
        'start_time' => '10:00:00',
        'valid_from' => $validFrom->toDateString(),
        'valid_until' => null,
        'is_active' => true,
    ]);

    // Con orizzonte 4 giorni non si supera valid_from (5 gg)
    $this->artisan('classes:generate-occurrences', ['--horizon' => 4])->assertExitCode(0);

    expect(ClassOccurrence::where('class_schedule_id', $schedule->id)->count())->toBe(0);
});

it('rispetta valid_until: non genera dopo la data di fine validità', function () {
    $yesterday = today()->subDay();
    $schedule = ClassSchedule::factory()->create([
        'weekday' => 0,
        'start_time' => '10:00:00',
        'valid_from' => today()->subDays(30)->toDateString(),
        'valid_until' => $yesterday->toDateString(),
        'is_active' => true,
    ]);

    $this->artisan('classes:generate-occurrences', ['--horizon' => 7])->assertExitCode(0);

    expect(ClassOccurrence::where('class_schedule_id', $schedule->id)->count())->toBe(0);
});

it('ignora palinsesti inattivi', function () {
    $target = today()->addDays(2);
    scheduleForDate($target, ['is_active' => false]);

    $this->artisan('classes:generate-occurrences', ['--horizon' => 5])->assertExitCode(0);

    expect(ClassOccurrence::count())->toBe(0);
});

it('l\'orizzonte custom sovrascrive il default', function () {
    // Crea due schedules: uno su un giorno entro 3 gg, uno su un giorno tra 4 e 6 gg
    $near = today()->addDays(2);
    $far = today()->addDays(5);

    scheduleForDate($near);
    scheduleForDate($far);

    // Orizzonte 3: genera solo il vicino
    $this->artisan('classes:generate-occurrences', ['--horizon' => 3])->assertExitCode(0);

    // Il numero di occorrenze dipende da quanti giorni nell'orizzonte hanno il weekday giusto.
    // Qui verifichiamo che "far" non ha occorrenze generate (data > today+2).
    $occurrences = ClassOccurrence::all();

    foreach ($occurrences as $occ) {
        expect(Carbon::parse($occ->date)->diffInDays(today()))->toBeLessThan(3);
    }
});

it('calcola end_time correttamente da duration_minutes', function () {
    $target = today()->addDays(2);
    $schedule = scheduleForDate($target, ['start_time' => '09:00:00']);
    $groupClass = GroupClass::find($schedule->group_class_id);
    $groupClass->update(['duration_minutes' => 90]);

    $this->artisan('classes:generate-occurrences', ['--horizon' => 5])->assertExitCode(0);

    $occ = ClassOccurrence::where('class_schedule_id', $schedule->id)->first();

    expect($occ)->not->toBeNull();
    expect($occ->end_time)->toBe('10:30:00');
});
