<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GeneratePwaIcons extends Command
{
    protected $signature = 'pwa:generate-icons
                            {--source=resources/images/icon.png : Immagine sorgente}';

    protected $description = 'Genera le icone PWA (192x192 e 512x512) da una sorgente PNG via GD';

    public function handle(): int
    {
        $source = base_path($this->option('source'));

        if (! file_exists($source)) {
            $this->error("Sorgente non trovata: {$source}");

            return self::FAILURE;
        }

        if (! function_exists('imagecreatefrompng')) {
            $this->error('Estensione GD non disponibile. Installare php-gd.');

            return self::FAILURE;
        }

        $outputDir = public_path('icons');
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $src = imagecreatefrompng($source);
        if ($src === false) {
            $this->error('Impossibile leggere il file PNG sorgente.');

            return self::FAILURE;
        }

        $sizes = [192, 512];

        foreach ($sizes as $size) {
            $dst = imagecreatetruecolor($size, $size);

            // Mantieni trasparenza alpha
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefill($dst, 0, 0, $transparent);

            $srcW = imagesx($src);
            $srcH = imagesy($src);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $size, $size, $srcW, $srcH);

            $output = "{$outputDir}/icon-{$size}.png";
            imagepng($dst, $output);
            imagedestroy($dst);

            $this->info("Generata: {$output}");
        }

        imagedestroy($src);

        $this->info('Icone PWA generate con successo.');

        return self::SUCCESS;
    }
}
