<?php

namespace App\Observers;

use App\Models\Exercise;
use Illuminate\Support\Facades\Cache;

class ExerciseObserver
{
    public function saved(Exercise $exercise): void
    {
        $this->flush();
    }

    public function deleted(Exercise $exercise): void
    {
        $this->flush();
    }

    private function flush(): void
    {
        Cache::forget('exercises:equipment');

        // Invalida tutte le chiavi cache del catalogo (pattern-based flush via tag)
        Cache::tags(['exercises'])->flush();
    }
}
