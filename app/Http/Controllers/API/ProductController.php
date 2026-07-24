<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::where('is_active', true);

        if ($request->category && $request->category !== 'Tout') {
            $query->where('category', $request->category);
        }

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $products = $query->orderBy('sort_order')->get();

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
        $products = Product::where('is_active', true)
            ->whereNotNull('badge')
            ->orderBy('sort_order')
            ->take(4)
            ->get();

        return response()->json($products);
    }
}
