<?php

namespace App\Http\Resources\Api\V1;

use App\Models\GroupClass;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GroupClass */
class GroupClassResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'duration_minutes' => $this->duration_minutes,
            'default_capacity' => $this->default_capacity,
            'room' => $this->room,
            'color' => $this->color,
            'is_active' => $this->is_active,
        ];
    }
}
