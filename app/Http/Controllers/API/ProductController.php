<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request) {
        $request->validate([
            'category' => 'nullable|string|max:100',
            'search' => 'nullable|string|max:100',
        ]);

        $query = Product::query()->orderBy('sort_order');

        // Administrators need to manage inactive catalogue entries. Everyone
        // else must only receive items that are actually available for sale.
        if (!$request->user()?->isAdmin()) {
            $query->where('is_active', true)->where('quantity', '>', 0);
        }

        $category = trim((string) $request->input('category'));
        if ($category !== '' && $category !== 'Tout') {
            $query->where('category', $category);
        }

        $search = trim((string) $request->input('search'));
        if ($search !== '') {
            $query->where(function ($productQuery) use ($search) {
                $productQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return response()->json($this->formatProducts($query->get()));
    }

    public function show(Request $request, Product $product) {
        if (!$request->user()?->isAdmin()) {
            if (!$product->is_active || $product->quantity < 1) {
                abort(404);
            }
        }

        return response()->json($this->formatProduct($product));
    }

    public function featured(Request $request) {
        $query = Product::query()->whereNotNull('badge')->orderBy('sort_order');

        if (!$request->user()?->isAdmin()) {
            $query->where('is_active', true)->where('quantity', '>', 0);
        }

        return response()->json($this->formatProducts($query->take(4)->get()));
    }

    private function formatProducts($products)
    {
        return $products->map(fn (Product $product) => $this->formatProduct($product))->values();
    }

    private function formatProduct(Product $product): Product
    {
        $product->makeHidden(['gradient_colors']);
        $product->setAttribute('gradient', $product->gradient_array);

        return $product;
    }

    public function store(Request $request) {
        if (!$request->user()||!$request->user()->isAdmin()) return response()->json(['message'=>'Action réservée aux administrateurs.'],403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|string|max:50',
            'category' => 'required|string|max:100',
            'description' => 'nullable|string|max:2000',
            'sizes' => 'nullable|array',
            'sizes.*' => 'string|max:50',
            'colors' => 'nullable|array',
            'colors.*' => 'string|max:50',
            'quantity' => 'nullable|integer|min:0',
        ]);

        $p = Product::create([
            ...$validated,
            'slug'=>Str::slug($validated['name'].'-'.time()),
            'emoji'=>'shirt-outline', 'gradient_colors'=>'["#0F2038","#3A5A80"]', 'is_active'=>true,
            'sort_order'=>(Product::max('sort_order') ?? 0) + 1,
        ]);
        return response()->json(['product'=>$p, 'message'=>'Produit créé !'], 201);
    }

    public function update(Request $request, Product $product) {
        if (!$request->user()||!$request->user()->isAdmin()) return response()->json(['message'=>'Action réservée aux administrateurs.'],403);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|string|max:50',
            'category' => 'sometimes|required|string|max:100',
            'description' => 'nullable|string|max:2000',
            'sizes' => 'nullable|array',
            'sizes.*' => 'string|max:50',
            'colors' => 'nullable|array',
            'colors.*' => 'string|max:50',
            'quantity' => 'nullable|integer|min:0',
        ]);

        $product->update($validated);
        return response()->json(['product'=>$product->fresh(), 'message'=>'Produit mis à jour !']);
    }

    public function toggle(Request $request, Product $product) {
        if (!$request->user()||!$request->user()->isAdmin()) return response()->json(['message'=>'Action réservée aux administrateurs.'],403);
        $product->update(['is_active'=>!$product->is_active]);
        return response()->json(['product'=>$product->fresh(), 'message'=>$product->is_active?'Produit activé':'Produit désactivé']);
    }

    public function destroy(Request $request, Product $product) {
        if (!$request->user()||!$request->user()->isAdmin()) return response()->json(['message'=>'Action réservée aux administrateurs.'],403);
        $product->delete();
        return response()->json(['message'=>'Produit supprimé !']);
    }

    public function uploadImage(Request $request, Product $product) {
        if (!$request->user()||!$request->user()->isAdmin()) return response()->json(['message'=>'Action réservée aux administrateurs.'],403);
        $request->validate(['image'=>'required|image|max:5120']);
        if ($product->image) Storage::disk('public')->delete($product->image);
        $path = $request->file('image')->store('products', 'public');
        $product->update(['image'=>$path]);
        return response()->json(['product' => $product->fresh(), 'image_url' => $product->fresh()->image_url, 'message' => 'Image téléchargée !']);
    }
}
