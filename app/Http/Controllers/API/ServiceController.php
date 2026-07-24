<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        return response()->json(
            Service::where('is_active', true)
                ->orderBy('sort_order')
                ->get()
        );
    }

    public function show(Service $service)
    {
        return response()->json($service);
    }
}
