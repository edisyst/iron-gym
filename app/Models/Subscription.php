<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'member_id', 'plan_id', 'started_at', 'expires_at',
        'accesses_used', 'accesses_remaining', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** @return BelongsTo<SubscriptionPlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<AccessLog, $this> */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }

    /** @param Builder<Subscription> $query
     * @return Builder<Subscription> */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('expires_at', '>=', now()->toDateString());
    }

    /** @param Builder<Subscription> $query
     * @return Builder<Subscription> */
    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->active()
            ->where('expires_at', '<=', now()->addDays($days)->toDateString());
    }
}
