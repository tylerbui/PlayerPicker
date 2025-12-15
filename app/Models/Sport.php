<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sport extends Model
{
    protected $fillable = [
        'id',
        'name',
        'slug',
        'description',
        'type',
        'category',
        'image',
        'icon',
    ];

    // virtual attributes
    protected $appends = [
        'image_url',
        'icon_url',
        'teams_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        // If image is already a full URL (external/CDN), return as-is
        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        // Otherwise, treat as local storage path
        return asset('storage/'.$this->image);
    }

    public function getIconUrlAttribute(): ?string
    {
        if (! $this->icon) {
            return null;
        }

        // If icon is already a full URL (external/CDN), return as-is
        if (str_starts_with($this->icon, 'http://') || str_starts_with($this->icon, 'https://')) {
            return $this->icon;
        }

        // Otherwise, treat as local storage path
        return asset('storage/'.$this->icon);
    }
}
