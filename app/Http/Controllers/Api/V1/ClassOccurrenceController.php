<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ClassOccurrenceIndexRequest;
use App\Http\Resources\Api\V1\ClassOccurrenceResource;
use App\Models\ClassOccurrence;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClassOccurrenceController extends Controller
{
    public function index(ClassOccurrenceIndexRequest $request): AnonymousResourceCollection|JsonResponse
    {
        if (! Setting::bool('group_classes_enabled', false)) {
            return response()->json([
                'message' => 'Il modulo corsi collettivi non è attivo.',
                'code' => 'module_disabled',
            ], 503);
        }

        $dateFrom = $request->input('date_from', today()->toDateString());

        $query = ClassOccurrence::with(['groupClass', 'confirmedBookings', 'trainer'])
            ->where('status', 'planned')
            ->where('date', '>=', $dateFrom)
            ->orderBy('date')
            ->orderBy('start_time');

        if ($request->has('date_to')) {
            $query->where('date', '<=', $request->input('date_to'));
        }

        if ($request->has('group_class_id')) {
            $query->where('group_class_id', $request->integer('group_class_id'));
        }

        $perPage = (int) $request->input('per_page', 25);

        return ClassOccurrenceResource::collection($query->paginate($perPage));
    }
}
