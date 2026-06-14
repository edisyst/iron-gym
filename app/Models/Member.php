<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $date_of_birth
 * @property Carbon|null $medical_cert_expiry
 */
class Member extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'first_name', 'last_name', 'email', 'phone',
        'date_of_birth', 'fiscal_code', 'address', 'city', 'postal_code',
        'medical_cert_expiry', 'notes', 'is_active', 'height_cm',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'medical_cert_expiry' => 'date',
            'is_active' => 'boolean',
            'height_cm' => 'decimal:1',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /** @return HasMany<AccessLog, $this> */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }

    /** @return HasOne<Subscription, $this> */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('expires_at', '>=', now()->toDateString())
            ->latestOfMany('started_at');
    }

    public function getHasMedicalCertValidAttribute(): bool
    {
        return $this->medical_cert_expiry !== null
            && $this->medical_cert_expiry->isFuture();
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
