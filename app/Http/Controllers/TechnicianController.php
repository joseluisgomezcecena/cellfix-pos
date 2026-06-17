<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\RepairProductCommission;
use App\Technician;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TechnicianController extends Controller
{
    public function index()
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $technicians = Technician::where('business_id', $business_id)
                ->with('locations')
                ->select(['id', 'name', 'phone', 'email', 'is_active']);

            return DataTables::of($technicians)
                ->addColumn('action', function ($row) {
                    $html = '<button type="button" data-href="' . route('technicians.edit', [$row->id]) .
                        '" class="btn btn-xs btn-primary btn-modal" data-container=".technician_modal">' .
                        '<i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</button>';
                    $html .= ' <button type="button" data-href="' . route('technicians.destroy', [$row->id]) .
                        '" class="btn btn-xs btn-danger delete_technician_button">' .
                        '<i class="glyphicon glyphicon-trash"></i> ' . __('messages.delete') . '</button>';

                    return $html;
                })
                ->addColumn('locations_list', function ($row) {
                    return $row->locations->pluck('name')->implode(', ');
                })
                ->editColumn('is_active', function ($row) {
                    return $row->is_active
                        ? '<span class="label label-success">' . __('business.is_active') . '</span>'
                        : '<span class="label label-default">' . __('lang_v1.inactive') . '</span>';
                })
                ->rawColumns(['action', 'is_active'])
                ->make(true);
        }

        return view('technician.index');
    }

    /**
     * Modal con la lista de productos de marca Reparaciones para asignar comisión por producto.
     * La comisión es global (igual para todos los técnicos).
     */
    public function repairCommissions()
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // Incluimos productos de las marcas Reparaciones, Servicios y Cortos —
        // las tres se consideran trabajos de técnico (mismo regex que en el POS para
        // decidir si mostrar el selector de técnico al vender).
        $brand_ids = DB::table('brands')
            ->where('business_id', $business_id)
            ->where(function ($q) {
                $q->where('name', 'REGEXP', '(reparac|servic|corto)')
                    ->orWhereRaw('LOWER(name) REGEXP "(reparac|servic|corto)"');
            })
            ->pluck('id');

        $products = collect();
        if ($brand_ids->isNotEmpty()) {
            $products = DB::table('products as p')
                ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
                ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
                ->where('p.business_id', $business_id)
                ->whereIn('p.brand_id', $brand_ids)
                ->where('p.is_inactive', 0)
                ->select('p.id', 'p.name', 'p.sku', 'b.name as brand', DB::raw("COALESCE(c.name, '-') as category"))
                ->orderBy('brand')->orderBy('category')->orderBy('p.name')
                ->get();
        }

        $existing = RepairProductCommission::where('business_id', $business_id)
            ->pluck('commission_amount', 'product_id')->toArray();

        return view('technician.repair_commissions_modal', compact('products', 'existing'));
    }

    public function saveRepairCommissions(Request $request)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $data = json_decode($request->input('commissions'), true);
            if (!is_array($data)) {
                $data = [];
            }

            $now = now();
            $rows = [];
            foreach ($data as $product_id => $amount) {
                $rows[] = [
                    'business_id' => $business_id,
                    'product_id' => (int) $product_id,
                    'commission_amount' => (float) $amount,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::beginTransaction();
            foreach (array_chunk($rows, 200) as $chunk) {
                RepairProductCommission::upsert($chunk, ['business_id', 'product_id'], ['commission_amount', 'updated_at']);
            }
            DB::commit();

            $output = ['success' => 1, 'msg' => __('lang_v1.commissions_saved')];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    public function create()
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $locations = BusinessLocation::forDropdown($business_id);

        return view('technician.create', compact('locations'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'location_ids' => 'array',
        ]);

        try {
            $input = $request->only(['name', 'phone', 'email', 'notes', 'commission_per_repair']);
            $input['commission_per_repair'] = (float) ($input['commission_per_repair'] ?? 0);
            $input['business_id'] = $request->session()->get('user.business_id');
            $input['is_active'] = $request->has('is_active') ? 1 : 0;

            $technician = Technician::create($input);

            $location_ids = $request->input('location_ids', []);
            if (!empty($location_ids)) {
                $technician->locations()->sync($location_ids);
            }

            $output = ['success' => true, 'msg' => __('lang_v1.added_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    public function edit($id)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $technician = Technician::where('business_id', $business_id)
                ->with('locations')
                ->findOrFail($id);

            $locations = BusinessLocation::forDropdown($business_id);

            return view('technician.edit', compact('technician', 'locations'));
        }
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'location_ids' => 'array',
        ]);

        try {
            $business_id = $request->session()->get('user.business_id');
            $technician = Technician::where('business_id', $business_id)->findOrFail($id);

            $input = $request->only(['name', 'phone', 'email', 'notes', 'commission_per_repair']);
            $input['commission_per_repair'] = (float) ($input['commission_per_repair'] ?? 0);
            $input['is_active'] = $request->has('is_active') ? 1 : 0;
            $technician->update($input);

            $technician->locations()->sync($request->input('location_ids', []));

            $output = ['success' => true, 'msg' => __('lang_v1.updated_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * Weekly report per technician — every repair line they worked on in the chosen
     * Mon–Sun week, grouped by day, plus commission summary.
     */
    public function report(\Illuminate\Http\Request $request)
    {
        if (!auth()->user()->can('business_settings.access') && !auth()->user()->can('view_purchase_n_sell_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        // Default start: this week's Monday
        $start_date = $request->get('start_date');
        if (empty($start_date)) {
            $today = \Carbon\Carbon::now();
            // Semana de SÁBADO a VIERNES (no Mon→Sun). Sat dayOfWeek=6.
            $daysSinceStart = ($today->dayOfWeek + 1) % 7;
            $start_date = $today->copy()->subDays($daysSinceStart)->toDateString();
        }
        $start = \Carbon\Carbon::parse($start_date)->startOfDay();
        $end = $start->copy()->addDays(6)->endOfDay();

        $location_id = $request->get('location_id');

        $data = $this->buildReportData($business_id, $start, $end, $location_id);

        $locations = \App\BusinessLocation::forDropdown($business_id);

        return view('technician.report', compact(
            'data', 'start_date', 'start', 'end', 'locations', 'location_id'
        ));
    }

    /**
     * Build the data array used by both the web view and the Excel export.
     * Returns an array of technicians with their repair lines grouped by day.
     */
    public function buildReportData($business_id, $start, $end, $location_id = null)
    {
        $tech_query = Technician::where('business_id', $business_id)->orderBy('name');
        $technicians = $tech_query->get();

        // Comisión por producto (global, igual para todos los técnicos)
        $repair_commissions = DB::table('repair_product_commissions')
            ->where('business_id', $business_id)
            ->pluck('commission_amount', 'product_id')
            ->toArray();

        $query = \DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 't.id', '=', 'tsl.transaction_id')
            ->join('products as p', 'p.id', '=', 'tsl.product_id')
            ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
            ->leftJoin('contacts as c', 'c.id', '=', 't.contact_id')
            ->leftJoin('users as u', 'u.id', '=', 't.created_by')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 't.location_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->whereNotNull('tsl.technician_id')
            ->select(
                'tsl.id as line_id',
                'tsl.transaction_id',
                'tsl.technician_id',
                'tsl.product_id',
                'tsl.quantity',
                'tsl.unit_price_inc_tax',
                'tsl.line_discount_amount',
                'tsl.repair_entry_date',
                'tsl.repair_anticipo',
                'tsl.technician_commission_override',
                't.invoice_no',
                't.transaction_date',
                't.location_id',
                't.final_total',
                'bl.name as location_name',
                'p.name as product_name',
                'b.name as brand_name',
                'c.name as customer_name',
                \DB::raw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as vendedor")
            );

        if (!empty($location_id)) {
            $query->where('t.location_id', $location_id);
        }

        $lines = $query->orderBy('t.transaction_date')->get();

        // Lookup payment info per transaction: pesos vs dollars + exchange rate
        $transaction_ids = $lines->pluck('transaction_id')->unique()->toArray();
        $payments_by_tx = [];
        if (!empty($transaction_ids)) {
            $payments = \DB::table('transaction_payments')
                ->whereIn('transaction_id', $transaction_ids)
                ->where('is_return', 0)
                ->select('transaction_id', 'method', 'amount', 'denomination_breakdown')
                ->get();
            foreach ($payments as $p) {
                $tx_id = $p->transaction_id;
                if (!isset($payments_by_tx[$tx_id])) {
                    $payments_by_tx[$tx_id] = ['used_usd' => false, 'exchange_rate' => null, 'paid' => 0];
                }
                $payments_by_tx[$tx_id]['paid'] += (float) $p->amount;
                if ($p->method === 'cash' && !empty($p->denomination_breakdown)) {
                    $bd = is_string($p->denomination_breakdown)
                        ? json_decode($p->denomination_breakdown, true)
                        : $p->denomination_breakdown;
                    if (is_array($bd) && !empty($bd['usd']) && is_array($bd['usd']) && !empty(array_filter($bd['usd']))) {
                        $payments_by_tx[$tx_id]['used_usd'] = true;
                        if (!empty($bd['exchange_rate'])) {
                            $payments_by_tx[$tx_id]['exchange_rate'] = (float) $bd['exchange_rate'];
                        }
                    }
                }
            }
        }

        // Group by technician → day
        $report = [];
        foreach ($technicians as $tech) {
            $tech_lines = $lines->where('technician_id', $tech->id);

            $by_day = [];
            $week_total = 0;
            $week_count = 0;
            $week_commission = 0;
            foreach ($tech_lines as $line) {
                // unit_price_inc_tax ya incluye el descuento de línea aplicado (UltimatePOS lo
                // guarda post-descuento). Restar line_discount_amount otra vez doble-cuenta el
                // descuento y dejaba líneas con descuento mostrando $0 en el reporte.
                $line_total = (float) $line->unit_price_inc_tax * (float) $line->quantity;
                // Si la línea tiene un override manual de comisión, usar ese; si no, la comisión por producto.
                $commission_overridden = !is_null($line->technician_commission_override);
                $line_commission = $commission_overridden
                    ? (float) $line->technician_commission_override
                    : (float) ($repair_commissions[$line->product_id] ?? 0);
                $day_key = \Carbon\Carbon::parse($line->transaction_date)->toDateString();
                if (!isset($by_day[$day_key])) {
                    $by_day[$day_key] = [
                        'date' => \Carbon\Carbon::parse($day_key),
                        'lines' => [],
                        'subtotal' => 0,
                        'count' => 0,
                    ];
                }
                $pay_info = $payments_by_tx[$line->transaction_id] ?? ['used_usd' => false, 'exchange_rate' => null, 'paid' => 0];
                $anticipo = (float) ($line->repair_anticipo ?? 0);
                // DEBE = saldo real según pagos (no solo el anticipo); reparte el pago por línea.
                $tx_total = (float) ($line->final_total ?: 0);
                $tx_paid = (float) ($pay_info['paid'] ?? 0);
                $paid_share = $tx_total > 0 ? ($tx_paid * $line_total / $tx_total) : $tx_paid;
                $debe = max(0, round($line_total - $paid_share, 2));

                $by_day[$day_key]['lines'][] = [
                    'line_id' => $line->line_id,
                    'invoice_no' => $line->invoice_no,
                    'customer' => $line->customer_name ?? '—',
                    'product' => $line->product_name,
                    'location' => $line->location_name,
                    'total' => $line_total,
                    'anticipo' => $anticipo,
                    'debe' => $debe,
                    'tipo_pago' => $pay_info['used_usd'] ? 'D' : 'P',
                    'tipo_cambio' => $pay_info['used_usd'] ? $pay_info['exchange_rate'] : null,
                    'total_en_pesos' => $line_total,
                    'entry_date' => $line->repair_entry_date,
                    'transaction_date' => $line->transaction_date,
                    'vendor' => trim($line->vendedor),
                    'commission' => $line_commission,
                    'commission_overridden' => $commission_overridden,
                ];
                $by_day[$day_key]['subtotal'] += $line_total;
                $by_day[$day_key]['count']++;
                $week_total += $line_total;
                $week_count++;
                $week_commission += $line_commission;
            }

            $report[] = [
                'technician' => $tech,
                'by_day' => $by_day,
                'week_total' => $week_total,
                'week_count' => $week_count,
                'commission_due' => $week_commission,
            ];
        }

        return $report;
    }

    public function exportReport(\Illuminate\Http\Request $request)
    {
        if (!auth()->user()->can('business_settings.access') && !auth()->user()->can('view_purchase_n_sell_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $start_date = $request->get('start_date');
        if (empty($start_date)) {
            $today = \Carbon\Carbon::now();
            // Semana de SÁBADO a VIERNES (no Mon→Sun). Sat dayOfWeek=6.
            $daysSinceStart = ($today->dayOfWeek + 1) % 7;
            $start_date = $today->copy()->subDays($daysSinceStart)->toDateString();
        }
        $start = \Carbon\Carbon::parse($start_date)->startOfDay();
        $end = $start->copy()->addDays(6)->endOfDay();
        $location_id = $request->get('location_id');

        $data = $this->buildReportData($business_id, $start, $end, $location_id);

        $filename = 'reporte_tecnicos_' . $start->toDateString() . '_a_' . $end->toDateString() . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\TechniciansReportExport($data, $start, $end),
            $filename
        );
    }

    public function destroy($id)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $technician = Technician::where('business_id', $business_id)->findOrFail($id);
            $technician->delete();

            $output = ['success' => true, 'msg' => __('lang_v1.deleted_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * Establece o limpia un override manual del pago al técnico para UNA línea de venta.
     * - amount NULL o vacío => limpia el override y vuelve a usar la comisión por producto.
     * - amount >= 0         => guarda el override y lo usa en el reporte.
     */
    public function updateCommissionOverride(\Illuminate\Http\Request $request, $line_id)
    {
        if (!auth()->user()->can('business_settings.access')) {
            return response()->json(['success' => 0, 'msg' => 'No autorizado'], 403);
        }

        $request->validate([
            'amount' => 'nullable|numeric|min:0',
        ]);

        $business_id = $request->session()->get('user.business_id');

        try {
            // Verificar que la línea pertenezca a este business
            $line = \DB::table('transaction_sell_lines as tsl')
                ->join('transactions as t', 't.id', '=', 'tsl.transaction_id')
                ->where('tsl.id', $line_id)
                ->where('t.business_id', $business_id)
                ->select('tsl.id')
                ->first();

            if (!$line) {
                return response()->json(['success' => 0, 'msg' => 'Línea no encontrada']);
            }

            $amount = $request->input('amount', null);
            $value = ($amount === null || $amount === '') ? null : (float) $amount;

            \DB::table('transaction_sell_lines')
                ->where('id', $line_id)
                ->update(['technician_commission_override' => $value]);

            return response()->json([
                'success' => 1,
                'msg' => $value === null ? 'Pago restaurado al monto calculado' : 'Pago actualizado correctamente',
                'new_value' => $value,
            ]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            return response()->json(['success' => 0, 'msg' => __('messages.something_went_wrong')]);
        }
    }
}
