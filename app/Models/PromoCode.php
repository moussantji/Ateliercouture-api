<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PromoCode extends Model {
    protected $fillable = ['code', 'discount', 'description', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
