<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\ShopSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    private function checkAdmin($request)
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            abort(403, 'Action réservée aux administrateurs.');
        }
    }

    // === PRODUCT IMAGES (multiple) ===
    public function uploadImages(Request $request, Product $product)
    {
        $this->checkAdmin($request);
        $request->validate(['images' => 'required|array|max:4', 'images.*' => 'image|max:5120']);

        $paths = $product->images ? json_decode($product->images, true) : [];
        foreach ($request->file('images') as $img) {
            $paths[] = $img->store('products', 'public');
        }
        $product->update(['images' => json_encode(array_slice($paths, 0, 4))]);
        $product->refresh();

        return response()->json(['product' => $product, 'message' => 'Images téléchargées !']);
    }

    public function deleteImage(Request $request, Product $product, $index)
    {
        $this->checkAdmin($request);
        $paths = $product->images ? json_decode($product->images, true) : [];
        if (isset($paths[$index])) {
            \Storage::disk('public')->delete($paths[$index]);
            unset($paths[$index]);
            $product->update(['images' => json_encode(array_values($paths))]);
        }
        return response()->json(['product' => $product->fresh(), 'message' => 'Image supprimée']);
    }

    // === PROMO CODES ===
    public function getPromoCodes()
    {
        return response()->json(PromoCode::all());
    }

    public function savePromoCode(Request $request)
    {
        $this->checkAdmin($request);
        $data = $request->validate(['code' => 'required|string|max:50', 'discount' => 'nullable|string|max:50', 'description' => 'nullable|string', 'is_active' => 'boolean']);

        if ($request->id) {
            $promo = PromoCode::findOrFail($request->id);
            $promo->update($data);
        } else {
            $promo = PromoCode::create($data);
        }
        return response()->json(['promo' => $promo, 'message' => 'Code promo enregistré !']);
    }

    public function deletePromoCode($id)
    {
        $this->checkAdmin(request());
        PromoCode::findOrFail($id)->delete();
        return response()->json(['message' => 'Code promo supprimé']);
    }

    // === SHOP SETTINGS (WhatsApp, Phone) ===
    public function getSettings()
    {
        $settings = ShopSetting::pluck('value', 'key')->toArray();
        return response()->json($settings);
    }

    public function saveSettings(Request $request)
    {
        $this->checkAdmin($request);
        $allowed = ['whatsapp_number', 'phone_number', 'address', 'hours'];
        foreach ($request->only($allowed) as $key => $value) {
            ShopSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        return response()->json(['message' => 'Paramètres enregistrés !']);
    }

    // === ORDERS ===
    public function getOrders()
    {
        return response()->json(Order::orderBy('created_at', 'desc')->get());
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $this->checkAdmin($request);
        $order = Order::findOrFail($id);
        $order->update(['status' => $request->status ?? 'pending']);
        return response()->json(['order' => $order, 'message' => 'Statut mis à jour']);
    }
}
