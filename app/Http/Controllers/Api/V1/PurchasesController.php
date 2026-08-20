<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Historial de compras del cliente autenticado.
 * Requiere middleware auth.customer.api — el contact se lee en attributes->api_customer.
 */
class PurchasesController extends Controller
{
    private const BUSINESS_ID = 2;
    private const PER_PAGE = 20;

    /**
     * GET /api/v1/purchases?page=1
     * Lista paginada de todas las compras (transactions type='sell') del cliente.
     * Ordena por fecha descendente. Incluye reparaciones y ventas normales.
     */
    public function index(Request $request): JsonResponse
    {
        $contact = $request->attributes->get('api_customer');
        $page = max(1, (int) $request->query('page', 1));

        $base = DB::table('transactions as t')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 't.location_id')
            ->where('t.business_id', self::BUSINESS_ID)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('t.contact_id', $contact->id);

        $total = (clone $base)->count();
        $rows = (clone $base)
            ->select(
                't.id', 't.invoice_no', 't.transaction_date', 't.final_total',
                't.repair_status', 't.repair_delivered_at',
                'bl.name as location_name',
                DB::raw('(SELECT COUNT(*) FROM transaction_sell_lines tsl WHERE tsl.transaction_id = t.id) as items_count'),
                DB::raw('(SELECT COALESCE(SUM(tp.amount),0) FROM transaction_payments tp WHERE tp.transaction_id = t.id AND tp.is_return = 0) as paid')
            )
            ->orderByDesc('t.transaction_date')
            ->offset(($page - 1) * self::PER_PAGE)
            ->limit(self::PER_PAGE)
            ->get();

        $data = $rows->map(function ($r) {
            $paid = (float) $r->paid;
            $total = (float) $r->final_total;
            return [
                'id'            => (int) $r->id,
                'invoice_no'    => $r->invoice_no,
                'date'          => date('Y-m-d', strtotime($r->transaction_date)),
                'time'          => date('H:i', strtotime($r->transaction_date)),
                'location'      => $r->location_name,
                'total'         => $total,
                'paid'          => $paid,
                'balance'       => round($total - $paid, 2),
                'items_count'   => (int) $r->items_count,
                'is_repair'     => !empty($r->repair_status),
                'repair_status' => $r->repair_status,
            ];
        });

        return response()->json([
            'success'    => true,
            'data'       => $data,
            'pagination' => [
                'current'  => $page,
                'per_page' => self::PER_PAGE,
                'total'    => $total,
                'last'     => (int) ceil($total / self::PER_PAGE),
            ],
        ]);
    }

    /**
     * GET /api/v1/purchases/{id}
     * Detalle de una compra específica. Valida que pertenece al cliente autenticado
     * — si no, devuelve 404 (no revelamos que existe pero es de otro).
     */
    public function show(Request $request, $id): JsonResponse
    {
        $contact = $request->attributes->get('api_customer');
        $id = (int) $id;

        $tx = DB::table('transactions as t')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 't.location_id')
            ->where('t.id', $id)
            ->where('t.business_id', self::BUSINESS_ID)
            ->where('t.type', 'sell')
            ->where('t.contact_id', $contact->id)
            ->select(
                't.id', 't.invoice_no', 't.transaction_date', 't.final_total',
                't.discount_amount', 't.discount_type', 't.tax_amount',
                't.additional_notes', 't.repair_status', 't.repair_delivered_at',
                't.status',
                'bl.name as location_name'
            )
            ->first();

        if (!$tx) {
            return response()->json([
                'success' => false,
                'message' => 'Compra no encontrada.',
            ], 404);
        }

        $items = DB::table('transaction_sell_lines as tsl')
            ->leftJoin('products as p', 'p.id', '=', 'tsl.product_id')
            ->leftJoin('variations as v', 'v.id', '=', 'tsl.variation_id')
            ->where('tsl.transaction_id', $tx->id)
            ->select(
                'p.name as product_name',
                'v.sub_sku',
                'tsl.quantity',
                'tsl.unit_price_inc_tax',
                'tsl.quantity_returned'
            )
            ->get()
            ->map(function ($l) {
                $qty = (float) $l->quantity;
                $price = (float) $l->unit_price_inc_tax;
                return [
                    'product_name'      => $l->product_name,
                    'sku'               => $l->sub_sku,
                    'quantity'          => $qty,
                    'unit_price'        => $price,
                    'subtotal'          => round($qty * $price, 2),
                    'quantity_returned' => (float) $l->quantity_returned,
                ];
            });

        $payments = DB::table('transaction_payments')
            ->where('transaction_id', $tx->id)
            ->select('method', 'amount', 'is_return', 'paid_on')
            ->orderBy('paid_on')
            ->get()
            ->map(function ($p) {
                return [
                    'method'    => $p->method,
                    'amount'    => (float) $p->amount,
                    'is_return' => (bool) $p->is_return,
                    'paid_on'   => $p->paid_on,
                ];
            });

        $paid = $payments->where('is_return', false)->sum('amount');

        return response()->json([
            'success'  => true,
            'purchase' => [
                'id'              => (int) $tx->id,
                'invoice_no'      => $tx->invoice_no,
                'date'            => $tx->transaction_date,
                'location'        => $tx->location_name,
                'total'           => (float) $tx->final_total,
                'discount_amount' => (float) $tx->discount_amount,
                'tax_amount'      => (float) $tx->tax_amount,
                'paid'            => (float) $paid,
                'balance'         => round((float) $tx->final_total - (float) $paid, 2),
                'notes'           => $tx->additional_notes,
                'is_repair'       => !empty($tx->repair_status),
                'repair_status'   => $tx->repair_status,
                'repair_delivered_at' => $tx->repair_delivered_at,
                'items'           => $items,
                'payments'        => $payments,
            ],
        ]);
    }
}
