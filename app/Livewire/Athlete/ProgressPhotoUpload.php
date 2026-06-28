<?php

namespace App\Livewire\Athlete;

use App\Models\ProgressPhoto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Title('Foto progressi')]
class ProgressPhotoUpload extends Component
{
    use WithFileUploads;

    /** @var array<string, TemporaryUploadedFile|null> */
    public array $photos = [
        'front' => null,
        'back' => null,
        'side_left' => null,
        'side_right' => null,
    ];

    public string $takenAt = '';

    public ?string $notes = null;

    public bool $saved = false;

    /** @var Collection<int, ProgressPhoto> */
    public Collection $uploaded;

    public function mount(): void
    {
        $this->takenAt = now()->toDateString();
        $this->uploaded = collect();
        $this->loadUploaded();
    }

    private function loadUploaded(): void
    {
        $this->uploaded = ProgressPhoto::where('athlete_id', auth()->id())
            ->where('taken_at', $this->takenAt)
            ->get()
            ->keyBy('pose');
    }

    public function updatedTakenAt(): void
    {
        $this->loadUploaded();
    }

    public function save(): void
    {
        $this->validate([
            'photos.front' => 'nullable|file|mimes:jpeg,jpg,png|max:5120',
            'photos.back' => 'nullable|file|mimes:jpeg,jpg,png|max:5120',
            'photos.side_left' => 'nullable|file|mimes:jpeg,jpg,png|max:5120',
            'photos.side_right' => 'nullable|file|mimes:jpeg,jpg,png|max:5120',
            'takenAt' => 'required|date',
        ]);

        $athleteId = auth()->id();
        $year = now()->parse($this->takenAt)->format('Y');
        $month = now()->parse($this->takenAt)->format('m');
        $basePath = "athletes/{$athleteId}/photos/{$year}/{$month}";

        foreach (['front', 'back', 'side_left', 'side_right'] as $pose) {
            /** @var TemporaryUploadedFile|null $file */
            $file = $this->photos[$pose] ?? null;
            if ($file === null) {
                continue;
            }

            // Elimina file precedente se esiste per questa posa/data
            $oldPath = ProgressPhoto::where('athlete_id', $athleteId)
                ->where('taken_at', $this->takenAt)
                ->where('pose', $pose)
                ->value('file_path');

            if ($oldPath) {
                Storage::disk('local')->delete($oldPath);
            }

            // Salva nel disco local (storage/app/)
            $filename = $pose.'_'.Str::uuid().'.'.$file->getClientOriginalExtension();
            $filePath = $file->storeAs($basePath, $filename, 'local');

            // Crea o sovrascrive la foto per questa data e posa
            ProgressPhoto::updateOrCreate(
                [
                    'athlete_id' => $athleteId,
                    'taken_at' => $this->takenAt,
                    'pose' => $pose,
                ],
                [
                    'file_path' => $filePath,
                    'notes' => $this->notes,
                ]
            );
        }

        $this->loadUploaded();
        $this->saved = true;
        $this->dispatch('saved');

        // Reset file inputs
        $this->photos = ['front' => null, 'back' => null, 'side_left' => null, 'side_right' => null];
        $this->notes = null;
    }

    public function render(): View
    {
        return view('livewire.athlete.progress-photo-upload')
            ->layout('layouts.athlete');
    }
}
