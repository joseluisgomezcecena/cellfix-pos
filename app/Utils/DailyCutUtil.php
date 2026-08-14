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
        //   A) Venta normal (no layaway, no repair) → cuenta por transaction_date.
        //   B) Apartado COMPLETADO hoy            → cuenta por layaways.completed_at = hoy.
        //                                           Cuando aparece, TODOS sus pagos acumulados
        //                                           se suman ese día.
        //   C) Reparación ENTREGADA hoy           → cuenta por repair_delivered_at = hoy.
        //                                           Igual que apartados: pagos (anticipo + saldo)
        //                                           se atribuyen al día de la entrega.
        //                                           Fallback: reparaciones históricas sin
        //                                           repair_delivered_at usan transaction_date.
        //   D) Apartado ACTIVO / Reparación PENDIENTE → NO aparecen en ningún cut. Su cash
        //                                           físico se maneja fuera del cajón principal
        //                                           (bolsa/caja separada), como con apartados.
        $sales = DB::table('transactions as t')
            ->leftJoin('layaways as l', 'l.id', '=', 't.layaway_id')
            ->where('t.business_id', $business_id)
            ->where('t.location_id', $location_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where(function ($q) use ($start, $end) {
                // Regla A: venta normal (sin layaway, sin repair_status) → transaction_date
                $q->where(function ($q2) use ($start, $end) {
                    $q2->whereNull('t.layaway_id')
                        ->whereNull('t.repair_status')
                        ->whereBetween('t.transaction_date', [$start, $end]);
                })
                // Regla B: apartado completado → layaways.completed_at
                ->orWhere(function ($q2) use ($start, $end) {
                    $q2->whereNotNull('t.layaway_id')
                        ->whereNotNull('l.completed_at')
                        ->whereBetween('l.completed_at', [$start, $end]);
                })
                // Regla C: reparación entregada → COALESCE(repair_delivered_at, transaction_date)
                // Excluye explícitamente 'pending'. Fallback a transaction_date para históricas.
                ->orWhere(function ($q2) use ($start, $end) {
                    $q2->whereNotNull('t.repair_status')
                        ->where('t.repair_status', '!=', 'pending')
                        ->whereBetween(
                            DB::raw('COALESCE(t.repair_delivered_at, t.transaction_date)'),
                            [$start, $end]
                        );
                });
                // Regla D: apartado activo / reparación pendiente → excluidas (no matchean nada)
            })
            ->select('t.id', 't.final_total', 't.total_before_tax', 't.tax_amount', 't.discount_amount')
            ->get();

        $sale_ids = $sales->pluck('id')->toArray();
        $total_sales = $sales->sum('final_total');
        $total_transactions = $sales->count();

        // ---- Sales by category buckets ----
        // Antes agrupábamos por p.brand_id crudo, lo que hacía que "Corto" apareciera
        // como su propia línea pero se mezclara con OTROS, y que HIDROGEL (que es
        // categoría, no brand) se sumara silenciosamente a VT. Ahora usamos los mismos
        // buckets que el sales-dashboard: EQUIPOS, ACCESORIOS, VT, HIDROGEL, CORTOS,
        // REPARACIONES, SERVICIOS, DESBLOQUEOS, OTROS. Y HIDROGEL siempre aparece
        // (aunque tenga 0) para que el gerente pueda ver la separación explícita.
        // Buckets fijos históricos. Las brands creadas después de $new_brand_cutoff
        // que no matcheen con ninguno de estos aparecen como su propio bucket con
        // el nombre real de la brand (en mayúsculas). Ajusta la fecha si necesitas
        // que brands viejas también salgan solas.
        $new_brand_cutoff = '2026-08-13';
        $bucket_order = ['EQUIPOS', 'ACCESORIOS', 'VT', 'HIDROGEL', 'CORTOS', 'REPARACIONES', 'SERVICIOS', 'DESBLOQUEOS'];
        $always_show = ['HIDROGEL', 'CORTOS'];
        $buckets = [];
        foreach ($bucket_order as $b) {
            $buckets[$b] = ['brand' => $b, 'quantity' => 0.0, 'subtotal' => 0.0];
        }
        // OTROS va al final (después de las brands nuevas dinámicas).

        if (!empty($sale_ids)) {
            // Todos los brand lookups usan arrays + in_array porque en la BD real hay
            // duplicados por typos (ej. "Hidrogel" id=109 y "HIdrogel" id=105 con I mayúscula).
            // Si tomáramos solo el primero perderíamos las ventas de las demás variantes.
            $brandIds = function ($names) use ($business_id) {
                return DB::table('brands')->where('business_id', $business_id)
                    ->whereIn(DB::raw('LOWER(name)'), array_map('strtolower', $names))
                    ->pluck('id')->toArray();
            };
            $brands_equipos      = array_flip($brandIds(['Equipos', 'Equipo']));
            $brands_accesorios   = array_flip($brandIds(['Accesorios']));
            $brands_reparaciones = array_flip($brandIds(['Reparaciones', 'Reparacion']));
            $brands_servicios    = array_flip($brandIds(['Servicios', 'Servicio']));
            $brands_desbloqueos  = array_flip($brandIds(['Desbloqueos', 'Desbloqueo']));
            $brands_cortos       = array_flip($brandIds(['Corto', 'Cortos']));
            $brands_hidrogel     = array_flip($brandIds(['Hidrogel']));
            $brands_vidrio       = array_flip($brandIds(['Vidrio Templado', 'VT']));

            // Brands nuevas (creadas ≥ cutoff): map brand_id => 'NOMBRE UPPER'.
            $new_brand_labels = DB::table('brands')->where('business_id', $business_id)
                ->where('created_at', '>=', $new_brand_cutoff)
                ->pluck('name', 'id')
                ->map(fn ($n) => mb_strtoupper($n))
                ->toArray();

            $treeIds = function ($rootName) use ($business_id) {
                $root = DB::table('categories')->where('business_id', $business_id)
                    ->whereRaw('LOWER(name)=?', [strtolower($rootName)])->value('id');
                if (!$root) return [];
                $ids = [$root]; $queue = [$root];
                while ($queue) {
                    $p = array_shift($queue);
                    foreach (DB::table('categories')->where('parent_id', $p)->pluck('id') as $k) {
                        $ids[] = $k; $queue[] = $k;
                    }
                }
                return array_unique($ids);
            };
            $vt_cats = $treeIds('VT');
            $hidrogel_cats = $treeIds('Hidrogel');
            $vt_only = array_flip(array_diff($vt_cats, $hidrogel_cats));
            $hg = array_flip($hidrogel_cats);

            $lines = DB::table('transaction_sell_lines as tsl')
                ->join('products as p', 'p.id', '=', 'tsl.product_id')
                ->whereIn('tsl.transaction_id', $sale_ids)
                ->select(
                    'p.brand_id', 'p.category_id',
                    DB::raw('COALESCE(SUM(tsl.quantity), 0) as quantity'),
                    // unit_price_inc_tax ya incluye el descuento de línea (UltimatePOS lo guarda
                    // post-descuento). Restar line_discount_amount otra vez doble-contaba el
                    // descuento y bajaba los subtotales por marca cuando había descuento.
                    DB::raw('COALESCE(SUM(tsl.unit_price_inc_tax * tsl.quantity), 0) as subtotal')
                )
                ->groupBy('p.brand_id', 'p.category_id')
                ->get();

            foreach ($lines as $r) {
                $bucket = null;
                if (isset($brands_equipos[$r->brand_id]))            $bucket = 'EQUIPOS';
                elseif (isset($brands_reparaciones[$r->brand_id]))   $bucket = 'REPARACIONES';
                elseif (isset($brands_servicios[$r->brand_id]))      $bucket = 'SERVICIOS';
                elseif (isset($brands_desbloqueos[$r->brand_id]))    $bucket = 'DESBLOQUEOS';
                elseif (isset($brands_cortos[$r->brand_id]))         $bucket = 'CORTOS';
                // HIDROGEL prioriza brand explícita antes que categoría (los productos
                // hidrogel del catálogo actual tienen cat=VT root, no la subcat "Hidrogel").
                elseif (isset($brands_hidrogel[$r->brand_id]))       $bucket = 'HIDROGEL';
                elseif (isset($hg[$r->category_id]))                 $bucket = 'HIDROGEL';
                elseif (isset($brands_vidrio[$r->brand_id]))         $bucket = 'VT';
                elseif (isset($vt_only[$r->category_id]))            $bucket = 'VT';
                elseif (isset($brands_accesorios[$r->brand_id]))     $bucket = 'ACCESORIOS';
                // Brand nueva (creada ≥ cutoff) que no encaja en ninguno de los buckets
                // conceptuales: aparece como su propia línea con el nombre real de la brand.
                elseif (isset($new_brand_labels[$r->brand_id])) {
                    $bucket = $new_brand_labels[$r->brand_id];
                    if (!isset($buckets[$bucket])) {
                        $buckets[$bucket] = ['brand' => $bucket, 'quantity' => 0.0, 'subtotal' => 0.0];
                    }
                }
                else $bucket = 'OTROS';
                if (!isset($buckets[$bucket])) {
                    $buckets[$bucket] = ['brand' => $bucket, 'quantity' => 0.0, 'subtotal' => 0.0];
                }
                $buckets[$bucket]['quantity'] += (float) $r->quantity;
                $buckets[$bucket]['subtotal'] += (float) $r->subtotal;
            }
        }

        // Aseguramos que OTROS exista (para las brands viejas sin clasificar)
        if (!isset($buckets['OTROS'])) {
            $buckets['OTROS'] = ['brand' => 'OTROS', 'quantity' => 0.0, 'subtotal' => 0.0];
        }

        // Emitir buckets con movimiento + los "always_show" (HIDROGEL, CORTOS).
        // Orden: los conceptuales primero, luego las brands nuevas (alfabético), OTROS al final.
        $known = ['EQUIPOS', 'ACCESORIOS', 'VT', 'HIDROGEL', 'CORTOS', 'REPARACIONES', 'SERVICIOS', 'DESBLOQUEOS'];
        $sales_by_brand = [];
        foreach ($known as $name) {
            if (isset($buckets[$name]) && ($buckets[$name]['quantity'] > 0 || $buckets[$name]['subtotal'] > 0 || in_array($name, $always_show))) {
                $sales_by_brand[] = $buckets[$name];
            }
        }
        // Brands nuevas (dinámicas), ordenadas alfabéticamente
        $new_names = array_diff(array_keys($buckets), array_merge($known, ['OTROS']));
        sort($new_names);
        foreach ($new_names as $name) {
            if ($buckets[$name]['quantity'] > 0 || $buckets[$name]['subtotal'] > 0) {
                $sales_by_brand[] = $buckets[$name];
            }
        }
        // OTROS al final si tiene ventas
        if ($buckets['OTROS']['quantity'] > 0 || $buckets['OTROS']['subtotal'] > 0) {
            $sales_by_brand[] = $buckets['OTROS'];
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
