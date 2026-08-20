<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Órdenes de reparación del cliente autenticado (solo las que le pertenecen).
 * En Celfix una reparación es una transaction type='sell' con repair_status
 * en ('pending', 'delivered').
 */
class RepairOrdersController extends Controller
{
    private const BUSINESS_ID = 2;

    /**
     * GET /api/v1/repair-orders?status=pending|delivered|all   (default: all)
     * Devuelve reparaciones del cliente en la sucursal correspondiente.
     */
    public function index(Request $request): JsonResponse
    {
        $contact = $request->attributes->get('api_customer');
        $status = strtolower((string) $request->query('status', 'all'));

        $q = DB::table('transactions as t')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 't.location_id')
            ->where('t.business_id', self::BUSINESS_ID)
            ->where('t.type', 'sell')
            ->where('t.contact_id', $contact->id)
            ->whereNotNull('t.repair_status');

        if ($status === 'pending') {
            $q->where('t.repair_status', 'pending');
        } elseif ($status === 'delivered') {
            $q->where('t.repair_status', 'delivered');
        }

        $rows = $q->select(
                't.id', 't.invoice_no', 't.transaction_date', 't.final_total',
                't.repair_status', 't.repair_delivered_at', 't.additional_notes',
                'bl.name as location_name',
                DB::raw('(SELECT COALESCE(SUM(tp.amount),0) FROM transaction_payments tp WHERE tp.transaction_id = t.id AND tp.is_return = 0) as paid')
            )
            ->orderByDesc('t.transaction_date')
            ->get();

        $data = $rows->map(function ($r) {
            // Nombres de productos concatenados (equipo/reparación)
            $products = DB::table('transaction_sell_lines as tsl')
                ->leftJoin('products as p', 'p.id', '=', 'tsl.product_id')
                ->where('tsl.transaction_id', $r->id)
                ->pluck('p.name')
                ->filter()
                ->implode(', ');

            $total = (float) $r->final_total;
            $paid  = (float) $r->paid;

            return [
                'id'            => (int) $r->id,
                'invoice_no'    => $r->invoice_no,
                'date'          => date('Y-m-d', strtotime($r->transaction_date)),
                'location'      => $r->location_name,
                'status'        => $r->repair_status,
                'status_label'  => $r->repair_status === 'pending' ? 'En reparación' : 'Entregado',
                'delivered_at'  => $r->repair_delivered_at,
                'total'         => $total,
                'paid'          => $paid,
                'balance'       => round($total - $paid, 2),
                'products'      => $products,
                'notes'         => $r->additional_notes,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }
}
