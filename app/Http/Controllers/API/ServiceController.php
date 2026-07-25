<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function index(Request $request) {
        $query = Service::query()->orderBy('sort_order');

        // The administration screen reuses this endpoint and must be able to
        // manage inactive services. Public and client requests only see bookable services.
        if (!$request->user()?->isAdmin()) {
            $query->where('is_active', true);
        }

        return response()->json($query->get());
    }

    public function show(Request $request, Service $service) {
        if (!$service->is_active && !$request->user()?->isAdmin()) {
            abort(404);
        }

        return response()->json($service);
    }

    public function store(Request $request) {
        if (!$request->user() || !$request->user()->isAdmin()) return response()->json(['message'=>'Action réservée aux administrateurs.'],403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|string|max:50',
            'duration' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'icon' => 'nullable|string|max:100',
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'category' => 'nullable|string|max:100',
        ]);

        $service = Service::create([
            ...$validated,
            'slug' => Str::slug($validated['name'].'-'.time()),
            'duration' => $validated['duration'] ?? '',
            'description' => $validated['description'] ?? '',
            'icon' => $validated['icon'] ?? 'shirt-outline',
            'color' => $validated['color'] ?? '#CFA866',
            'category' => $validated['category'] ?? 'robe',
            'is_active' => true,
            'sort_order' => (Service::max('sort_order') ?? 0) + 1,
        ]);
        return response()->json(['service'=>$service, 'message'=>'Service créé !'], 201);
    }

    public function update(Request $request, Service $service) {
        if (!$request->user()||!$request->user()->isAdmin()) return response()->json(['message'=>'Action réservée aux administrateurs.'],403);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|string|max:50',
            'duration' => 'sometimes|nullable|string|max:100',
            'description' => 'sometimes|nullable|string|max:2000',
            'icon' => 'sometimes|nullable|string|max:100',
            'color' => ['sometimes', 'nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'category' => 'sometimes|nullable|string|max:100',
        ]);

        $service->update($validated);
        return response()->json(['service'=>$service->fresh(), 'message'=>'Service mis à jour !']);
    }

    public function toggle(Request $request, Service $service) {
        if (!$request->user()||!$request->user()->isAdmin()) return response()->json(['message'=>'Action réservée aux administrateurs.'],403);
        $service->update(['is_active'=>!$service->is_active]);
        return response()->json(['service'=>$service->fresh(), 'message'=>$service->is_active?'Service activé':'Service désactivé']);
    }

    public function destroy(Request $request, Service $service) {
        if (!$request->user()||!$request->user()->isAdmin()) return response()->json(['message'=>'Action réservée aux administrateurs.'],403);
        $service->delete();
        return response()->json(['message'=>'Service supprimé !']);
    }
}
