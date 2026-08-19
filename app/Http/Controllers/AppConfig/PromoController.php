<?php

namespace App\Http\Controllers\AppConfig;

use App\AppPromo;
use App\BusinessLocation;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PromoController extends Controller
{
    private function guard()
    {
        if (! auth()->user()->can('business_settings.access')
            && ! auth()->user()->can('celfix.app_config.access')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index(Request $request)
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $promos = AppPromo::where('business_id', $business_id)
            ->with('location')
            ->orderBy('sort_order')->orderByDesc('id')->get();
        return view('app_config.promos.index', compact('promos'));
    }

    public function create(Request $request)
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $locations = BusinessLocation::where('business_id', $business_id)->orderBy('name')->pluck('name', 'id');
        $promo = new AppPromo(['is_active' => true, 'sort_order' => 0]);
        return view('app_config.promos.form', compact('promo', 'locations'));
    }

    public function store(Request $request)
    {
        $this->guard();
        $data = $this->validated($request);
        $business_id = $request->session()->get('user.business_id');
        $data['business_id'] = $business_id;
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('app_promos', 'public');
        }
        AppPromo::create($data);
        return redirect()->route('app-config.promos.index')
            ->with('status', ['success' => 1, 'msg' => 'Promo creada.']);
    }

    public function edit(Request $request, $id)
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $promo = AppPromo::where('business_id', $business_id)->findOrFail($id);
        $locations = BusinessLocation::where('business_id', $business_id)->orderBy('name')->pluck('name', 'id');
        return view('app_config.promos.form', compact('promo', 'locations'));
    }

    public function update(Request $request, $id)
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $promo = AppPromo::where('business_id', $business_id)->findOrFail($id);
        $data = $this->validated($request);
        $data['updated_by'] = auth()->id();
        if ($request->hasFile('image')) {
            // Borra imagen anterior si había
            if ($promo->image_path) Storage::disk('public')->delete($promo->image_path);
            $data['image_path'] = $request->file('image')->store('app_promos', 'public');
        }
        $promo->update($data);
        return redirect()->route('app-config.promos.index')
            ->with('status', ['success' => 1, 'msg' => 'Promo actualizada.']);
    }

    public function destroy(Request $request, $id)
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $promo = AppPromo::where('business_id', $business_id)->findOrFail($id);
        if ($promo->image_path) Storage::disk('public')->delete($promo->image_path);
        $promo->delete();
        return response()->json(['success' => 1, 'msg' => 'Promo eliminada.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'target_location_id' => 'nullable|integer|exists:business_locations,id',
            'category' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'image' => 'nullable|image|max:2048',
        ]);
    }
}
