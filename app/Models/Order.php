<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Order extends Model {
    protected $fillable = ['client_name', 'client_phone', 'client_email', 'items', 'total', 'promo_code', 'status'];
    protected $casts = ['items' => 'array'];
}
