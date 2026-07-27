<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'category', 'price', 'original_price',
        'badge', 'badge_type', 'gradient_colors', 'emoji', 'image', 'images',
        'sizes', 'colors', 'quantity',
        'rating', 'description', 'is_active', 'sort_order',
    ];

    protected $appends = ['image_url', 'image_urls'];

    protected $casts = [
        'is_active' => 'boolean',
        'rating' => 'float',
        'images' => 'array',
        'sizes' => 'array',
        'colors' => 'array',
        'quantity' => 'integer',
    ];

    public function getGradientArrayAttribute(): array
    {
        return $this->gradient_colors
            ? json_decode($this->gradient_colors, true)
            : ['#1a1a2e', '#4a4a6a'];
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->imagePath($this->image);
    }

    public function getImageUrlsAttribute(): array
    {
        return collect($this->images ?? [])
            ->map(fn ($path) => $this->imagePath($path))
            ->filter()
            ->values()
            ->all();
    }

    private function imagePath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return ltrim($path, '/');
    }
}
