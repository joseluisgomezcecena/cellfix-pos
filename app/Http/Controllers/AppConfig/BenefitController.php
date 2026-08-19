<?php

namespace App\Http\Controllers\AppConfig;

use App\AppBenefit;
use App\BusinessLocation;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BenefitController extends Controller
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
        $benefits = AppBenefit::where('business_id', $business_id)
            ->with('location')
            ->orderBy('sort_order')->orderByDesc('id')->get();
        return view('app_config.benefits.index', compact('benefits'));
    }

    public function create(Request $request)
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $locations = BusinessLocation::where('business_id', $business_id)->orderBy('name')->pluck('name', 'id');
        $benefit = new AppBenefit(['is_active' => true, 'value_type' => 'amount', 'sort_order' => 0]);
        return view('app_config.benefits.form', compact('benefit', 'locations'));
    }

    public function store(Request $request)
    {
        $this->guard();
        $data = $this->validated($request);
        $business_id = $request->session()->get('user.business_id');
        $data['business_id'] = $business_id;
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        AppBenefit::create($data);
        return redirect()->route('app-config.benefits.index')
            ->with('status', ['success' => 1, 'msg' => 'Beneficio creado.']);
    }

    public function edit(Request $request, $id)
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $benefit = AppBenefit::where('business_id', $business_id)->findOrFail($id);
        $locations = BusinessLocation::where('business_id', $business_id)->orderBy('name')->pluck('name', 'id');
        return view('app_config.benefits.form', compact('benefit', 'locations'));
    }

    public function update(Request $request, $id)
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $benefit = AppBenefit::where('business_id', $business_id)->findOrFail($id);
        $data = $this->validated($request);
        $data['updated_by'] = auth()->id();
        $benefit->update($data);
        return redirect()->route('app-config.benefits.index')
            ->with('status', ['success' => 1, 'msg' => 'Beneficio actualizado.']);
    }

    public function destroy(Request $request, $id)
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        AppBenefit::where('business_id', $business_id)->findOrFail($id)->delete();
        return response()->json(['success' => 1, 'msg' => 'Beneficio eliminado.']);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'value_type' => 'required|in:amount,percent,text',
            'value' => 'nullable|numeric|min:0',
            'value_text' => 'nullable|string|max:100',
            'min_purchase' => 'nullable|numeric|min:0',
            'conditions' => 'nullable|string',
            'target_location_id' => 'nullable|integer|exists:business_locations,id',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);
        // Consistencia: si es text, value=null; si es amount/percent, value_text=null
        if ($data['value_type'] === 'text') {
            $data['value'] = null;
        } else {
            $data['value_text'] = null;
        }
        return $data;
    }
}
