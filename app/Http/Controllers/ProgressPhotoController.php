<?php

namespace App\Http\Controllers;

use App\Models\ProgressPhoto;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProgressPhotoController extends Controller
{
    /**
     * Serve la foto di progresso. Solo l'atleta proprietario può vederla.
     */
    public function show(ProgressPhoto $progressPhoto): BinaryFileResponse
    {
        if ($progressPhoto->athlete_id !== auth()->id()) {
            abort(403);
        }

        $disk = Storage::disk('local');
        $resolved = $disk->path($progressPhoto->file_path);
        $athleteRoot = $disk->path('athletes/'.$progressPhoto->athlete_id.'/photos');

        // Blocca path traversal: il file deve stare sotto la cartella dell'atleta
        if (! str_starts_with(realpath($resolved) ?: $resolved, realpath($athleteRoot) ?: $athleteRoot)) {
            abort(403);
        }

        if (! $disk->exists($progressPhoto->file_path)) {
            abort(404);
        }

        $mime = $disk->mimeType($progressPhoto->file_path) ?: 'image/jpeg';

        return response()->file($resolved, ['Content-Type' => $mime]);
    }
}
