<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $weight_kg
 * @property int $quantity_pairs
 * @property string|null $color
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PlateInventory extends Model
{
    /** Nome tabella esplicito: convenzione spec usa singolare per lookup table */
    protected $table = 'plate_inventory';

    /** @var list<string> */
    protected $fillable = [
        'weight_kg',
        'quantity_pairs',
        'color',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope: solo dischi attivi, ordinati dal più pesante.
     *
     * @param  Builder<PlateInventory>  $query
     * @return Builder<PlateInventory>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderByDesc('weight_kg');
    }
}
