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
        $q = Product::orderBy('sort_order');
        if ($request->category && $request->category !== 'Tout') $q->where('category', $request->category);
        if ($request->search) $q->where('name', 'like', "%{$request->search}%");
        $products = $q->get();
        $products->each(function ($p) { $p->makeHidden(['gradient_colors']); $p->gradient = $p->gradient_array; });
        return response()->json($products);
    }

    public function show(Product $product) { return response()->json($product); }

    public function featured() {
        return response()->json(Product::whereNotNull('badge')->orderBy('sort_order')->take(4)->get());
    }

    public function store(Request $request) {
        if (!$request->user()||!$request->user()->isAdmin()) return response()->json(['message'=>'Action réservée aux administrateurs.'],403);
        $p = Product::create([
            'name'=>$request->name, 'slug'=>Str::slug($request->name.'-'.time()),
            'price'=>$request->price, 'category'=>$request->category??'Général', 'description'=>$request->description??'',
            'emoji'=>'shirt-outline', 'gradient_colors'=>'["#0F2038","#3A5A80"]', 'is_active'=>true,
            'sort_order'=>Product::max('sort_order')+1,
        ]);
        return response()->json(['product'=>$p, 'message'=>'Produit créé !'], 201);
    }

    public function update(Request $request, Product $product) {
        if (!$request->user()||!$request->user()->isAdmin()) return response()->json(['message'=>'Action réservée aux administrateurs.'],403);
        $product->update($request->only(['name','price','category','description']));
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
        return response()->json(['product'=>$product, 'image_url'=>url('storage/'.$path), 'message'=>'Image téléchargée !']);
    }
}
