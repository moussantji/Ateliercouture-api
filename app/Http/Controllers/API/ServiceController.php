<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function __construct()
    {
        // La vérification admin est faite directement dans les méthodes admin
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

    // 🔐 Admin uniquement
    public function store(Request $request)
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Action réservée aux administrateurs.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|string|max:100',
            'duration' => 'nullable|string|max:100',
            'description' => 'nullable|string',
        ]);

        $service = Service::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name . '-' . time()),
            'price' => $request->price,
            'duration' => $request->duration ?? '',
            'description' => $request->description ?? '',
            'icon' => 'shirt-outline',
            'color' => '#CFA866',
            'category' => $request->category ?? 'couture',
            'is_active' => true,
            'sort_order' => Service::max('sort_order') + 1,
        ]);

        return response()->json(['service' => $service, 'message' => 'Service créé !'], 201);
    }

    public function update(Request $request, Service $service)
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Action réservée aux administrateurs.'], 403);
        }

        $service->update($request->only(['name', 'price', 'duration', 'description', 'icon', 'color', 'category']));
        $service->refresh();
        return response()->json(['service' => $service, 'message' => 'Service mis à jour !']);
    }

    public function toggle(Request $request, Service $service)
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Action réservée aux administrateurs.'], 403);
        }

        $service->update(['is_active' => !$service->is_active]);
        $service->refresh();
        return response()->json(['service' => $service, 'message' => $service->is_active ? 'Service activé' : 'Service désactivé']);
    }

    public function destroy(Request $request, Service $service)
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Action réservée aux administrateurs.'], 403);
        }

        $service->delete();
        return response()->json(['message' => 'Service supprimé !']);
    }
}
