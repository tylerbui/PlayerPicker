<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class League extends Model
{
    protected $fillable = [
        'name', 
        'slug',
        'description',
        'sport_id', 
        'api_id', 
        'api_type', 
        'country', 
        'category', 
        'logo', 
        'flag', 
        'seasons', 
        'synced_at', 
        'is_active'
    ];

    protected $casts = [
        'seasons' => 'array',
        'synced_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $appends = ['logo_url'];

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function getLogoUrlAttribute()
    {
        return $this->logo ?: null;
    }
}