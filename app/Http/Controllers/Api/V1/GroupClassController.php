<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\GroupClassResource;
use App\Models\GroupClass;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GroupClassController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        if (! Setting::bool('group_classes_enabled', false)) {
            return response()->json([
                'message' => 'Il modulo corsi collettivi non è attivo.',
                'code' => 'module_disabled',
            ], 503);
        }

        $perPage = (int) $request->input('per_page', 25);

        return GroupClassResource::collection(
            GroupClass::active()->orderBy('name')->paginate($perPage)
        );
    }
}
