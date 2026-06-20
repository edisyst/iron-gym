<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class CommunicationTemplate extends Model
{
    protected $fillable = [
        'name',
        'channel',
        'subject',
        'body',
        'created_by',
    ];

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return array{subject: string|null, body: string} */
    public function render(Member $member): array
    {
        $subscription = $member->activeSubscription;
        $expiresAt = $subscription?->expires_at;
        $certExpiry = $member->medical_cert_expiry;

        $search = ['{{nome}}', '{{cognome}}', '{{scadenza_abbonamento}}', '{{scadenza_certificato}}'];
        $replace = [
            $member->first_name,
            $member->last_name,
            $expiresAt !== null ? Carbon::parse($expiresAt)->format('d/m/Y') : 'N/D',
            $certExpiry !== null ? Carbon::parse($certExpiry)->format('d/m/Y') : 'N/D',
        ];

        $subject = $this->subject !== null
            ? str_replace($search, $replace, $this->subject)
            : null;

        $body = str_replace($search, $replace, $this->body);

        return compact('subject', 'body');
    }
}
