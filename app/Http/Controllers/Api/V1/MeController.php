<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $request->user()->currentAccessToken();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_service_account' => $user->is_service_account,
            'roles' => $user->getRoleNames()->values(),
            'abilities' => method_exists($token, 'abilities') ? $token->abilities : [],
        ]);
    }
}
