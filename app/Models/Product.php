<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'category', 'price', 'original_price',
        'badge', 'badge_type', 'gradient_colors', 'emoji', 'image', 'images',
        'rating', 'description', 'is_active', 'sort_order',
    ];

    protected $appends = ['image_url', 'image_urls'];

    protected $casts = [
        'is_active' => 'boolean',
        'rating' => 'float',
        'images' => 'array',
    ];

    public function getGradientArrayAttribute(): array
    {
        return $this->gradient_colors
            ? json_decode($this->gradient_colors, true)
            : ['#1a1a2e', '#4a4a6a'];
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->storageUrl($this->image);
    }

    public function getImageUrlsAttribute(): array
    {
        return collect($this->images ?? [])
            ->map(fn ($path) => $this->storageUrl($path))
            ->filter()
            ->values()
            ->all();
    }

    private function storageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return url('storage/'.ltrim($path, '/'));
    }
}
