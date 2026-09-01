<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ClassOccurrence;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ClassOccurrence */
class ClassOccurrenceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'group_class_id' => $this->group_class_id,
            'group_class_name' => $this->whenLoaded('groupClass', fn () => $this->groupClass->name),
            'date' => $this->date->toDateString(),
            'start_time' => substr((string) $this->start_time, 0, 5),
            'end_time' => substr((string) $this->end_time, 0, 5),
            'capacity' => $this->capacity,
            'available_spots' => $this->available_spots,
            'status' => $this->status,
            'trainer_id' => $this->trainer_id,
            'trainer_name' => $this->whenLoaded('trainer', fn () => $this->trainer?->name),
        ];
    }
}
