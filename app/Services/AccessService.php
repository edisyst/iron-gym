<?php

namespace App\Services;

use App\Models\AccessLog;
use App\Models\Member;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class AccessService
{
    public function checkin(
        Member $member,
        int $performedBy,
        ?int $idempotencyWindowMinutes = null,
        ?string $note = null,
    ): CheckinResult {
        if ($idempotencyWindowMinutes !== null) {
            $existing = AccessLog::where('member_id', $member->id)
                ->where('checked_in_at', '>=', now()->subMinutes($idempotencyWindowMinutes))
                ->latest('checked_in_at')
                ->first();

            if ($existing) {
                return new CheckinResult(accessLog: $existing, failure: null, isDuplicate: true);
            }
        }

        if (! $member->has_medical_cert_valid) {
            return new CheckinResult(accessLog: null, failure: CheckinFailure::MedicalCertInvalid);
        }

        return DB::transaction(function () use ($member, $performedBy, $note): CheckinResult {
            $subscription = Subscription::where('member_id', $member->id)
                ->active()
                ->lockForUpdate()
                ->first();

            if (! $subscription) {
                return new CheckinResult(accessLog: null, failure: CheckinFailure::NoActiveSubscription);
            }

            if ($subscription->accesses_remaining !== null && $subscription->accesses_remaining <= 0) {
                return new CheckinResult(accessLog: null, failure: CheckinFailure::NoAccessesLeft);
            }

            $subscription->increment('accesses_used');
            if ($subscription->accesses_remaining !== null) {
                $subscription->decrement('accesses_remaining');
            }

            $log = AccessLog::create([
                'member_id' => $member->id,
                'subscription_id' => $subscription->id,
                'checked_in_at' => now(),
                'checked_in_by' => $performedBy,
                'note' => $note,
            ]);

            return new CheckinResult(accessLog: $log, failure: null);
        });
    }
}
