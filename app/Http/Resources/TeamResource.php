<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'founded' => $this->founded,
            'logo' => $this->logo_url,
            'slug' => $this->slug,

            // League metadata (NBA-specific, from extra_data)
            'conference' => data_get($this->extra_data, 'leagues.standard.conference'),
            'division' => data_get($this->extra_data, 'leagues.standard.division'),
            'nba_franchise' => data_get($this->extra_data, 'nbaFranchise'),
            
            // Sport info
            'sport' => [
                'id' => $this->sport->id,
                'name' => $this->sport->name,
            ],
            
            // Player count (when not loading full roster)
            'players_count' => $this->when(
                !$request->routeIs('*.show'),
                fn() => $this->players_count ?? $this->players()->count()
            ),
            
            // Full roster (only when viewing team details)
            'players' => PlayerResource::collection($this->whenLoaded('players')),
            
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
