<?php

namespace App\Livewire\Backoffice\Admin;

use App\Models\FeedbackSubmission;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class FeedbackList extends Component
{
    use WithPagination;

    public string $filterType = '';

    public string $filterFrom = '';

    public string $filterTo = '';

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function updatingFilterFrom(): void
    {
        $this->resetPage();
    }

    public function updatingFilterTo(): void
    {
        $this->resetPage();
    }

    public function saveNotes(int $id, string $notes): void
    {
        FeedbackSubmission::whereKey($id)->update(['internal_notes' => $notes]);
    }

    public function render(): View
    {
        $query = FeedbackSubmission::with('user')->latest('created_at');

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }
        if ($this->filterFrom) {
            $query->whereDate('created_at', '>=', $this->filterFrom);
        }
        if ($this->filterTo) {
            $query->whereDate('created_at', '<=', $this->filterTo);
        }

        return view('livewire.backoffice.admin.feedback-list', [
            'feedbacks' => $query->paginate(20),
        ])->layout('layouts.backoffice', ['page_title' => 'Feedback ricevuti']);
    }
}
