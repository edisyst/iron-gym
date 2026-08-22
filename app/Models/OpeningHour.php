<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OpeningHour extends Model
{
    protected $fillable = [
        'day_of_week',
        'specific_date',
        'is_annual',
        'start_time',
        'end_time',
        'is_open',
        'notes',
    ];

    protected $casts = [
        'specific_date' => 'date',
        'is_annual' => 'boolean',
        'is_open' => 'boolean',
    ];

    /**
     * @param  Builder<OpeningHour>  $query
     * @return Builder<OpeningHour>
     */
    public function scopeRecurring(Builder $query): Builder
    {
        return $query->whereNotNull('day_of_week')->whereNull('specific_date');
    }

    /**
     * @param  Builder<OpeningHour>  $query
     * @return Builder<OpeningHour>
     */
    public function scopeOverrides(Builder $query): Builder
    {
        return $query->whereNull('day_of_week')->whereNotNull('specific_date');
    }
}
