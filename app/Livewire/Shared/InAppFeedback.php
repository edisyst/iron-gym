<?php

namespace App\Livewire\Shared;

use App\Models\FeedbackSubmission;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Livewire\Attributes\Rule;
use Livewire\Component;

class InAppFeedback extends Component
{
    public bool $open = false;

    #[Rule('required|in:bug,suggestion,confused')]
    public string $type = 'suggestion';

    #[Rule('required|string|max:500')]
    public string $body = '';

    public string $pageUrl = '';

    public function submit(): void
    {
        $this->validate();

        $submission = FeedbackSubmission::create([
            'user_id' => auth()->id(),
            'page_url' => $this->pageUrl,
            'type' => $this->type,
            'body' => $this->body,
            'user_agent' => request()->userAgent(),
        ]);

        $to = config('app.feedback_email', config('services.feedback_email', 'feedback@iron-gym.local'));

        Mail::raw(
            implode("\n", [
                'Nuovo feedback iron-gym',
                '========================',
                'Tipo:     '.$submission->type,
                'Pagina:   '.$submission->page_url,
                'Utente:   '.($submission->user !== null ? $submission->user->email : 'anonimo'),
                'User-agent: '.$submission->user_agent,
                '',
                $submission->body,
            ]),
            fn ($m) => $m->to($to)->subject('[iron-gym] Feedback: '.$submission->type),
        );

        $this->reset(['open', 'type', 'body', 'pageUrl']);
        $this->type = 'suggestion';

        session()->flash('feedback_sent', true);
    }

    public function render(): View
    {
        return view('livewire.shared.in-app-feedback');
    }
}
