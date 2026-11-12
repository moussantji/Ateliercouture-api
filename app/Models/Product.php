<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'category', 'price', 'original_price',
        'badge', 'badge_type', 'gradient_colors', 'emoji', 'image', 'images',
        'quantity', 'sizes', 'colors', 'rating', 'description', 'is_active', 'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rating' => 'float',
        'sizes' => 'array',
        'colors' => 'array',
    ];

    public function getGradientArrayAttribute(): array
    {
        return $this->gradient_colors 
            ? json_decode($this->gradient_colors, true) 
            : ['#1a1a2e', '#4a4a6a'];
    }
}
