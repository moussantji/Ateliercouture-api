<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;
use App\Mail\NewOrderMail;
use App\Mail\OrderStatusMail;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            Order::where('user_id', $request->user()->id)
                ->latest()
                ->get()
        );
    }

    public function store(Request $request)
    {
        // Authentifier optionnellement via Sanctum même sur route publique
        $user = null;
        $bearer = $request->bearerToken();
        if ($bearer) {
            $accessToken = PersonalAccessToken::findToken($bearer);
            $user = $accessToken?->tokenable;
        }

        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_phone' => 'required|string|max:30',
            'client_email' => 'nullable|email|max:255',
            'items' => 'required|array|min:1|max:50',
            'items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')],
            'items.*.quantity' => 'required|integer|min:1|max:20',
            'items.*.selected_size' => 'nullable|string|max:50',
            'items.*.selected_color' => 'nullable|string|max:50',
            'promo_code' => 'nullable|string|max:50',
            'payment_method' => 'nullable|string|max:50',
            'delivery_method' => 'nullable|string|max:50',
            'delivery_address' => 'nullable|string|max:500',
        ]);

        $result = DB::transaction(function () use ($request, $validated, $user) {
            $products = Product::query()
                ->whereIn('id', collect($validated['items'])->pluck('product_id'))
                ->where('is_active', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($validated['items'])) {
                abort(422, 'Un ou plusieurs produits ne sont plus disponibles.');
            }

            foreach ($validated['items'] as $line) {
                $product = $products->get($line['product_id']);
                if (!$product || $product->quantity < $line['quantity']) {
                    abort(422, "Stock insuffisant pour « {$product->name} » (demandé: {$line['quantity']}, disponible: " . ($product?->quantity ?? 0) . ').');
                }
            }

            $items = collect($validated['items'])->map(function (array $item) use ($products): array {
                $product = $products->get($item['product_id']);
                $unitPrice = $this->amountFromPrice($product->price);
                return [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'unit_price' => $unitPrice,
                    'quantity' => $item['quantity'],
                    'line_total' => $unitPrice * $item['quantity'],
                    'selected_size' => $item['selected_size'] ?? null,
                    'selected_color' => $item['selected_color'] ?? null,
                ];
            })->values();

            $subtotal = $items->sum('line_total');
            $promo = $this->findApplicablePromo($validated['promo_code'] ?? null, $subtotal);

            if (($validated['promo_code'] ?? null) && !$promo) {
                abort(422, 'Le code promo est invalide ou ne peut pas être appliqué à ce panier.');
            }

            $discountValue = $promo ? $this->discountValue($promo, $subtotal) : 0;
            $total = max(0, $subtotal - $discountValue);

            $order = Order::create([
                'user_id' => $user?->id,
                'client_name' => $validated['client_name'],
                'client_phone' => $validated['client_phone'],
                'client_email' => $validated['client_email'] ?? null,
                'items' => $items->all(),
                'total' => $total,
                'promo_code' => $promo?->code,
                'payment_method' => $validated['payment_method'] ?? null,
                'delivery_method' => $validated['delivery_method'] ?? null,
                'delivery_address' => $validated['delivery_address'] ?? null,
                'status' => 'pending',
            ]);

            foreach ($items as $line) {
                Product::where('id', $line['product_id'])
                    ->where('quantity', '>=', $line['quantity'])
                    ->decrement('quantity', $line['quantity']);
            }

            // Verify no product went negative (safety net for race conditions)
            $belowZero = Product::whereIn('id', $items->pluck('product_id'))
                ->where('quantity', '<', 0)
                ->lockForUpdate()
                ->get();
            if ($belowZero->isNotEmpty()) {
                throw new \RuntimeException('Stock insuffisant détecté après décrémentation.');
            }

            return [
                'order' => $order,
                'subtotal' => $subtotal,
                'discount_value' => $discountValue,
                'total' => $total,
                'promo_code' => $promo?->code,
            ];
        });

        try {
            Mail::to('contact@coutureatelier.com')->send(new NewOrderMail($result['order']));
        } catch (\Throwable $e) {
            // Email failure must never block the order
        }

        return response()->json([
            'order' => $result['order'],
            'summary' => [
                'subtotal' => $result['subtotal'],
                'discount_value' => $result['discount_value'],
                'total' => $result['total'],
                'promo_code' => $result['promo_code'],
            ],
            'message' => 'Commande enregistrée ! Nous vous contacterons sous 24h.',
        ], 201);
    }

    public function validatePromo(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'total' => 'required|integer|min:0',
        ]);

        $promo = $this->findApplicablePromo($validated['code'], $validated['total']);

        if (!$promo) {
            return response()->json(['valid' => false, 'message' => 'Code promo invalide ou non applicable.'], 404);
        }

        return response()->json([
            'valid' => true,
            'promo' => $promo,
            'discount_value' => $this->discountValue($promo, $validated['total']),
            'discount_label' => $promo->type === 'free_delivery' ? 'Livraison offerte' : ($promo->discount ?: ''),
            'message' => 'Code promo appliqué !',
        ]);
    }

    private function findApplicablePromo(?string $code, int $subtotal): ?PromoCode
    {
        if (!$code) {
            return null;
        }

        $promo = PromoCode::query()
            ->where('code', strtoupper(trim($code)))
            ->where('is_active', true)
            ->first();

        if (!$promo || $subtotal < $promo->min_amount) {
            return null;
        }

        return $promo;
    }

    private function discountValue(PromoCode $promo, int $subtotal): int
    {
        if ($promo->type === 'free_delivery') {
            // Delivery is not yet represented in orders, therefore this code
            // communicates the benefit but cannot reduce the product subtotal.
            return 0;
        }

        $value = $this->amountFromPrice($promo->discount ?? '0');

        if ($promo->type === 'percentage') {
            return min($subtotal, (int) floor($subtotal * min($value, 100) / 100));
        }

        return min($subtotal, $value);
    }

    private function amountFromPrice(string $price): int
    {
        return (int) preg_replace('/\D+/', '', $price);
    }

    public function cancel(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403, 'Cette commande ne vous appartient pas.');
        }

        if ($order->status !== 'pending') {
            abort(422, 'Seules les commandes en attente peuvent être annulées.');
        }

        DB::transaction(function () use ($order) {
            $order->update(['status' => 'cancelled']);
            $items = is_array($order->items) ? $order->items : [];
            foreach ($items as $item) {
                if (!empty($item['product_id']) && !empty($item['quantity'])) {
                    Product::where('id', $item['product_id'])->increment('quantity', (int) $item['quantity']);
                }
            }
        });

        return response()->json(['message' => 'Commande annulée.', 'order' => $order->fresh()]);
    }
}
