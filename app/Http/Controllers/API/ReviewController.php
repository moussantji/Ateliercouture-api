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
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'text' => 'required|string|max:1000',
        ]);

        $review = Review::create([
            'user_id' => $request->user()?->id,
            'name' => $request->user()?->name ?? 'Anonyme',
            'text' => $validated['text'],
            'rating' => $validated['rating'],
            'color' => '#CFA866',
            'is_active' => false,
        ]);

        return response()->json($review, 201);
    }
}
