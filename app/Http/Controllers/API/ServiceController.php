<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    public function index()
    {
        return response()->json(
            Service::orderBy('sort_order')->get()
        );
    }

    public function show(Service $service)
    {
        return response()->json($service);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|string|max:100',
            'duration' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'color' => 'nullable|string',
            'category' => 'nullable|string',
        ]);

        $service = Service::create([
            'name' => $request->name,
            'slug' => \Str::slug($request->name),
            'price' => $request->price,
            'duration' => $request->duration ?? '',
            'description' => $request->description ?? '',
            'icon' => $request->icon ?? 'shirt-outline',
            'color' => $request->color ?? '#CFA866',
            'category' => $request->category ?? 'couture',
            'is_active' => true,
            'sort_order' => Service::max('sort_order') + 1,
        ]);

        return response()->json(['service' => $service, 'message' => 'Service créé !'], 201);
    }

    public function update(Request $request, Service $service)
    {
        $service->update($request->only(['name', 'price', 'duration', 'description', 'icon', 'color', 'category']));
        return response()->json(['service' => $service, 'message' => 'Service mis à jour !']);
    }

    public function toggle(Service $service)
    {
        $service->update(['is_active' => !$service->is_active]);
        return response()->json(['service' => $service, 'message' => $service->is_active ? 'Service activé' : 'Service désactivé']);
    }

    public function destroy(Service $service)
    {
        $service->delete();
        return response()->json(['message' => 'Service supprimé !']);
    }
}
