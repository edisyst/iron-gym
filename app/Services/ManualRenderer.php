<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ManualRenderer
{
    private const MANUAL_DIR = 'resources/docs/manual';

    /**
     * Restituisce la lista ordinata delle sezioni del manuale.
     *
     * @return array<string, array{slug: string, title: string, path: string}>
     */
    public function sections(): array
    {
        $files = glob(base_path(self::MANUAL_DIR.'/*.md'));
        if ($files === false || $files === []) {
            return [];
        }
        sort($files);

        $sections = [];
        foreach ($files as $path) {
            $slug = pathinfo($path, PATHINFO_FILENAME);
            $sections[$slug] = [
                'slug' => $slug,
                'title' => $this->extractTitle($path),
                'path' => $path,
            ];
        }

        return $sections;
    }

    /**
     * Renderizza una sezione identificata dallo slug.
     *
     * Lo slug e' validato contro l'elenco dei file effettivamente presenti:
     * non viene mai concatenato a un path. Sicurezza path-traversal garantita.
     */
    public function render(string $slug): string
    {
        $sections = $this->sections();

        if (! isset($sections[$slug])) {
            abort(404);
        }

        $path = $sections[$slug]['path'];
        $mtime = (int) filemtime($path);
        $cacheKey = "manual.{$slug}.{$mtime}";

        return Cache::remember($cacheKey, 3600, function () use ($path): string {
            $content = (string) file_get_contents($path);

            return Str::markdown($content);
        });
    }

    public function slugExists(string $slug): bool
    {
        return isset($this->sections()[$slug]);
    }

    private function extractTitle(string $path): string
    {
        $content = (string) file_get_contents($path);

        if (preg_match('/^# (.+)/m', $content, $matches)) {
            return trim($matches[1]);
        }

        return pathinfo($path, PATHINFO_FILENAME);
    }
}
