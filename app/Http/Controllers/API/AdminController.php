<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\ShopSetting;
use App\Models\Order;
use Illuminate\Http\Request;

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
        $request->validate(['images'=>'required|array|max:4','images.*'=>'image|max:5120']);
        $paths = $product->images ? json_decode($product->images, true) : [];
        foreach ($request->file('images') as $img) $paths[] = $img->store('products', 'public');
        $product->update(['images' => json_encode(array_slice($paths, 0, 4))]);
        return response()->json(['product'=>$product->fresh(), 'message'=>'Images téléchargées !']);
    }

    public function deleteImage(Request $request, Product $product, $index) {
        $this->checkAdmin($request);
        $paths = $product->images ? json_decode($product->images, true) : [];
        if (isset($paths[$index])) { \Storage::disk('public')->delete($paths[$index]); unset($paths[$index]); $product->update(['images'=>json_encode(array_values($paths))]); }
        return response()->json(['product'=>$product->fresh(), 'message'=>'Image supprimée']);
    }

    // Promos
    public function getPromoCodes() { return response()->json(PromoCode::all()); }

    public function savePromoCode(Request $request) {
        $this->checkAdmin($request);
        $validated = $request->validate([
            'code'=>'required|string|max:50', 'discount'=>'nullable|string|max:50',
            'description'=>'nullable|string', 'is_active'=>'boolean',
            'type'=>'nullable|string|in:percentage,fixed,free_delivery', 'min_amount'=>'nullable|integer|min:0',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);

        if ($request->id) {
            $promo = PromoCode::findOrFail($request->id); $promo->update($validated);
        } else {
            $promo = PromoCode::create($validated);
        }
        return response()->json(['promo'=>$promo->fresh(), 'message'=>'Code promo enregistré !']);
    }

    public function deletePromoCode($id) { PromoCode::findOrFail($id)->delete(); return response()->json(['message'=>'Code promo supprimé']); }

    // Settings
    public function getSettings() { return response()->json(ShopSetting::pluck('value','key')->toArray()); }

    public function saveSettings(Request $request) {
        $this->checkAdmin($request);
        foreach ($request->only(['whatsapp_number','phone_number','address','hours']) as $k=>$v) {
            if ($v) ShopSetting::updateOrCreate(['key'=>$k], ['value'=>$v]);
        }
        return response()->json(['message'=>'Paramètres enregistrés !']);
    }

    // Orders
    public function getOrders() { return response()->json(Order::orderBy('created_at','desc')->get()); }

    public function updateOrderStatus(Request $request, $id) {
        $this->checkAdmin($request);
        $order = Order::findOrFail($id); $order->update(['status'=>$request->status??'pending']);
        return response()->json(['order'=>$order, 'message'=>'Statut mis à jour']);
    }
}
