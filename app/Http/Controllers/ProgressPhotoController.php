<?php

namespace App\Http\Controllers;

use App\Models\ProgressPhoto;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProgressPhotoController extends Controller
{
    /**
     * Serve la foto di progresso. Solo l'atleta proprietario può vederla.
     */
    public function show(ProgressPhoto $progressPhoto): BinaryFileResponse
    {
        // Verifica ownership: solo l'atleta proprietario accede alla sua foto
        if ($progressPhoto->athlete_id !== auth()->id()) {
            abort(403);
        }

        $path = storage_path('app/'.$progressPhoto->file_path);

        if (! file_exists($path)) {
            abort(404);
        }

        $mime = mime_content_type($path) ?: 'image/jpeg';

        return response()->file($path, ['Content-Type' => $mime]);
    }
}
