<?php

namespace App\Models;

use Database\Factories\SubscriptionPlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    /** @use HasFactory<SubscriptionPlanFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'price_cents', 'duration_days', 'max_accesses', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    /**
     * Accessor: prezzo formattato in euro con separatore italiano.
     */
    public function getPriceFormattedAttribute(): string
    {
        return '€ '.number_format($this->price_cents / 100, 2, ',', '.');
    }
}
