<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'slug' => $this->slug,
            
            // Physical attributes
            'height' => $this->height,
            'weight' => $this->weight,
            'position' => $this->position,
            'number' => $this->number,
            
            // Birth info
            'birth_date' => $this->birth_date?->toDateString(),
            'age' => $this->age,
            'birth_place' => $this->birth_place,
            'birth_country' => $this->birth_country,
            'nationality' => $this->nationality,
            
            // Media
            'photo' => $this->photo_url,
            
            // Team info
            'team' => $this->when(
                $this->relationLoaded('team'),
                fn() => [
                    'id' => $this->team->id,
                    'name' => $this->team->name,
                    'code' => $this->team->code,
                    'logo' => $this->team->logo_url,
                    'sport' => [
                        'id' => $this->team->sport->id,
                        'name' => $this->team->sport->name,
                    ],
                ]
            ),
            
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
