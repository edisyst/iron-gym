<?php

namespace App\Jobs;

use App\Models\CommunicationLog;
use App\Models\Member;
use App\Notifications\MedicalCertExpiryNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SendMedicalCertExpiryReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $today = Carbon::today();

        // Scaduti negli ultimi 7 giorni o in scadenza entro 30 giorni
        Member::query()
            ->whereNotNull('medical_cert_expiry')
            ->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereBetween('medical_cert_expiry', [$today->copy()->subDays(7), $today->copy()->addDays(30)]);
            })
            ->each(function (Member $member) {
                if ($member->user !== null) {
                    $member->user->notify(new MedicalCertExpiryNotification($member));
                } else {
                    CommunicationLog::create([
                        'member_id' => $member->id,
                        'channel' => 'email',
                        'subject' => 'Certificato medico in scadenza',
                        'body' => "Il certificato medico di {$member->full_name} è in scadenza.",
                        'status' => 'pending',
                    ]);
                }
            });
    }
}
