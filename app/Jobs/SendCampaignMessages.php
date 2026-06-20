<?php

namespace App\Jobs;

use App\Models\CommunicationLog;
use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendCampaignMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  list<int>  $memberIds
     */
    public function __construct(
        public readonly array $memberIds,
        public readonly string $channel,
        public readonly ?string $subject,
        public readonly string $body,
        public readonly ?int $templateId = null,
    ) {}

    public function handle(): void
    {
        $chunks = array_chunk($this->memberIds, 50);

        foreach ($chunks as $chunk) {
            Member::whereIn('id', $chunk)->each(function (Member $member) {
                $renderedSubject = $this->renderVariables($this->subject ?? '', $member);
                $renderedBody = $this->renderVariables($this->body, $member);

                $status = 'pending';
                $sentAt = null;
                $error = null;

                if ($this->channel === 'email' && $member->email !== '') {
                    try {
                        Mail::raw($renderedBody, function ($mail) use ($member, $renderedSubject) {
                            $mail->to($member->email)
                                ->subject($renderedSubject ?: 'Comunicazione Iron Gym');
                        });
                        $status = 'sent';
                        $sentAt = now();
                    } catch (\Throwable $e) {
                        $status = 'failed';
                        $error = $e->getMessage();
                    }
                }

                CommunicationLog::create([
                    'member_id' => $member->id,
                    'template_id' => $this->templateId,
                    'channel' => $this->channel,
                    'subject' => $renderedSubject ?: null,
                    'body' => $renderedBody,
                    'status' => $status,
                    'sent_at' => $sentAt,
                    'error_message' => $error,
                ]);
            });
        }
    }

    private function renderVariables(string $text, Member $member): string
    {
        $subscription = $member->activeSubscription;

        return str_replace(
            ['{{nome}}', '{{cognome}}', '{{scadenza_abbonamento}}', '{{scadenza_certificato}}'],
            [
                $member->first_name,
                $member->last_name,
                ($subscription?->expires_at !== null ? Carbon::parse($subscription->expires_at)->format('d/m/Y') : 'N/D'),
                ($member->medical_cert_expiry !== null ? Carbon::parse($member->medical_cert_expiry)->format('d/m/Y') : 'N/D'),
            ],
            $text,
        );
    }
}
