<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Setting::bool('public_api_enabled', false)) {
            return response()->json([
                'message' => 'API non disponibile.',
                'code' => 'api_disabled',
            ], 503);
        }

        return $next($request);
    }
}
