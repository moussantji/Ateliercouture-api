<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PromoCode;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function store(Request $request) {
        $validated = $request->validate([
            'client_name'=>'required|string|max:255', 'client_phone'=>'required|string|max:20',
            'client_email'=>'nullable|email', 'items'=>'required|array',
            'items.*.name'=>'required|string', 'items.*.price'=>'required|string',
            'items.*.quantity'=>'required|integer|min:1', 'total'=>'required|integer',
            'promo_code'=>'nullable|string',
        ]);
        $validated['status'] = 'pending';
        $order = Order::create($validated);
        return response()->json(['order'=>$order, 'message'=>'✅ Commande enregistrée ! Nous vous contacterons sous 24h.'], 201);
    }

    public function validatePromo(Request $request) {
        $code = $request->code;
        $total = intval($request->total ?? 0);
        $promo = PromoCode::where('code', $code)->where('is_active', true)->first();

        if (!$promo) return response()->json(['valid'=>false, 'message'=>'Code promo invalide.'], 404);
        if ($promo->min_amount && $total < $promo->min_amount) {
            return response()->json(['valid'=>false, 'message'=>"Ce code nécessite un minimum de ".number_format($promo->min_amount,0,',',' ')." FCFA d'achat."]);
        }

        $discountValue = 0;
        if ($promo->type === 'free_delivery') {
            $discountValue = 2500; // Livraison gratuite
        } elseif ($promo->type === 'fixed') {
            $discountValue = intval(preg_replace('/[^0-9]/','',$promo->discount));
        } else { // percentage
            $pct = intval(preg_replace('/[^0-9]/','',$promo->discount) ?: 0);
            $discountValue = intval($total * $pct / 100);
        }

        return response()->json([
            'valid'=>true, 'promo'=>$promo,
            'discount_value' => $discountValue,
            'discount_label' => $promo->type === 'free_delivery' ? 'Livraison offerte' : ($promo->discount ?: ''),
            'message' => '✅ Code promo appliqué !'
        ]);
    }
}
