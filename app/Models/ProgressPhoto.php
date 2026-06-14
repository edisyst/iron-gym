<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressPhoto extends Model
{
    public const UPDATED_AT = null; // tabella senza updated_at

    protected $fillable = [
        'athlete_id',
        'taken_at',
        'pose',
        'file_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'taken_at' => 'date',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }

    /**
     * URL per visualizzare la foto via route dedicata (non URL storage pubblico).
     */
    public function getUrlAttribute(): string
    {
        return route('athlete.photos.show', $this);
    }
}
