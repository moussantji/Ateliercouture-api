<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PromoCode;
use App\Models\ShopSetting;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_phone' => 'required|string|max:20',
            'client_email' => 'nullable|email',
            'items' => 'required|array',
            'items.*.name' => 'required|string',
            'items.*.price' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'total' => 'required|integer',
            'promo_code' => 'nullable|string',
        ]);

        $validated['status'] = 'pending';
        $order = Order::create($validated);

        return response()->json([
            'order' => $order,
            'message' => '✅ Commande enregistrée ! Nous vous contacterons sous 24h.'
        ], 201);
    }

    public function validatePromo(Request $request)
    {
        $code = $request->code;
        $promo = PromoCode::where('code', $code)->where('is_active', true)->first();
        if (!$promo) {
            return response()->json(['valid' => false, 'message' => 'Code promo invalide.'], 404);
        }
        return response()->json(['valid' => true, 'promo' => $promo]);
    }
}
