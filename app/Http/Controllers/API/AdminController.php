<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\ShopSetting;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderStatusMail;

class AdminController extends Controller
{
    private function checkAdmin($request) {
        if (!$request->user() || !$request->user()->isAdmin()) abort(403, 'Action réservée aux administrateurs.');
    }

    // Categories existantes
    public function getCategories() {
        $productCategories = Product::whereNotNull('category')->distinct()->pluck('category');
        $serviceCategories = \App\Models\Service::whereNotNull('category')->distinct()->pluck('category');
        return response()->json([
            'products' => $productCategories,
            'services' => $serviceCategories,
        ]);
    }

    // Images
    public function uploadImages(Request $request, Product $product) {
        $this->checkAdmin($request);
        $request->validate(['images' => 'required|array|min:1|max:4', 'images.*' => 'image|max:5120']);
        $paths = is_array($product->images) ? $product->images : [];
        $newImages = $request->file('images', []);

        if (count($paths) + count($newImages) > 4) {
            return response()->json([
                'message' => 'Un produit peut contenir au maximum 4 photos.',
                'errors' => ['images' => ['Supprimez une photo avant d’en ajouter une nouvelle.']],
            ], 422);
        }

        foreach ($newImages as $image) {
            $paths[] = $image->store('products', 'public');
        }

        $product->update(['images' => $paths]);

        if (!$product->image && !empty($paths)) {
            $product->update(['image' => $paths[0]]);
        }

        return response()->json(['product' => $product->fresh(), 'message' => 'Images téléchargées !']);
    }

    public function deleteImage(Request $request, Product $product, $index) {
        $this->checkAdmin($request);
        $paths = is_array($product->images) ? $product->images : [];
        if (!array_key_exists((int) $index, $paths)) {
            return response()->json(['message' => 'Photo introuvable.'], 404);
        }

        $removed = $paths[$index];
        \Storage::disk('public')->delete($removed);
        unset($paths[$index]);
        $paths = array_values($paths);
        $product->update(['images' => $paths]);

        if ($product->image === $removed) {
            $product->update(['image' => $paths[0] ?? null]);
        }

        return response()->json(['product' => $product->fresh(), 'message' => 'Image supprimée']);
    }

    // Promos
    public function getPromoCodes() { return response()->json(PromoCode::all()); }

    public function savePromoCode(Request $request) {
        $this->checkAdmin($request);
        $validated = $request->validate([
            'id' => 'nullable|integer|exists:promo_codes,id',
            'code' => 'required|string|max:50',
            'discount' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'type' => 'required|in:percentage,fixed,free_delivery',
            'min_amount' => 'nullable|integer|min:0',
        ]);

        if ($validated['type'] !== 'free_delivery' && blank($validated['discount'] ?? null)) {
            return response()->json([
                'message' => 'Une réduction est requise pour ce type de promotion.',
                'errors' => ['discount' => ['La réduction est obligatoire.']],
            ], 422);
        }

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['min_amount'] = $validated['min_amount'] ?? 0;
        unset($validated['id']);

        if ($request->filled('id')) {
            $promo = PromoCode::findOrFail($request->integer('id'));
            if (!$request->has('is_active')) {
                $validated['is_active'] = $promo->is_active;
            } else {
                $validated['is_active'] = $request->boolean('is_active');
            }
            $promo->update($validated);
        } else {
            $validated['is_active'] = $request->boolean('is_active', true);
            $promo = PromoCode::create($validated);
        }
        return response()->json(['promo'=>$promo->fresh(), 'message'=>'Code promo enregistré !']);
    }

    public function deletePromoCode(Request $request, $id) {
        $this->checkAdmin($request);
        PromoCode::findOrFail($id)->delete();
        return response()->json(['message'=>'Code promo supprimé']);
    }

    // Settings
    public function getSettings() { return response()->json(ShopSetting::pluck('value','key')->toArray()); }

    public function saveSettings(Request $request) {
        $this->checkAdmin($request);
        $validated = $request->validate([
            'whatsapp_number' => 'sometimes|nullable|string|max:30',
            'phone_number' => 'sometimes|nullable|string|max:30',
            'address' => 'sometimes|nullable|string|max:500',
            'hours' => 'sometimes|nullable|string|max:255',
            'payment_methods' => 'sometimes|nullable|json',
        ]);

        foreach ($validated as $key => $value) {
            ShopSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return response()->json(['message'=>'Paramètres enregistrés !']);
    }

    // Orders
    public function getOrders() { return response()->json(Order::orderBy('created_at','desc')->get()); }

    public function updateOrderStatus(Request $request, $id) {
        $this->checkAdmin($request);
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,processing,completed,cancelled',
        ]);

        $order = Order::findOrFail($id);
        $oldStatus = $order->status;

        DB::transaction(function () use ($order, $validated, $oldStatus) {
            $order->update($validated);

            if ($validated['status'] === 'cancelled' && $oldStatus !== 'cancelled') {
                $items = is_array($order->items) ? $order->items : [];
                foreach ($items as $item) {
                    if (!empty($item['product_id']) && !empty($item['quantity'])) {
                        Product::where('id', $item['product_id'])->increment('quantity', (int) $item['quantity']);
                    }
                }
            }
        });

        if ($order->client_email) {
            try {
                Mail::to($order->client_email)->send(new OrderStatusMail($order->fresh()));
            } catch (\Throwable $e) {}
        }

        return response()->json(['order'=>$order->fresh(), 'message'=>'Statut mis à jour']);
    }

    public function markOrderPaid(Request $request, $id) {
        $this->checkAdmin($request);
        $order = Order::findOrFail($id);
        $order->update(['paid_at' => $order->paid_at ? null : now()]);
        return response()->json(['order'=>$order->fresh(), 'message'=>$order->paid_at ? '✅ Paiement marqué comme reçu' : '✅ Marqué comme non payé']);
    }
}
