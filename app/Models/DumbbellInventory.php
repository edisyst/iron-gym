<?php

namespace App\Models;

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
class DumbbellInventory extends Model
{
    protected $table = 'dumbbell_inventory';

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


}
