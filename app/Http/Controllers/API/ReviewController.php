<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index()
    {
        return response()->json(
            Review::where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    public function store(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'text' => 'required|string|max:1000',
        ]);

        $review = Review::create([
            'user_id' => $request->user()->id,
            'name' => $request->user()->name,
            'rating' => $validated['rating'],
            'text' => $validated['text'],
            'is_active' => true,
        ]);

        return response()->json(['review' => $review, 'message' => 'Merci pour votre avis !'], 201);
    }
}
