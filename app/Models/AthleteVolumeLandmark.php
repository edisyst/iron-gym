<?php

namespace App\Models;

use Database\Factories\AthleteVolumeLandmarkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AthleteVolumeLandmark extends Model
{
    /** @use HasFactory<AthleteVolumeLandmarkFactory> */
    use HasFactory;

    protected $fillable = [
        'athlete_id',
        'muscle_id',
        'mev',
        'mav_min',
        'mav_max',
        'mrv',
        'notes',
        'updated_by',
    ];

    /**
     * Muscolo a cui si riferiscono i landmarks di volume
     *     */
    /** @return BelongsTo<Muscle, $this> */
    public function muscle(): BelongsTo
    {
        return $this->belongsTo(Muscle::class);
    }

    /**
     * Atleta proprietario dei landmarks
     *     */
    /** @return BelongsTo<User, $this> */
    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }

    /**
     * Trainer che ha modificato i landmarks da ultimo
     *     */
    /** @return BelongsTo<User, $this> */
    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
