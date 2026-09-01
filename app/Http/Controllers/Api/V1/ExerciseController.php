<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ExerciseIndexRequest;
use App\Http\Resources\Api\V1\ExerciseDetailResource;
use App\Http\Resources\Api\V1\ExerciseListResource;
use App\Models\Exercise;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExerciseController extends Controller
{
    public function index(ExerciseIndexRequest $request): AnonymousResourceCollection
    {
        $query = Exercise::with('primaryMuscles')->orderBy('name');

        if ($slug = $request->input('muscle_slug')) {
            $query->whereHas('muscles', fn ($q) => $q->where('slug', $slug));
        }

        if ($slug = $request->input('equipment_slug')) {
            $query->whereHas('equipment', fn ($q) => $q->where('slug', $slug));
        }

        if ($type = $request->input('measurement_type')) {
            $query->where('measurement_type', $type);
        }

        if ($mechanic = $request->input('mechanic')) {
            $query->where('mechanic', $mechanic);
        }

        $perPage = (int) $request->input('per_page', 25);

        return ExerciseListResource::collection($query->paginate($perPage));
    }

    public function show(Exercise $exercise): ExerciseDetailResource
    {
        $exercise->load(['muscles', 'equipment', 'compoundPattern', 'jointAction']);

        return new ExerciseDetailResource($exercise);
    }
}
