<?php

namespace App\Http\Controllers\AppConfig;

use App\BusinessLocation;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Admin de sucursales para la app Flutter. Aquí se editan los campos que
 * la app consume: teléfono público, horarios por día, coordenadas GPS,
 * y si la sucursal se muestra o no en la app.
 *
 * NO edita los datos operativos del POS (nombre, dirección postal, etc.)
 * — esos se administran desde /business-location.
 */
class LocationController extends Controller
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
        $locations = BusinessLocation::where('business_id', $business_id)
            ->orderBy('name')->get();
        return view('app_config.locations.index', compact('locations'));
    }

    public function edit(Request $request, $id)
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $location = BusinessLocation::where('business_id', $business_id)->findOrFail($id);
        // Días de la semana en orden. hours_json guarda por clave: mon,tue,wed,thu,fri,sat,sun
        $days = [
            'mon' => 'Lunes', 'tue' => 'Martes', 'wed' => 'Miércoles',
            'thu' => 'Jueves', 'fri' => 'Viernes', 'sat' => 'Sábado', 'sun' => 'Domingo',
        ];
        $hours = is_string($location->hours_json)
            ? (json_decode($location->hours_json, true) ?: [])
            : ($location->hours_json ?? []);
        return view('app_config.locations.edit', compact('location', 'days', 'hours'));
    }

    public function update(Request $request, $id)
    {
        $this->guard();
        $request->validate([
            'phone_app' => 'nullable|string|max:30',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_public_in_app' => 'nullable|in:0,1',
            'hours' => 'nullable|array',
        ]);
        $business_id = $request->session()->get('user.business_id');
        $location = BusinessLocation::where('business_id', $business_id)->findOrFail($id);

        // Normaliza horarios: {mon: {open:'09:00', close:'18:00'}, ..., sat: {closed:true}}.
        // Si el día trae 'closed' o open/close vacíos → guardamos {closed: true}.
        $hours_in = $request->input('hours', []);
        $hours_out = [];
        foreach (['mon','tue','wed','thu','fri','sat','sun'] as $d) {
            $h = $hours_in[$d] ?? [];
            $closed = !empty($h['closed']);
            $open = trim($h['open'] ?? '');
            $close = trim($h['close'] ?? '');
            if ($closed || $open === '' || $close === '') {
                $hours_out[$d] = ['closed' => true];
            } else {
                $hours_out[$d] = ['open' => $open, 'close' => $close];
            }
        }

        $location->phone_app = $request->input('phone_app');
        $location->latitude = $request->input('latitude');
        $location->longitude = $request->input('longitude');
        $location->is_public_in_app = $request->input('is_public_in_app') ? 1 : 0;
        $location->hours_json = $hours_out;
        $location->save();

        return redirect()->route('app-config.locations.index')
            ->with('status', ['success' => 1, 'msg' => 'Sucursal actualizada.']);
    }
}
