<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $fillable = [
'team_id', 'api_id', 'espn_athlete_id', 'first_name', 'last_name', 'slug',
        'birth_date', 'birth_place', 'birth_country', 'nationality',
        'height', 'weight', 'position', 'number', 'photo', 'biography',
        'current_season_stats', 'recent_games_stats', 'previous_season_stats',
        'career_stats', 'news', 'stats_synced_at',
        'extra_data', 'synced_at', 'is_active'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'height' => 'integer',
        'weight' => 'integer',
        'current_season_stats' => 'array',
        'recent_games_stats' => 'array',
        'previous_season_stats' => 'array',
        'career_stats' => 'array',
        'news' => 'array',
        'stats_synced_at' => 'datetime',
        'extra_data' => 'array',
        'synced_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $appends = ['photo_url', 'full_name', 'age'];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function getPhotoUrlAttribute()
    {
        return $this->photo ?: asset('images/default-player.png');
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getAgeAttribute()
    {
        return $this->birth_date ? $this->birth_date->age : null;
    }
}
