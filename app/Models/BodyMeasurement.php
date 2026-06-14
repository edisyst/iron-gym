<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BodyMeasurement extends Model
{
    protected $fillable = [
        'athlete_id',
        'measured_at',
        'weight_kg',
        'body_fat_pct',
        'chest_cm',
        'waist_cm',
        'hips_cm',
        'left_arm_cm',
        'right_arm_cm',
        'left_thigh_cm',
        'right_thigh_cm',
        'left_calf_cm',
        'right_calf_cm',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'measured_at' => 'date',
            'weight_kg' => 'decimal:2',
            'body_fat_pct' => 'decimal:1',
            'chest_cm' => 'decimal:1',
            'waist_cm' => 'decimal:1',
            'hips_cm' => 'decimal:1',
            'left_arm_cm' => 'decimal:1',
            'right_arm_cm' => 'decimal:1',
            'left_thigh_cm' => 'decimal:1',
            'right_thigh_cm' => 'decimal:1',
            'left_calf_cm' => 'decimal:1',
            'right_calf_cm' => 'decimal:1',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Calcola il BMI usando il peso corrente e l'altezza del profilo atleta.
     * Restituisce null se mancano peso o altezza.
     */
    public function getBmiAttribute(): ?float
    {
        $weight = $this->weight_kg;
        $height = $this->athlete?->member?->height_cm;

        if ($weight === null || $height === null || (float) $height <= 0) {
            return null;
        }

        $heightM = (float) $height / 100;

        return round((float) $weight / ($heightM ** 2), 1);
    }
}
