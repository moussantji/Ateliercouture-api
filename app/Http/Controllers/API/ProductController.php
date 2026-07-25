<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::orderBy('sort_order');

        if ($request->category && $request->category !== 'Tout') {
            $query->where('category', $request->category);
        }

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $products = $query->get();

        $products->each(function ($product) {
            $product->makeHidden(['gradient_colors']);
            $product->gradient = $product->gradient_array;
        });

        return response()->json($products);
    }

    public function show(Product $product)
    {
        return response()->json($product);
    }

    public function featured()
    {
        $products = Product::whereNotNull('badge')
            ->orderBy('sort_order')
            ->take(4)
            ->get();

        return response()->json($products);
    }

    // 🔐 Admin uniquement
    public function store(Request $request)
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Action réservée aux administrateurs.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|string|max:100',
            'category' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $product = Product::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name . '-' . time()),
            'price' => $request->price,
            'category' => $request->category ?? 'Général',
            'description' => $request->description ?? '',
            'emoji' => 'shirt-outline',
            'gradient_colors' => '["#0F2038","#3A5A80"]',
            'is_active' => true,
            'sort_order' => Product::max('sort_order') + 1,
        ]);

        return response()->json(['product' => $product, 'message' => 'Produit créé !'], 201);
    }

    public function update(Request $request, Product $product)
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Action réservée aux administrateurs.'], 403);
        }

        $product->update($request->only(['name', 'price', 'category', 'description']));
        $product->refresh();
        return response()->json(['product' => $product, 'message' => 'Produit mis à jour !']);
    }

    public function toggle(Request $request, Product $product)
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Action réservée aux administrateurs.'], 403);
        }

        $product->update(['is_active' => !$product->is_active]);
        $product->refresh();
        return response()->json(['product' => $product, 'message' => $product->is_active ? 'Produit activé' : 'Produit désactivé']);
    }

    public function destroy(Request $request, Product $product)
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Action réservée aux administrateurs.'], 403);
        }

        $product->delete();
        return response()->json(['message' => 'Produit supprimé !']);
    }
}
