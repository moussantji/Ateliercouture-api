<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function store(Request $request) {
        $validated = $request->validate([
            'client_name'=>'required|string|max:255',
            'client_phone'=>'required|string|max:20',
            'client_email'=>'nullable|email',
            'items'=>'required|array|min:1',
            'items.*.name'=>'required|string',
            'items.*.price'=>'required',
            'items.*.quantity'=>'required|integer|min:1',
            'items.*.product_id'=>'nullable|integer',
            'items.*.selected_size'=>'nullable|string',
            'items.*.selected_color'=>'nullable|string',
            'promo_code'=>'nullable|string',
            'payment_method'=>'nullable|string|max:50',
            'delivery_method'=>'nullable|string|max:50',
            'delivery_address'=>'nullable|string|max:500',
        ]);

        // Recompute total server-side to avoid client tampering.
        $total = 0;
        foreach ($validated['items'] as &$item) {
            $price = 0;
            if (!empty($item['product_id'])) {
                $product = Product::find($item['product_id']);
                if ($product && $product->price) {
                    $price = (int) preg_replace('/[^0-9]/', '', (string) $product->price);
                }
            }
            if ($price <= 0) {
                $price = (int) preg_replace('/[^0-9]/', '', (string) $item['price']);
            }
            $item['price'] = (string) $item['price'];
            $total += $price * (int) $item['quantity'];
        }
        unset($item);

        $validated['user_id'] = auth('sanctum')->user()?->id;
        $validated['items'] = $validated['items'];
        $validated['total'] = $total;
        $validated['status'] = 'pending';

        $order = Order::create($validated);

        return response()->json(['order'=>$order, 'message'=>'✅ Commande enregistrée ! Nous vous contacterons sous 24h.'], 201);
    }

    public function index(Request $request) {
        if (!$request->user()) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $orders = Order::where('user_id', $request->user()->id)
            ->orWhere('client_email', $request->user()->email)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }

    public function cancel(Request $request, $id) {
        if (!$request->user()) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $order = Order::findOrFail($id);

        $owner = $order->user_id === $request->user()->id
            || ($order->client_email && strcasecmp($order->client_email, $request->user()->email) === 0);

        if (!$owner) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Cette commande ne peut plus être annulée.'], 422);
        }

        $order->update(['status' => 'cancelled']);

        return response()->json(['order' => $order, 'message' => 'Commande annulée.']);
    }

    public function validatePromo(Request $request) {
        $code = $request->code;
        $total = intval($request->total ?? 0);
        $promo = \App\Models\PromoCode::where('code', $code)->where('is_active', true)->first();

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
