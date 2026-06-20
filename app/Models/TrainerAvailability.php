<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class TrainerAvailability extends Model
{
    protected $table = 'trainer_availability';

    protected $fillable = [
        'trainer_id',
        'day_of_week',
        'specific_date',
        'start_time',
        'end_time',
        'is_available',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'specific_date' => 'date',
            'is_available' => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Relazioni
    // -------------------------------------------------------------------------

    /** @return BelongsTo<User, $this> */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    // -------------------------------------------------------------------------
    // Scope
    // -------------------------------------------------------------------------

    /**
     * Slot ricorrenti settimanali (day_of_week valorizzato).
     *
     * @param  Builder<TrainerAvailability>  $query
     * @return Builder<TrainerAvailability>
     */
    public function scopeRecurring(Builder $query): Builder
    {
        return $query->whereNotNull('day_of_week');
    }

    /**
     * Override puntuali per data specifica.
     *
     * @param  Builder<TrainerAvailability>  $query
     * @return Builder<TrainerAvailability>
     */
    public function scopeOverrides(Builder $query): Builder
    {
        return $query->whereNotNull('specific_date');
    }

    /**
     * Slot validi per una data: ricorrenti sul giorno della settimana OPPURE override puntuali.
     * Convenzione day_of_week: 0=lunedì, 6=domenica (Carbon dayOfWeekIso - 1).
     *
     * @param  Builder<TrainerAvailability>  $query
     * @return Builder<TrainerAvailability>
     */
    public function scopeForDate(Builder $query, Carbon $date): Builder
    {
        // Giorno della settimana ISO: 1=lunedì..7=domenica → mappa a 0..6
        $isoDay = $date->dayOfWeekIso - 1;

        return $query->where(function (Builder $q) use ($isoDay, $date) {
            // Slot ricorrente per il giorno della settimana
            $q->where(function (Builder $inner) use ($isoDay) {
                $inner->whereNotNull('day_of_week')
                    ->where('day_of_week', $isoDay);
            })
            // Override puntuale per la data specifica
                ->orWhere(function (Builder $inner) use ($date) {
                    $inner->whereNotNull('specific_date')
                        ->whereDate('specific_date', $date->toDateString());
                });
        });
    }

    // -------------------------------------------------------------------------
    // Static helpers
    // -------------------------------------------------------------------------

    /**
     * Restituisce gli slot orari disponibili per un trainer in una data,
     * escluse le finestre già occupate da prenotazioni PT attive.
     *
     * Ogni slot ha durata $durationMinutes minuti.
     * Restituisce Collection di array ['start' => 'HH:MM', 'end' => 'HH:MM'].
     *
     * @return Collection<int, array{start: string, end: string}>
     */
    public static function getAvailableSlots(int $trainerId, Carbon $date, int $durationMinutes = 60): Collection
    {
        // 1. Carica tutti gli slot di disponibilità del trainer per la data
        $availabilitySlots = static::where('trainer_id', $trainerId)
            ->forDate($date)
            ->get();

        // 2. Separa slot aperti da override di blocco
        $openSlots = $availabilitySlots->where('is_available', true);
        $blockSlots = $availabilitySlots->where('is_available', false);

        if ($openSlots->isEmpty()) {
            return collect();
        }

        // 3. Carica prenotazioni PT attive del trainer per quella data
        $existingBookings = PtBooking::where('trainer_id', $trainerId)
            ->where('booked_date', $date->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->get(['start_time', 'end_time']);

        // Costruisce array di intervalli occupati [Carbon $start, Carbon $end]
        $busyIntervals = [];

        foreach ($existingBookings as $booking) {
            $busyIntervals[] = [
                Carbon::parse($date->toDateString().' '.$booking->start_time),
                Carbon::parse($date->toDateString().' '.$booking->end_time),
            ];
        }

        // Aggiunge anche i blocchi di non disponibilità puntuale
        foreach ($blockSlots as $block) {
            $busyIntervals[] = [
                Carbon::parse($date->toDateString().' '.$block->start_time),
                Carbon::parse($date->toDateString().' '.$block->end_time),
            ];
        }

        // 4. Genera sub-slot da ogni finestra aperta
        $slots = collect();

        foreach ($openSlots as $window) {
            $windowStart = Carbon::parse($date->toDateString().' '.$window->start_time);
            $windowEnd = Carbon::parse($date->toDateString().' '.$window->end_time);

            $slotStart = $windowStart->copy();

            while ($slotStart->copy()->addMinutes($durationMinutes)->lte($windowEnd)) {
                $slotEnd = $slotStart->copy()->addMinutes($durationMinutes);

                // Verifica sovrapposizione con intervalli occupati
                $isBusy = false;
                foreach ($busyIntervals as [$busyStart, $busyEnd]) {
                    // Sovrapposizione: slotStart < busyEnd AND slotEnd > busyStart
                    if ($slotStart->lt($busyEnd) && $slotEnd->gt($busyStart)) {
                        $isBusy = true;
                        break;
                    }
                }

                if (! $isBusy) {
                    $slots->push([
                        'start' => $slotStart->format('H:i'),
                        'end' => $slotEnd->format('H:i'),
                    ]);
                }

                $slotStart->addMinutes($durationMinutes);
            }
        }

        return $slots;
    }
}
