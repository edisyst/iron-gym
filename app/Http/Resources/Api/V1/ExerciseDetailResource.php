<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Exercise */
class ExerciseDetailResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $pattern = $this->compoundPattern ?? $this->jointAction;

        return [
            'slug' => $this->slug,
            'name' => $this->name_it,
            'description' => $this->description,
            'execution_description' => $this->execution_description,
            'mechanic' => $this->mechanic,
            'plane' => $this->plane,
            'laterality' => $this->laterality,
            'skill_level' => $this->skill_level,
            'measurement_type' => $this->measurement_type,
            'video_url' => $this->video_url,
            'thumbnail_url' => $this->thumbnail_url,
            'movement_pattern' => $pattern !== null ? [
                'slug' => $pattern->slug,
                'name' => $pattern->name_it,
                'category' => $pattern->category,
            ] : null,
            'muscles' => $this->whenLoaded('muscles', fn () => $this->muscles->map(fn ($m) => [
                'slug' => $m->slug,
                'name' => $m->name_it,
                'role' => $m->pivot->role,
                'contribution_pct' => $m->pivot->contribution_pct,
            ])->values()),
            'equipment' => $this->whenLoaded('equipment', fn () => $this->equipment->map(fn ($e) => [
                'slug' => $e->slug,
                'name' => $e->name_it,
            ])->values()),
        ];
    }
}
