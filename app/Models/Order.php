<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Order extends Model {
    protected $fillable = ['user_id', 'client_name', 'client_phone', 'client_email', 'items', 'total', 'promo_code', 'payment_method', 'delivery_method', 'delivery_address', 'status'];
    protected $casts = ['items' => 'array', 'paid_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
