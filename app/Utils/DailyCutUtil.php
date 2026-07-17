<?php

namespace App\Utils;

use App\DailyCut;
use App\BusinessLocation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DailyCutUtil
{
    /**
     * Computes the daily cut for a given business + location + date.
     * Returns the data structure (without persisting). Pass to upsert() to save.
     */
    public function compute($business_id, $location_id, $date)
    {
        $start = Carbon::parse($date)->startOfDay()->toDateTimeString();
        $end = Carbon::parse($date)->endOfDay()->toDateTimeString();

        // ---- Sales transactions (final only) ----
        // Reglas:
        //   A) Venta normal sin apartado    → cuenta por transaction_date.
        //   B) Apartado COMPLETADO hoy      → cuenta por layaways.completed_at = hoy.
        //                                     Cuando aparece, TODOS sus pagos acumulados se
        //                                     suman ese día (no se distribuyen por paid_on).
        //   C) Apartado todavía ACTIVO      → NO aparece en ningún cut. El dinero está
        //                                     físicamente en la caja del equipo, no en
        //                                     la caja registradora.
        $sales = DB::table('transactions as t')
            ->leftJoin('layaways as l', 'l.id', '=', 't.layaway_id')
            ->where('t.business_id', $business_id)
            ->where('t.location_id', $location_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where(function ($q) use ($start, $end) {
                // Regla A: venta normal sin layaway_id → filtra por transaction_date
                $q->where(function ($q2) use ($start, $end) {
                    $q2->whereNull('t.layaway_id')
                        ->whereBetween('t.transaction_date', [$start, $end]);
                })
                // Regla B: apartado completado hoy → filtra por layaways.completed_at
                ->orWhere(function ($q2) use ($start, $end) {
                    $q2->whereNotNull('t.layaway_id')
                        ->whereNotNull('l.completed_at')
                        ->whereBetween('l.completed_at', [$start, $end]);
                });
                // Regla C: apartado activo → automáticamente excluido (no matchea A ni B)
            })
            ->select('t.id', 't.final_total', 't.total_before_tax', 't.tax_amount', 't.discount_amount')
            ->get();

        $sale_ids = $sales->pluck('id')->toArray();
        $total_sales = $sales->sum('final_total');
        $total_transactions = $sales->count();

        // ---- Sales by brand (Reparaciones, Servicios, Equipos, Accesorios, Desbloqueos, etc.) ----
        $sales_by_brand = [];
        if (!empty($sale_ids)) {
            $by_brand = DB::table('transaction_sell_lines as tsl')
                ->join('products as p', 'p.id', '=', 'tsl.product_id')
                ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
                ->whereIn('tsl.transaction_id', $sale_ids)
                ->groupBy('b.id', 'b.name')
                ->select(
                    'b.name as brand_name',
                    DB::raw('COALESCE(SUM(tsl.quantity), 0) as quantity'),
                    // unit_price_inc_tax ya incluye el descuento de línea (UltimatePOS lo guarda
                    // post-descuento). Restar line_discount_amount otra vez doble-contaba el
                    // descuento y bajaba los subtotales por marca cuando había descuento.
                    DB::raw('COALESCE(SUM(tsl.unit_price_inc_tax * tsl.quantity), 0) as subtotal')
                )
                ->get();

            foreach ($by_brand as $row) {
                $sales_by_brand[] = [
                    'brand' => $row->brand_name ?? 'Sin marca',
                    'quantity' => (float) $row->quantity,
                    'subtotal' => (float) $row->subtotal,
                ];
            }
        }

        // ---- Payments breakdown ----
        $payments = collect();
        if (!empty($sale_ids)) {
            $payments = DB::table('transaction_payments')
                ->whereIn('transaction_id', $sale_ids)
                ->select('id', 'amount', 'method', 'card_type', 'card_terminal_id', 'is_return', 'denomination_breakdown')
                ->get();
        }

        // Net per method (subtract returns)
        $totals = ['cash' => 0, 'card' => 0, 'bank_transfer' => 0, 'cheque' => 0, 'other' => 0];
        $card_by_type = ['debit' => 0, 'credit' => 0, 'amex' => 0, 'unknown' => 0];
        $card_by_terminal = []; // [terminal_id => total]

        // Separate denomination totals per currency
        $mxn_denominations = []; // [face_value => count_total]
        $mxn_coins = 0;
        $usd_denominations = [];
        $usd_coins = 0;
        $usd_total_in_mxn = 0; // accumulated equivalent in MXN

        foreach ($payments as $p) {
            $signed = $p->is_return == 1 ? -1 * $p->amount : $p->amount;
            $method = $p->method ?? 'other';

            if (in_array($method, ['cash', 'card', 'bank_transfer', 'cheque'])) {
                $totals[$method] += $signed;
            } else {
                $totals['other'] += $signed;
            }

            if ($method == 'card' && $p->is_return == 0) {
                $type = $p->card_type ?: 'unknown';
                if (!isset($card_by_type[$type])) {
                    $card_by_type[$type] = 0;
                }
                $card_by_type[$type] += $p->amount;

                if (!empty($p->card_terminal_id)) {
                    if (!isset($card_by_terminal[$p->card_terminal_id])) {
                        $card_by_terminal[$p->card_terminal_id] = 0;
                    }
                    $card_by_terminal[$p->card_terminal_id] += $p->amount;
                }
            }

            // Denomination breakdown for cash payments (not returns)
            if ($method == 'cash' && $p->is_return == 0 && !empty($p->denomination_breakdown)) {
                $bd = is_string($p->denomination_breakdown)
                    ? json_decode($p->denomination_breakdown, true)
                    : $p->denomination_breakdown;

                if (is_array($bd)) {
                    // New nested format
                    if (isset($bd['mxn']) || isset($bd['usd'])) {
                        if (!empty($bd['mxn']) && is_array($bd['mxn'])) {
                            foreach ($bd['mxn'] as $face => $count) {
                                if ($face === 'coins') {
                                    $mxn_coins += (float) $count;
                                } else {
                                    $mxn_denominations[$face] = ($mxn_denominations[$face] ?? 0) + (int) $count;
                                }
                            }
                        }
                        if (!empty($bd['usd']) && is_array($bd['usd'])) {
                            foreach ($bd['usd'] as $face => $count) {
                                if ($face === 'coins') {
                                    $usd_coins += (float) $count;
                                } else {
                                    $usd_denominations[$face] = ($usd_denominations[$face] ?? 0) + (int) $count;
                                }
                            }
                        }
                        if (!empty($bd['usd_in_mxn'])) {
                            $usd_total_in_mxn += (float) $bd['usd_in_mxn'];
                        }
                    } else {
                        // Old flat format — assume MXN
                        foreach ($bd as $face => $count) {
                            if ($face === 'coins') {
                                $mxn_coins += (float) $count;
                            } else {
                                $mxn_denominations[$face] = ($mxn_denominations[$face] ?? 0) + (int) $count;
                            }
                        }
                    }
                }
            }
        }

        // Resolve terminal names
        $terminal_breakdown = [];
        if (!empty($card_by_terminal)) {
            $terminals = DB::table('card_terminals')
                ->whereIn('id', array_keys($card_by_terminal))
                ->select('id', 'name', 'bank')
                ->get()
                ->keyBy('id');

            foreach ($card_by_terminal as $tid => $amount) {
                $t = $terminals->get($tid);
                $terminal_breakdown[] = [
                    'terminal_id' => $tid,
                    'name' => $t->name ?? '—',
                    'bank' => $t->bank ?? null,
                    'total' => (float) $amount,
                ];
            }
        }

        // ---- Expenses for the day ----
        $total_expenses = (float) DB::table('transactions')
            ->where('business_id', $business_id)
            ->where('location_id', $location_id)
            ->where('type', 'expense')
            ->where('status', 'final')
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('final_total');

        // ---- Refunds: reembolsos por devoluciones (sell_return) del día ----
        // El corte debe reflejar el dinero que SALIÓ del cajón por reembolsos, en
        // el mismo método en que se le devolvió al cliente. Sin esto el corte
        // estaba inflado — quedaba diciendo que había más cash del que físicamente
        // había, cuando la cajera había reembolsado algo en efectivo.
        //
        // Bug reportado: "SI SE LE REGRESA DINERO EN EFECTIVO NO LO MARCA COMO GASTO".
        $refunds_by_method = ['cash' => 0, 'card' => 0, 'bank_transfer' => 0, 'cheque' => 0, 'other' => 0];
        $refunds_total = 0;
        $refund_payments = DB::table('transaction_payments as tp')
            ->join('transactions as t', 't.id', '=', 'tp.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.location_id', $location_id)
            ->where('t.type', 'sell_return')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start, $end])
            ->where('tp.is_return', 0)
            ->select('tp.amount', 'tp.method')
            ->get();
        foreach ($refund_payments as $rp) {
            $m = $rp->method ?? 'other';
            if (! array_key_exists($m, $refunds_by_method)) {
                $m = 'other';
            }
            $refunds_by_method[$m] += (float) $rp->amount;
            $refunds_total += (float) $rp->amount;
        }
        // Los reembolsos NO se restan de $totals['cash']/'card'/etc. Se muestran como
        // línea aparte en el corte ("Reembolsos entregados") y el "Efectivo neto a
        // entregar" ya considera el cash reembolsado. Si los restáramos aquí, la
        // fórmula "cambio = bruto − total_cash" del view los interpretaría como
        // cambio extra (bug detectado en prueba con 0 ventas + reembolso cash).

        // ---- Refunds detail: lista de devoluciones del día con producto/cliente/método/hora ----
        // Para que el gerente pueda auditar cada devolución individual desde el corte.
        // Nota UltimatePOS: la devolución (sell_return) NO tiene sus propias sell_lines.
        // Los productos devueltos están en la venta original (return_parent_id) con
        // quantity_returned > 0.
        $returns_detail = [];
        $return_txs = DB::table('transactions as t')
            ->leftJoin('contacts as c', 'c.id', '=', 't.contact_id')
            ->where('t.business_id', $business_id)
            ->where('t.location_id', $location_id)
            ->where('t.type', 'sell_return')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start, $end])
            ->select(
                't.id',
                't.invoice_no',
                't.transaction_date',
                't.final_total',
                't.return_parent_id',
                'c.name as customer_name'
            )
            ->orderBy('t.transaction_date', 'desc')
            ->get();

        if ($return_txs->isNotEmpty()) {
            $return_ids = $return_txs->pluck('id')->toArray();
            $parent_ids = $return_txs->pluck('return_parent_id')->filter()->toArray();

            // Productos devueltos: viven en las líneas de la venta padre con quantity_returned > 0
            $lines_by_parent = collect();
            if (! empty($parent_ids)) {
                $lines_by_parent = DB::table('transaction_sell_lines as tsl')
                    ->join('products as p', 'p.id', '=', 'tsl.product_id')
                    ->whereIn('tsl.transaction_id', $parent_ids)
                    ->where('tsl.quantity_returned', '>', 0)
                    ->select(
                        'tsl.transaction_id as parent_id',
                        'p.name as product_name',
                        'tsl.quantity_returned'
                    )
                    ->get()
                    ->groupBy('parent_id');
            }

            // Métodos de reembolso por cada devolución
            $methods_by_tx = DB::table('transaction_payments')
                ->whereIn('transaction_id', $return_ids)
                ->where('is_return', 0)
                ->select('transaction_id', 'method', 'amount')
                ->get()
                ->groupBy('transaction_id');

            foreach ($return_txs as $rt) {
                $products = [];
                foreach (($lines_by_parent[$rt->return_parent_id] ?? []) as $ln) {
                    $qty = (float) $ln->quantity_returned;
                    if ($qty > 0) {
                        $products[] = [
                            'name' => $ln->product_name,
                            'qty' => $qty,
                        ];
                    }
                }
                $methods = [];
                foreach (($methods_by_tx[$rt->id] ?? []) as $mp) {
                    $methods[] = [
                        'method' => $mp->method ?? 'other',
                        'amount' => (float) $mp->amount,
                    ];
                }
                $returns_detail[] = [
                    'invoice_no' => $rt->invoice_no,
                    'datetime' => $rt->transaction_date,
                    'customer' => $rt->customer_name ?? '—',
                    'total' => (float) $rt->final_total,
                    'products' => $products,
                    'methods' => $methods,
                ];
            }
        }

        // ---- Build summary JSON ----
        // Compute MXN-equivalent subtotals for the cash report
        $mxn_subtotal = $mxn_coins;
        foreach ($mxn_denominations as $face => $count) {
            $mxn_subtotal += (float) $face * (int) $count;
        }
        $usd_subtotal = $usd_coins;
        foreach ($usd_denominations as $face => $count) {
            $usd_subtotal += (float) $face * (int) $count;
        }

        $summary = [
            'sales_by_brand' => $sales_by_brand,
            'card_by_type' => $card_by_type,
            'card_by_terminal' => $terminal_breakdown,
            // Legacy keys (kept for backward compatibility) — MXN values
            'denominations' => $mxn_denominations,
            'coins_total' => $mxn_coins,
            // New per-currency breakdown
            'mxn' => [
                'denominations' => $mxn_denominations,
                'coins' => $mxn_coins,
                'subtotal' => $mxn_subtotal,
            ],
            'usd' => [
                'denominations' => $usd_denominations,
                'coins' => $usd_coins,
                'subtotal' => $usd_subtotal,
                'in_mxn' => $usd_total_in_mxn,
            ],
            // Desglose de reembolsos entregados a clientes en el día
            'refunds' => [
                'total' => $refunds_total,
                'by_method' => $refunds_by_method,
                'detail' => $returns_detail,
            ],
        ];

        return [
            'business_id' => $business_id,
            'location_id' => $location_id,
            'cut_date' => Carbon::parse($date)->toDateString(),
            'total_sales' => (float) $total_sales,
            'total_cash' => (float) $totals['cash'],
            'total_card' => (float) $totals['card'],
            'total_transfer' => (float) $totals['bank_transfer'],
            'total_cheque' => (float) $totals['cheque'],
            'total_other' => (float) $totals['other'],
            'total_expenses' => $total_expenses,
            'total_transactions' => $total_transactions,
            'summary' => $summary,
        ];
    }

    /**
     * Computes and persists (UPSERT) the daily cut.
     */
    public function upsert($business_id, $location_id, $date, $generated_by = null)
    {
        $data = $this->compute($business_id, $location_id, $date);
        $data['generated_at'] = Carbon::now();
        $data['generated_by'] = $generated_by;

        return DailyCut::updateOrCreate(
            [
                'business_id' => $business_id,
                'location_id' => $location_id,
                'cut_date' => $data['cut_date'],
            ],
            $data
        );
    }

    /**
     * Generate cuts for all active locations of a business for a given date.
     */
    public function generateForBusiness($business_id, $date, $generated_by = null)
    {
        $locations = BusinessLocation::where('business_id', $business_id)
            ->where('is_active', 1)
            ->pluck('id');

        $results = [];
        foreach ($locations as $location_id) {
            $results[] = $this->upsert($business_id, $location_id, $date, $generated_by);
        }

        return $results;
    }

    /**
     * Generate cuts for all businesses for a given date.
     */
    public function generateForAllBusinesses($date)
    {
        $business_ids = DB::table('business')->pluck('id');

        $count = 0;
        foreach ($business_ids as $bid) {
            $this->generateForBusiness($bid, $date);
            $count++;
        }

        return $count;
    }
}
