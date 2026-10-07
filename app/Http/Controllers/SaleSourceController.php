<?php

namespace App\Http\Controllers;

use App\Business;
use App\SaleSource;
use App\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CRUD de orígenes (de dónde se enteró el cliente) + switch del modal + endpoints
 * usados por el POS para preguntar y guardar la selección.
 */
class SaleSourceController extends Controller
{
    private function guard()
    {
        if (! auth()->user()->can('business_settings.access')
            && ! auth()->user()->can('celfix.sale_sources.access')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index(Request $request)
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $business = Business::find($business_id);
        $sources = SaleSource::where('business_id', $business_id)
            ->orderBy('sort_order')->orderBy('id')
            ->get();
        return view('sale_source.index', compact('sources', 'business'));
    }

    public function create(Request $request)
    {
        $this->guard();
        $source = new SaleSource(['is_active' => true, 'sort_order' => 0]);
        return view('sale_source.form', compact('source'));
    }

    public function store(Request $request)
    {
        $this->guard();
        $data = $this->validated($request);
        $data['business_id'] = $request->session()->get('user.business_id');
        SaleSource::create($data);
        return redirect()->route('sale-sources.index')
            ->with('status', ['success' => 1, 'msg' => 'Origen agregado.']);
    }

    public function edit(Request $request, $id)
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $source = SaleSource::where('business_id', $business_id)->findOrFail($id);
        return view('sale_source.form', compact('source'));
    }

    public function update(Request $request, $id)
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $source = SaleSource::where('business_id', $business_id)->findOrFail($id);
        $source->update($this->validated($request));
        return redirect()->route('sale-sources.index')
            ->with('status', ['success' => 1, 'msg' => 'Origen actualizado.']);
    }

    public function destroy(Request $request, $id)
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $source = SaleSource::where('business_id', $business_id)->findOrFail($id);
        // Si tiene ventas asociadas no borramos — solo desactivamos para preservar historia.
        $used = Transaction::where('sale_source_id', $source->id)->exists();
        if ($used) {
            $source->is_active = 0;
            $source->save();
            return response()->json(['success' => 1, 'msg' => 'Tenía ventas asociadas: se desactivó en vez de eliminarse.']);
        }
        $source->delete();
        return response()->json(['success' => 1, 'msg' => 'Origen eliminado.']);
    }

    /**
     * Prende/apaga el switch global del modal.
     */
    public function toggleModal(Request $request)
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $business = Business::findOrFail($business_id);
        $business->sale_source_modal_enabled = (int) $request->input('enabled', 0) ? 1 : 0;
        $business->save();
        return response()->json([
            'success' => 1,
            'enabled' => (bool) $business->sale_source_modal_enabled,
            'msg' => $business->sale_source_modal_enabled ? 'Modal activado.' : 'Modal desactivado.',
        ]);
    }

    /**
     * El POS llama este endpoint después de confirmar el pago para saber si
     * debe mostrar el modal. Devuelve show=true solo si:
     *   - el switch está prendido,
     *   - hay orígenes activos,
     *   - el contact de la venta es CLIENTE NUEVO (nunca había tenido una
     *     venta final en este business, excluyendo la que acabamos de crear).
     */
    public function shouldPrompt(Request $request, $transaction_id)
    {
        $business_id = $request->session()->get('user.business_id');
        $business = Business::find($business_id);
        if (!$business || !$business->sale_source_modal_enabled) {
            return response()->json(['show' => false]);
        }

        $tx = Transaction::where('business_id', $business_id)->find($transaction_id);
        if (!$tx || empty($tx->contact_id)) {
            return response()->json(['show' => false]);
        }

        // Cliente nuevo: solo tiene UNA venta final (la de ahorita) en todo el business.
        $prior = Transaction::where('business_id', $business_id)
            ->where('contact_id', $tx->contact_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->where('id', '!=', $tx->id)
            ->count();
        if ($prior > 0) {
            return response()->json(['show' => false]);
        }

        $sources = SaleSource::where('business_id', $business_id)
            ->where('is_active', 1)
            ->orderBy('sort_order')->orderBy('id')
            ->get(['id', 'name']);
        if ($sources->isEmpty()) {
            return response()->json(['show' => false]);
        }

        return response()->json([
            'show' => true,
            'sources' => $sources,
            'transaction_id' => (int) $tx->id,
        ]);
    }

    /**
     * Asocia el origen seleccionado a la venta. Idempotente (si ya tenía, lo sobrescribe).
     */
    public function attach(Request $request, $transaction_id)
    {
        $business_id = $request->session()->get('user.business_id');
        $source_id = (int) $request->input('source_id', 0);
        if ($source_id <= 0) {
            return response()->json(['success' => 0, 'msg' => 'Fuente inválida.'], 422);
        }
        $source = SaleSource::where('business_id', $business_id)->where('is_active', 1)->find($source_id);
        if (!$source) {
            return response()->json(['success' => 0, 'msg' => 'Fuente no válida.'], 422);
        }
        $updated = Transaction::where('business_id', $business_id)
            ->where('id', $transaction_id)
            ->update(['sale_source_id' => $source->id]);
        if (!$updated) {
            return response()->json(['success' => 0, 'msg' => 'Venta no encontrada.'], 404);
        }
        return response()->json(['success' => 1, 'msg' => 'Origen registrado.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:150',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);
    }
}
