<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function index() { return response()->json(Service::orderBy('sort_order')->get()); }
    public function show(Service $service) { return response()->json($service); }

    public function store(Request $request) {
        if (!$request->user() || !$request->user()->isAdmin()) return response()->json(['message'=>'Action réservée aux administrateurs.'],403);
        $service = Service::create([
            'name' => $request->name, 'slug' => Str::slug($request->name.'-'.time()),
            'price' => $request->price, 'duration' => $request->duration??'', 'description' => $request->description??'',
            'icon' => $request->icon??'shirt-outline', 'color' => $request->color??'#CFA866',
            'category' => $request->category??'robe', 'is_active' => true, 'sort_order' => Service::max('sort_order')+1,
        ]);
        return response()->json(['service'=>$service, 'message'=>'Service créé !'], 201);
    }

    public function update(Request $request, Service $service) {
        if (!$request->user()||!$request->user()->isAdmin()) return response()->json(['message'=>'Action réservée aux administrateurs.'],403);
        $service->update($request->only(['name','price','duration','description','icon','color','category']));
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
