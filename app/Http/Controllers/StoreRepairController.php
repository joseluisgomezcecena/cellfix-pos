<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\StoreRepair;
use App\Technician;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

/**
 * Módulo REPARACION DE TIENDA — servicio gratis para el cliente donde el técnico
 * repara un equipo y comisiona. Independiente de todo el resto: no crea transactions,
 * no aparece en cortes/dashboard/reporte semanal/denominaciones. Solo se integra
 * con el reporte de técnicos.
 *
 * Permisos:
 *   - VER    → celfix.store_repairs.access
 *   - AGREGAR → admin/gerente (business_settings.access o superadmin)
 *   - EDITAR COMISIÓN → celfix.store_repairs.manage_commissions o admin/gerente
 */
class StoreRepairController extends Controller
{
    /** Puede ver el listado y detalles. */
    private function canView(): bool
    {
        $u = auth()->user();
        return $u->can('celfix.store_repairs.access')
            || $u->can('business_settings.access')
            || $u->can('superadmin');
    }

    /** Puede agregar y editar reparaciones (excepto la comisión). */
    private function canManage(): bool
    {
        $u = auth()->user();
        return $u->can('business_settings.access')
            || $u->can('superadmin');
    }

    /** Puede editar el campo comisión (típicamente admin/gerente). */
    private function canEditCommission(): bool
    {
        $u = auth()->user();
        return $u->can('celfix.store_repairs.manage_commissions')
            || $u->can('business_settings.access')
            || $u->can('superadmin');
    }

    /**
     * GET /store-repairs/search-imei?term=X
     * Autocomplete: IMEIs conocidos en ventas previas + reparaciones previas del módulo.
     * Devuelve un array de {imei, label} para poblar un select2.
     */
    public function searchImei(Request $request)
    {
        if (!$this->canView()) abort(403);
        $business_id = $request->session()->get('user.business_id');
        $term = trim($request->query('term', ''));
        if ($term === '' || strlen($term) < 3) {
            return response()->json(['results' => []]);
        }

        // Variations vendidas (IMEIs de equipos que vendimos) con último cliente conocido.
        $sold = DB::table('variations as v')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->where('p.business_id', $business_id)
            ->where('v.sub_sku', 'like', "%{$term}%")
            ->whereRaw("v.sub_sku REGEXP '^[0-9]{10,20}$'")
            ->select('v.sub_sku as imei', 'p.name as product_name')
            ->limit(20)
            ->get();

        // Previas del propio módulo
        $prev = DB::table('store_repairs')
            ->where('business_id', $business_id)
            ->where('imei', 'like', "%{$term}%")
            ->select('imei')
            ->groupBy('imei')
            ->limit(10)
            ->get();

        $seen = [];
        $out = [];
        foreach ($sold as $r) {
            if (isset($seen[$r->imei])) continue;
            $seen[$r->imei] = 1;
            $out[] = [
                'id' => $r->imei,
                'text' => $r->imei . '  — ' . mb_strimwidth($r->product_name, 0, 50, '…'),
            ];
        }
        foreach ($prev as $r) {
            if (isset($seen[$r->imei])) continue;
            $seen[$r->imei] = 1;
            $out[] = [
                'id' => $r->imei,
                'text' => $r->imei . '  (reparación previa)',
            ];
        }

        return response()->json(['results' => $out]);
    }

    /**
     * GET /store-repairs/lookup-imei?imei=X
     * Cuando el user escribe un IMEI, busca la venta original y devuelve toda la
     * info para pre-llenar el form: producto, cliente, factura, fecha, sucursal.
     * Si el IMEI no está en ventas pero sí en reparaciones previas, devuelve eso.
     */
    public function lookupImei(Request $request)
    {
        if (!$this->canView()) abort(403);
        $business_id = $request->session()->get('user.business_id');
        $imei = trim($request->query('imei', ''));
        if ($imei === '') {
            return response()->json(['found' => false]);
        }

        // Última venta donde salió ese IMEI (por variations.sub_sku)
        $sale = DB::table('transaction_sell_lines as tsl')
            ->join('variations as v', 'v.id', '=', 'tsl.variation_id')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->join('transactions as t', 't.id', '=', 'tsl.transaction_id')
            ->leftJoin('contacts as c', 'c.id', '=', 't.contact_id')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 't.location_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('v.sub_sku', $imei)
            ->select(
                't.id as transaction_id',
                't.invoice_no',
                't.transaction_date',
                't.location_id',
                'bl.name as location_name',
                'p.name as product_name',
                'c.id as contact_id',
                'c.name as customer_name',
                'c.mobile as customer_mobile'
            )
            ->orderByDesc('t.transaction_date')
            ->first();

        if ($sale) {
            return response()->json([
                'found' => true,
                'source' => 'sale',
                'product_name' => $sale->product_name,
                'invoice_no' => $sale->invoice_no,
                'sale_date' => \Carbon\Carbon::parse($sale->transaction_date)->format('d/m/Y'),
                'location_id' => (int) $sale->location_id,
                'location_name' => $sale->location_name,
                'customer_name' => $sale->customer_name,
                'customer_mobile' => $sale->customer_mobile,
                'transaction_id' => (int) $sale->transaction_id,
            ]);
        }

        // Fallback: última reparación previa del mismo IMEI
        $prev = DB::table('store_repairs')
            ->where('business_id', $business_id)
            ->where('imei', $imei)
            ->orderByDesc('created_at')
            ->first();

        if ($prev) {
            return response()->json([
                'found' => true,
                'source' => 'previous_repair',
                'product_name' => null,
                'invoice_no' => null,
                'sale_date' => null,
                'location_id' => (int) $prev->location_id,
                'location_name' => null,
                'customer_name' => $prev->customer_name,
                'customer_mobile' => $prev->customer_mobile,
            ]);
        }

        return response()->json(['found' => false]);
    }

    public function index(Request $request)
    {
        if (!$this->canView()) abort(403, 'Unauthorized action.');
        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $q = DB::table('store_repairs as sr')
                ->leftJoin('business_locations as bl', 'bl.id', '=', 'sr.location_id')
                ->leftJoin('technicians as t', 't.id', '=', 'sr.technician_id')
                ->leftJoin('users as u', 'u.id', '=', 'sr.created_by')
                ->where('sr.business_id', $business_id)
                ->select(
                    'sr.id', 'sr.imei', 'sr.commission', 'sr.status',
                    'sr.customer_name', 'sr.customer_mobile',
                    'sr.created_at', 'sr.delivered_at',
                    'bl.name as location_name',
                    't.name as technician_name',
                    DB::raw("CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) as created_by_name")
                );

            if ($request->filled('location_id')) $q->where('sr.location_id', $request->location_id);
            if ($request->filled('technician_id')) $q->where('sr.technician_id', $request->technician_id);
            if ($request->filled('status')) $q->where('sr.status', $request->status);
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $q->whereBetween('sr.created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59',
                ]);
            }

            return DataTables::of($q)
                ->editColumn('created_at', fn($r) => Carbon::parse($r->created_at)->format('d/m/Y H:i'))
                ->editColumn('commission', fn($r) => '$' . number_format((float) $r->commission, 2))
                ->editColumn('status', function ($r) {
                    $color = [
                        'pending' => 'bg-yellow',
                        'in_progress' => 'bg-blue',
                        'delivered' => 'bg-green',
                        'cancelled' => 'bg-gray',
                    ][$r->status] ?? 'bg-gray';
                    return '<span class="label ' . $color . '">' . StoreRepair::statusLabel($r->status) . '</span>';
                })
                ->addColumn('action', function ($r) {
                    $html = '<div class="btn-group"><button type="button" class="btn btn-xs btn-info dropdown-toggle" data-toggle="dropdown">Acciones <span class="caret"></span></button><ul class="dropdown-menu dropdown-menu-right">';
                    if ($this->canManage() || $this->canEditCommission()) {
                        $html .= '<li><a href="' . route('store-repairs.edit', $r->id) . '"><i class="fa fa-edit"></i> Editar</a></li>';
                    }
                    $html .= '</ul></div>';
                    return $html;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        $locations = BusinessLocation::forDropdown($business_id);
        $technicians = Technician::where('business_id', $business_id)->where('is_active', 1)->orderBy('name')->pluck('name', 'id');

        return view('store_repair.index', compact('locations', 'technicians'));
    }

    public function create(Request $request)
    {
        if (!$this->canManage()) abort(403, 'Solo un administrador o gerente puede agregar reparaciones.');
        $business_id = $request->session()->get('user.business_id');

        $locations = BusinessLocation::forDropdown($business_id);
        $technicians = Technician::where('business_id', $business_id)->where('is_active', 1)->orderBy('name')->pluck('name', 'id');

        return view('store_repair.create', compact('locations', 'technicians'));
    }

    public function store(Request $request)
    {
        if (!$this->canManage()) abort(403);
        $request->validate([
            'location_id' => 'required|integer',
            'technician_id' => 'required|integer',
            'imei' => 'required|string|max:191',
            'customer_name' => 'nullable|string|max:191',
            'customer_mobile' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,delivered,cancelled',
        ]);

        $business_id = $request->session()->get('user.business_id');
        $imei = trim($request->input('imei'));

        // IMEI único solo si hay una reparación activa (pending/in_progress) con ese IMEI.
        // Si ya fue entregada o cancelada, puede volver a entrar (mismo equipo, nueva reparación).
        $exists = StoreRepair::where('business_id', $business_id)
            ->where('imei', $imei)
            ->whereIn('status', ['pending', 'in_progress'])
            ->exists();
        if ($exists) {
            return back()->withInput()->with('status', [
                'success' => 0,
                'msg' => 'Ya existe una reparación ACTIVA con ese IMEI. Entrégala o cancélala antes de crear otra.',
            ]);
        }

        StoreRepair::create([
            'business_id' => $business_id,
            'location_id' => (int) $request->input('location_id'),
            'imei' => $imei,
            'technician_id' => (int) $request->input('technician_id'),
            'commission' => 0, // Siempre arranca en 0; el gerente la ajusta después.
            'customer_name' => $request->input('customer_name') ?: null,
            'customer_mobile' => $request->input('customer_mobile') ?: null,
            'notes' => $request->input('notes') ?: null,
            'status' => $request->input('status'),
            'delivered_at' => $request->input('status') === 'delivered' ? Carbon::now() : null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('store-repairs.index')->with('status', [
            'success' => 1,
            'msg' => 'Reparación registrada.',
        ]);
    }

    public function edit($id)
    {
        if (!$this->canView()) abort(403);
        $business_id = request()->session()->get('user.business_id');
        $repair = StoreRepair::where('business_id', $business_id)->findOrFail($id);

        $locations = BusinessLocation::forDropdown($business_id);
        $technicians = Technician::where('business_id', $business_id)->where('is_active', 1)->orderBy('name')->pluck('name', 'id');
        $can_edit_commission = $this->canEditCommission();
        $can_manage = $this->canManage();

        return view('store_repair.edit', compact('repair', 'locations', 'technicians', 'can_edit_commission', 'can_manage'));
    }

    public function update(Request $request, $id)
    {
        if (!$this->canView()) abort(403);
        $business_id = $request->session()->get('user.business_id');
        $repair = StoreRepair::where('business_id', $business_id)->findOrFail($id);

        // Los campos que se pueden editar dependen del rol:
        // - Manager/admin: todo excepto id/created_by
        // - Con solo manage_commissions: solo la comisión y el estado
        $data = ['updated_by' => auth()->id()];

        if ($this->canEditCommission() && $request->filled('commission')) {
            $data['commission'] = (float) $request->input('commission');
        }

        if ($this->canManage()) {
            $request->validate([
                'location_id' => 'required|integer',
                'technician_id' => 'required|integer',
                'imei' => 'required|string|max:191',
                'status' => 'required|in:pending,in_progress,delivered,cancelled',
            ]);
            $data['location_id'] = (int) $request->input('location_id');
            $data['technician_id'] = (int) $request->input('technician_id');
            $data['imei'] = trim($request->input('imei'));
            $data['customer_name'] = $request->input('customer_name') ?: null;
            $data['customer_mobile'] = $request->input('customer_mobile') ?: null;
            $data['notes'] = $request->input('notes') ?: null;
            $data['status'] = $request->input('status');
        } else {
            // Sin permisos de manage, solo puede tocar status (para marcar como entregada, por ej.)
            if ($request->filled('status')) {
                $request->validate(['status' => 'required|in:pending,in_progress,delivered,cancelled']);
                $data['status'] = $request->input('status');
            }
        }

        // Marca delivered_at cuando pasa a delivered; lo limpia si vuelve a otro estado.
        if (isset($data['status'])) {
            if ($data['status'] === 'delivered' && !$repair->delivered_at) {
                $data['delivered_at'] = Carbon::now();
            } elseif ($data['status'] !== 'delivered') {
                $data['delivered_at'] = null;
            }
        }

        $repair->fill($data)->save();

        return redirect()->route('store-repairs.index')->with('status', [
            'success' => 1,
            'msg' => 'Reparación actualizada.',
        ]);
    }

    public function destroy($id)
    {
        if (!$this->canManage()) abort(403);
        $business_id = request()->session()->get('user.business_id');
        $repair = StoreRepair::where('business_id', $business_id)->findOrFail($id);
        $repair->status = 'cancelled';
        $repair->updated_by = auth()->id();
        $repair->save();
        return response()->json(['success' => 1, 'msg' => 'Reparación cancelada.']);
    }
}
