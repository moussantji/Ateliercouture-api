<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_phone' => 'required|string|max:30',
            'client_email' => 'nullable|email|max:255',
            'items' => 'required|array|min:1|max:50',
            'items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')],
            'items.*.quantity' => 'required|integer|min:1|max:20',
            'promo_code' => 'nullable|string|max:50',
        ]);

        $products = Product::query()
            ->whereIn('id', collect($validated['items'])->pluck('product_id'))
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        if ($products->count() !== count($validated['items'])) {
            return response()->json([
                'message' => 'Un ou plusieurs produits ne sont plus disponibles.',
                'errors' => ['items' => ['Le panier contient un produit indisponible.']],
            ], 422);
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
            ];
        })->values();

        $subtotal = $items->sum('line_total');
        $promo = $this->findApplicablePromo($validated['promo_code'] ?? null, $subtotal);

        if (($validated['promo_code'] ?? null) && !$promo) {
            return response()->json([
                'message' => 'Le code promo est invalide ou ne peut pas être appliqué à ce panier.',
                'errors' => ['promo_code' => ['Le code promo est invalide ou non applicable.']],
            ], 422);
        }

        $discountValue = $promo ? $this->discountValue($promo, $subtotal) : 0;
        $total = max(0, $subtotal - $discountValue);

        $order = DB::transaction(function () use ($request, $validated, $items, $total, $promo) {
            return Order::create([
                'user_id' => $request->user()?->id,
                'client_name' => $validated['client_name'],
                'client_phone' => $validated['client_phone'],
                'client_email' => $validated['client_email'] ?? null,
                'items' => $items->all(),
                'total' => $total,
                'promo_code' => $promo?->code,
                'status' => 'pending',
            ]);
        });

        return response()->json([
            'order' => $order,
            'summary' => [
                'subtotal' => $subtotal,
                'discount_value' => $discountValue,
                'total' => $total,
                'promo_code' => $promo?->code,
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
}
