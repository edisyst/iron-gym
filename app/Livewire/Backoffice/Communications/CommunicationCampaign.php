<?php

namespace App\Livewire\Backoffice\Communications;

use App\Jobs\SendCampaignMessages;
use App\Models\CommunicationTemplate;
use App\Models\Member;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Component;

class CommunicationCampaign extends Component
{
    public string $filter = 'all';

    public string $channel = 'email';

    public ?int $templateId = null;

    public string $subject = '';

    public string $body = '';

    public bool $sent = false;

    public function updatedTemplateId(): void
    {
        if ($this->templateId) {
            $template = CommunicationTemplate::find($this->templateId);
            if ($template) {
                $this->subject = $template->subject ?? '';
                $this->body = $template->body;
                $this->channel = $template->channel;
            }
        }
    }

    public function send(): void
    {
        $this->validate([
            'channel' => 'required|in:email,sms',
            'body' => 'required|string|max:5000',
            'subject' => 'nullable|string|max:255',
        ]);

        $memberIds = $this->filteredMembers()->pluck('id')->all();

        if (empty($memberIds)) {
            $this->addError('filter', 'Nessun destinatario trovato per il filtro selezionato.');

            return;
        }

        SendCampaignMessages::dispatch(
            memberIds: $memberIds,
            channel: $this->channel,
            subject: $this->subject ?: null,
            body: $this->body,
            templateId: $this->templateId,
        );

        $this->sent = true;
        session()->flash('success', 'Campagna inviata in coda ('.count($memberIds).' destinatari).');
    }

    /** @return Collection<int, Member> */
    private function filteredMembers(): Collection
    {
        return match ($this->filter) {
            'active' => Member::whereHas('subscriptions', fn ($q) => $q->where('status', 'active')->where('expires_at', '>=', now()->toDateString()))->get(),
            'expired' => Member::whereDoesntHave('subscriptions', fn ($q) => $q->where('status', 'active')->where('expires_at', '>=', now()->toDateString()))->get(),
            'cert_expired' => Member::where('medical_cert_expiry', '<', Carbon::today())->get(),
            default => Member::all(),
        };
    }

    public function getRecipientsCountProperty(): int
    {
        return $this->filteredMembers()->count();
    }

    public function render(): View
    {
        $templates = CommunicationTemplate::orderBy('name')->get();

        return view('livewire.backoffice.communications.communication-campaign', compact('templates'))
            ->layout('layouts.backoffice')
            ->layoutData(['page_title' => 'Campagna di comunicazione']);
    }
}
