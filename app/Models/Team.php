<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $fillable = [
        'id',
        'sport_id',
        'league_id',
'api_id', 'espn_team_id',
        'name',
        'slug',
        'code',
        'description',
        'country',
        'city',
        'state',
        'founded',
        'is_national',
        'venue_name',
        'venue_address',
        'venue_city',
        'venue_capacity',
        'venue_surface',
        'venue_image',
        'logo',
        'extra_data',
        'synced_at',
        'is_active',
    ];

    protected $casts = [
        'extra_data' => 'array',
        'synced_at' => 'datetime',
        'is_active' => 'boolean',
        'is_national' => 'boolean',
        'founded' => 'integer',
        'venue_capacity' => 'integer',
    ];

    protected $appends = [
        'logo_url',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        // If logo is already a full URL (external/CDN), return as-is
        if (str_starts_with($this->logo, 'http://') || str_starts_with($this->logo, 'https://')) {
            return $this->logo;
        }

        // Otherwise, treat as local storage path
        return asset('storage/'.$this->logo);
    }

    public function sport(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function players(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Player::class);
    }
}
