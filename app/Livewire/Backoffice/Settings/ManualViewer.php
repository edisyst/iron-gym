<?php

namespace App\Livewire\Backoffice\Settings;

use App\Services\ManualRenderer;
use Illuminate\View\View;
use Laravel\Pennant\Feature;
use Livewire\Component;

class ManualViewer extends Component
{
    /**
     * Flag associati a sezioni specifiche (slug => nome_flag).
     * Popolato nello Step 4 con le sezioni relative a funzioni flaggabili.
     *
     * @var array<string, string>
     */
    private const SECTION_FLAGS = [];

    public string $currentSlug = '';

    public function mount(): void
    {
        $sections = app(ManualRenderer::class)->sections();

        if ($this->currentSlug === '' && $sections !== []) {
            $this->currentSlug = array_key_first($sections);
        }
    }

    public function selectSection(string $slug): void
    {
        if (app(ManualRenderer::class)->slugExists($slug)) {
            $this->currentSlug = $slug;
        }
    }

    public function render(): View
    {
        $renderer = app(ManualRenderer::class);
        $sections = $renderer->sections();

        $renderedHtml = '';
        if ($this->currentSlug !== '' && isset($sections[$this->currentSlug])) {
            $renderedHtml = $renderer->render($this->currentSlug);
        }

        $flagStatuses = [];
        foreach (self::SECTION_FLAGS as $slug => $flag) {
            $flagStatuses[$slug] = Feature::active($flag);
        }

        return view('livewire.backoffice.settings.manual-viewer', [
            'sections' => $sections,
            'renderedHtml' => $renderedHtml,
            'flagStatuses' => $flagStatuses,
        ]);
    }
}
