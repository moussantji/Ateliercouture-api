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
}
