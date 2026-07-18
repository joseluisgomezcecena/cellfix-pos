<?php

namespace App\Http\Controllers;

use App\Account;
use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\CustomerGroup;
use App\InvoiceScheme;
use App\Media;
use App\Product;
use App\SellingPriceGroup;
use App\TaxRate;
use App\Transaction;
use App\TransactionSellLine;
use App\TypesOfService;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use App\Warranty;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;
use DB;
//use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class SellController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $contactUtil;

    protected $businessUtil;

    protected $transactionUtil;

    protected $productUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(ContactUtil $contactUtil, BusinessUtil $businessUtil, TransactionUtil $transactionUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil)
    {
        $this->contactUtil = $contactUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->productUtil = $productUtil;

        $this->dummyPaymentLine = ['method' => '', 'amount' => 0, 'note' => '', 'card_transaction_number' => '', 'card_number' => '', 'card_type' => '', 'card_holder_name' => '', 'card_month' => '', 'card_year' => '', 'card_security' => '', 'cheque_number' => '', 'bank_account_number' => '',
            'is_return' => 0, 'transaction_no' => '', ];

        $this->shipping_status_colors = [
            'ordered' => 'bg-yellow',
            'packed' => 'bg-info',
            'shipped' => 'bg-navy',
            'delivered' => 'bg-green',
            'cancelled' => 'bg-red',
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    

    
    
    public function index()
{
    $is_admin = $this->businessUtil->is_admin(auth()->user());

    if (! $is_admin && ! auth()->user()->hasAnyPermission(['sell.view', 'sell.create', 'direct_sell.access', 'direct_sell.view', 'view_own_sell_only', 'view_commission_agent_sell', 'access_shipping', 'access_own_shipping', 'access_commission_agent_shipping', 'so.view_all', 'so.view_own'])) {
        abort(403, 'Unauthorized action.');
    }

    $business_id = request()->session()->get('user.business_id');
    $is_woocommerce = $this->moduleUtil->isModuleInstalled('Woocommerce');
    $is_crm = $this->moduleUtil->isModuleInstalled('Crm');
    $is_tables_enabled = $this->transactionUtil->isModuleEnabled('tables');
    $is_service_staff_enabled = $this->transactionUtil->isModuleEnabled('service_staff');
    $is_types_service_enabled = $this->moduleUtil->isModuleEnabled('types_of_service');

    if (request()->ajax()) {
        // OPTIMIZACIÓN 1: Usar Query Builder directo en lugar de Eloquent
        // Esto es 50-70% más rápido
        
        // Variables de paginación de DataTables
        $draw = request()->get('draw');
        $start = request()->get('start', 0);
        $length = request()->get('length', 25);
        
        // Validar fechas
        $start_date = request()->input('start_date');
        $end_date = request()->input('end_date');
        
        if (!empty($start_date) && !empty($end_date)) {
            $start_date = \Carbon\Carbon::parse($start_date)->format('Y-m-d');
            $end_date = \Carbon\Carbon::parse($end_date)->format('Y-m-d');
            
            $date_diff = \Carbon\Carbon::parse($end_date)->diffInDays(\Carbon\Carbon::parse($start_date));
            
            if ($date_diff > 90) {
                return response()->json([
                    'draw' => $draw,
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'El rango máximo permitido es de 90 días'
                ]);
            }
        } else {
            $start_date = \Carbon\Carbon::today()->format('Y-m-d');
            $end_date = \Carbon\Carbon::today()->format('Y-m-d');
        }

        $sale_type = request()->input('sale_type', 'sell');
        
        // OPTIMIZACIÓN 2: Query base con Query Builder (no Eloquent)
        $baseQuery = \DB::table('transactions')
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->leftJoin('transaction_sell_lines as tsl', function ($join) {
                $join->on('transactions.id', '=', 'tsl.transaction_id')
                    ->whereNull('tsl.parent_sell_line_id');
            })
            ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
            ->join('business_locations as bl', 'transactions.location_id', '=', 'bl.id');
        
        // JOINs condicionales según módulos
        if ($is_service_staff_enabled) {
            $baseQuery->leftJoin('users as ss', 'transactions.res_waiter_id', '=', 'ss.id');
        }
        
        if ($is_tables_enabled && \Schema::hasTable('res_tables')) {
            $baseQuery->leftJoin('res_tables as tables', 'transactions.res_table_id', '=', 'tables.id');
        }
        
        if ($is_types_service_enabled && \Schema::hasTable('types_of_services')) {
            $baseQuery->leftJoin('types_of_services as tos', 'transactions.types_of_service_id', '=', 'tos.id');
        }
        
        $baseQuery->where('transactions.business_id', $business_id)
            ->where('transactions.type', $sale_type)
            ->where('transactions.status', 'final');

        // REGLA DE APARTADOS (misma que DailyCutUtil::compute, commit 98ce00f):
        //   A) Venta normal (layaway_id NULL) → filtra por transaction_date.
        //   B) Apartado COMPLETADO en el rango → filtra por layaways.completed_at.
        //      El apartado aparece el día que el cliente se lleva el equipo, NO
        //      el día del anticipo. El dinero del anticipo vive en la caja del
        //      equipo hasta liquidación, así que no era "venta de ese día".
        //   C) Apartado ACTIVO (layaway_id NOT NULL AND completed_at IS NULL) → EXCLUIDO
        //      de todas las fechas. El dinero aún no entra a la caja registradora.
        if ($sale_type == 'sell') {
            $baseQuery->leftJoin('layaways as sc_l', 'sc_l.id', '=', 'transactions.layaway_id')
                ->where(function ($q) use ($start_date, $end_date) {
                    $q->where(function ($q2) use ($start_date, $end_date) {
                        $q2->whereNull('transactions.layaway_id')
                            ->whereRaw('DATE(transactions.transaction_date) >= ?', [$start_date])
                            ->whereRaw('DATE(transactions.transaction_date) <= ?', [$end_date]);
                    })->orWhere(function ($q2) use ($start_date, $end_date) {
                        $q2->whereNotNull('transactions.layaway_id')
                            ->whereNotNull('sc_l.completed_at')
                            ->whereRaw('DATE(sc_l.completed_at) >= ?', [$start_date])
                            ->whereRaw('DATE(sc_l.completed_at) <= ?', [$end_date]);
                    });
                });
        } else {
            $baseQuery->whereRaw('DATE(transactions.transaction_date) >= ?', [$start_date])
                ->whereRaw('DATE(transactions.transaction_date) <= ?', [$end_date]);
        }

        // Filtros para sell (excluir project_invoice)
        if ($sale_type == 'sell') {
            $baseQuery->where(function ($query) {
                $query->where('transactions.sub_type', '!=', 'project_invoice')
                      ->orWhereNull('transactions.sub_type');
            });
        }

        // Aplicar filtros
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $baseQuery->whereIn('transactions.location_id', $permitted_locations);
        }

        if (!empty(request()->input('location_id'))) {
            $baseQuery->where('transactions.location_id', request()->input('location_id'));
        }

        if (!empty(request()->input('customer_id'))) {
            $baseQuery->where('contacts.id', request()->input('customer_id'));
        }

        if (!empty(request()->input('payment_status'))) {
            if (request()->input('payment_status') == 'overdue') {
                $baseQuery->whereIn('transactions.payment_status', ['due', 'partial'])
                    ->whereNotNull('transactions.pay_term_number')
                    ->whereNotNull('transactions.pay_term_type')
                    ->whereRaw("IF(transactions.pay_term_type='days', DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number DAY) < CURDATE(), DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number MONTH) < CURDATE())");
            } else {
                $baseQuery->where('transactions.payment_status', request()->input('payment_status'));
            }
        }

        // Filtro por método de pago: la venta debe tener AL MENOS un pago con el método
        // seleccionado (una venta puede combinar varios métodos). El frontend lo envía en
        // d.payment_method pero el controller no lo estaba leyendo, así el dropdown no
        // hacía nada.
        if (!empty(request()->input('payment_method'))) {
            $selected_method = request()->input('payment_method');
            $baseQuery->whereExists(function ($q) use ($selected_method) {
                $q->select(\DB::raw(1))
                  ->from('transaction_payments')
                  ->whereColumn('transaction_payments.transaction_id', 'transactions.id')
                  ->where('transaction_payments.is_return', 0)
                  ->where('transaction_payments.method', $selected_method);
            });
        }

        if (!empty(request()->input('created_by'))) {
            $baseQuery->where('transactions.created_by', request()->input('created_by'));
        }

        if (!empty(request()->input('sales_cmsn_agnt'))) {
            $baseQuery->where('transactions.commission_agent', request()->input('sales_cmsn_agnt'));
        }

        if (!empty(request()->input('service_staffs'))) {
            $baseQuery->where('transactions.res_waiter_id', request()->input('service_staffs'));
        }

        if (!empty(request()->input('shipping_status'))) {
            $baseQuery->where('transactions.shipping_status', request()->input('shipping_status'));
        }

        // Búsqueda global de DataTables
        $searchValue = request()->input('search.value');
        if (!empty($searchValue)) {
            $baseQuery->where(function ($q) use ($searchValue) {
                $q->where('transactions.invoice_no', 'like', "%{$searchValue}%")
                  ->orWhere('contacts.name', 'like', "%{$searchValue}%")
                  ->orWhere('contacts.mobile', 'like', "%{$searchValue}%")
                  ->orWhere('transactions.final_total', 'like', "%{$searchValue}%");
            });
        }

        // OPTIMIZACIÓN 3: COUNT simplificado (sin todos los JOINs).
        // IMPORTANTE: como el JOIN con layaways introduce otra columna business_id/location_id,
        // hay que calificar TODAS las columnas con el nombre de tabla o MySQL tira error
        // "Column ... in where clause is ambiguous".
        $countQuery = \DB::table('transactions')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', $sale_type)
            ->where('transactions.status', 'final');

        if ($sale_type == 'sell') {
            // Misma regla de apartados que baseQuery (arriba).
            $countQuery->leftJoin('layaways as sc_l2', 'sc_l2.id', '=', 'transactions.layaway_id')
                ->where(function ($q) use ($start_date, $end_date) {
                    $q->where(function ($q2) use ($start_date, $end_date) {
                        $q2->whereNull('transactions.layaway_id')
                            ->whereRaw('DATE(transactions.transaction_date) >= ?', [$start_date])
                            ->whereRaw('DATE(transactions.transaction_date) <= ?', [$end_date]);
                    })->orWhere(function ($q2) use ($start_date, $end_date) {
                        $q2->whereNotNull('transactions.layaway_id')
                            ->whereNotNull('sc_l2.completed_at')
                            ->whereRaw('DATE(sc_l2.completed_at) >= ?', [$start_date])
                            ->whereRaw('DATE(sc_l2.completed_at) <= ?', [$end_date]);
                    });
                })->where(function ($query) {
                    $query->where('transactions.sub_type', '!=', 'project_invoice')
                          ->orWhereNull('transactions.sub_type');
                });
        } else {
            $countQuery->whereRaw('DATE(transactions.transaction_date) >= ?', [$start_date])
                ->whereRaw('DATE(transactions.transaction_date) <= ?', [$end_date]);
        }

        if ($permitted_locations != 'all') {
            $countQuery->whereIn('transactions.location_id', $permitted_locations);
        }

        $recordsFiltered = $countQuery->count();

        // OPTIMIZACIÓN 4: Query para datos con campos específicos
        $dataQuery = clone $baseQuery;
        
        // Construir campos SELECT base (siempre existen)
        $selectFields = [
            'transactions.id',
            'transactions.transaction_date',
            'transactions.invoice_no',
            'transactions.payment_status',
            'transactions.final_total',
            'transactions.shipping_status',
            'transactions.is_direct_sale',
            'transactions.type',
            'transactions.sub_type',
            'transactions.status',
            'transactions.document',
            'transactions.custom_field_1',
            'transactions.custom_field_2',
            'transactions.custom_field_3',
            'transactions.custom_field_4',
            'transactions.additional_notes',
            'transactions.staff_note',
            'transactions.shipping_details',
            'transactions.is_export',
            'transactions.pay_term_number',
            'transactions.pay_term_type',
            'contacts.name',
            'contacts.mobile',
            'contacts.supplier_business_name',
            'bl.name as business_location',
            'u.first_name as added_by',
            \DB::raw('COUNT(DISTINCT tsl.id) as total_items'),
            // Subconsultas para evitar JOINs adicionales
            \DB::raw('(SELECT SUM(IF(tp.is_return = 1, -1 * tp.amount, tp.amount)) FROM transaction_payments tp WHERE tp.transaction_id = transactions.id) as total_paid'),
            \DB::raw('(SELECT GROUP_CONCAT(DISTINCT method SEPARATOR ",") FROM transaction_payments WHERE transaction_payments.transaction_id = transactions.id) as payment_methods_list'),
            \DB::raw('EXISTS(SELECT 1 FROM transactions returns WHERE returns.return_parent_id = transactions.id AND returns.type = "sell_return") as return_exists'),
            \DB::raw('(SELECT SUM(returns.final_total) FROM transactions returns WHERE returns.return_parent_id = transactions.id AND returns.type = "sell_return") as amount_return'),
            \DB::raw('(SELECT SUM(tp2.amount) FROM transaction_payments tp2 INNER JOIN transactions returns2 ON tp2.transaction_id = returns2.id WHERE returns2.return_parent_id = transactions.id AND returns2.type = "sell_return") as return_paid'),
            \DB::raw('(SELECT returns3.id FROM transactions returns3 WHERE returns3.return_parent_id = transactions.id AND returns3.type = "sell_return" ORDER BY returns3.id DESC LIMIT 1) as return_transaction_id'),
            // CAPA 4: edit_count y last_edited_at NO se calculan aquí como subqueries
            // porque activity_log tiene 100K+ filas y sin índice adecuado ralentiza
            // el datatables a decenas de segundos. Se rellenan después del $data->get()
            // con UNA sola query agregada sobre las 25 filas de la página actual.
        ];
        
        // Campos condicionales según módulos
        if ($is_service_staff_enabled) {
            $selectFields[] = 'ss.first_name as waiter';
        }
        
        if ($is_tables_enabled && \Schema::hasTable('res_tables')) {
            $selectFields[] = 'tables.name as table_name';
        }
        
        if ($is_types_service_enabled && \Schema::hasTable('types_of_services')) {
            $selectFields[] = 'tos.name as types_of_service_name';
            
            // Verificar si existe la columna service_custom_field_1
            if (\Schema::hasColumn('transactions', 'service_custom_field_1') && 
                \Schema::hasColumn('types_of_services', 'service_custom_field_1')) {
                $selectFields[] = \DB::raw('IF(transactions.service_custom_field_1 IS NOT NULL, transactions.service_custom_field_1, tos.service_custom_field_1) as service_custom_field_1');
            } else if (\Schema::hasColumn('transactions', 'service_custom_field_1')) {
                $selectFields[] = 'transactions.service_custom_field_1';
            }
        }

        // Agregar campos condicionales según módulos
        if ($is_woocommerce && \Schema::hasColumn('transactions', 'woocommerce_order_id')) {
            $selectFields[] = 'transactions.woocommerce_order_id';
        }

        if ($is_crm && \Schema::hasColumn('transactions', 'crm_is_order_request')) {
            $selectFields[] = 'transactions.crm_is_order_request';
        }

        if ($this->businessUtil->isModuleEnabled('subscription')) {
            if (\Schema::hasColumn('transactions', 'is_recurring')) {
                $selectFields[] = 'transactions.is_recurring';
            }
            if (\Schema::hasColumn('transactions', 'recur_parent_id')) {
                $selectFields[] = 'transactions.recur_parent_id';
            }
        }
        
        // Verificar columnas de service staff
        if ($is_service_staff_enabled && \Schema::hasColumn('transactions', 'res_waiter_id')) {
            $selectFields[] = 'transactions.res_waiter_id';
        }
        
        // Verificar columnas de tables
        if ($is_tables_enabled && \Schema::hasColumn('transactions', 'res_table_id')) {
            $selectFields[] = 'transactions.res_table_id';
        }
        
        // Verificar columnas de types of service
        if ($is_types_service_enabled && \Schema::hasColumn('transactions', 'types_of_service_id')) {
            $selectFields[] = 'transactions.types_of_service_id';
        }

        $dataQuery->select($selectFields)
            ->groupBy('transactions.id');

        // Ordenamiento
        $orderColumn = request()->input('order.0.column', 1);
        $orderDir = request()->input('order.0.dir', 'desc');
        $columns = request()->input('columns');
        
        if (isset($columns[$orderColumn]['name'])) {
            $orderBy = $columns[$orderColumn]['name'];
            // Asegurar que use la tabla correcta
            if (!str_contains($orderBy, '.')) {
                $orderBy = 'transactions.' . $orderBy;
            }
            $dataQuery->orderBy($orderBy, $orderDir);
        } else {
            $dataQuery->orderBy('transactions.transaction_date', 'desc');
        }

        // OPTIMIZACIÓN 5: PAGINACIÓN REAL con LIMIT y OFFSET.
        // DataTables manda length=-1 cuando el usuario elige "Show all". En ese caso
        // Laravel ignora ->limit(-1) pero SÍ agrega ->offset(0), lo que produce
        // "OFFSET 0" sin LIMIT — MySQL rechaza esa sintaxis. Solo aplicamos ambos
        // cuando $length es positivo.
        $length = (int) $length;
        if ($length > 0) {
            $dataQuery->limit($length)->offset((int) $start);
        }
        $data = $dataQuery->get();

        // CAPA 4: cargar contadores de edición para las 25 filas visibles en UNA
        // sola query agregada, en vez de 2 subqueries correlacionadas por fila.
        // Sin este cambio y sin índice en activity_log(subject_type, subject_id)
        // el datatables tardaba ~68 segundos por página.
        $tx_ids_for_edits = collect($data)->pluck('id')->filter()->all();
        $edit_map = [];
        if (!empty($tx_ids_for_edits)) {
            $edit_rows = \DB::table('activity_log')
                ->where('subject_type', 'App\\Transaction')
                ->where('description', 'edited')
                ->whereIn('subject_id', $tx_ids_for_edits)
                ->select('subject_id',
                    \DB::raw('COUNT(*) as edit_count'),
                    \DB::raw('MAX(created_at) as last_edited_at'))
                ->groupBy('subject_id')
                ->get();
            foreach ($edit_rows as $er) {
                $edit_map[$er->subject_id] = [
                    'count' => (int) $er->edit_count,
                    'last'  => $er->last_edited_at,
                ];
            }
        }
        foreach ($data as $row) {
            $info = $edit_map[$row->id] ?? ['count' => 0, 'last' => null];
            $row->edit_count = $info['count'];
            $row->last_edited_at = $info['last'];
        }

        // Formatear datos para DataTables
        $payment_types = $this->transactionUtil->payment_types(null, true, $business_id);
        $shipping_statuses = $this->transactionUtil->shipping_statuses();
        $formattedData = [];

        foreach ($data as $row) {
            // Calcular total_remaining
            $total_remaining = $row->final_total - ($row->total_paid ?? 0);
            
            // Formatear payment methods
            $payment_method_html = '';
            if (!empty($row->payment_methods_list)) {
                $methods = explode(',', $row->payment_methods_list);
                $payment_method = '';
                if (count($methods) == 1) {
                    $payment_method = $payment_types[$methods[0]] ?? '';
                } elseif (count($methods) > 1) {
                    $payment_method = __('lang_v1.checkout_multi_pay');
                }
                if ($payment_method) {
                    $payment_method_html = '<span class="payment-method">' . $payment_method . '</span>';
                }
            }

            // Formatear invoice_no con verificaciones
            $invoice_no = $row->invoice_no;
            if (property_exists($row, 'woocommerce_order_id') && !empty($row->woocommerce_order_id)) {
                $invoice_no .= ' <i class="fab fa-wordpress text-primary no-print"></i>';
            }
            if (property_exists($row, 'return_exists') && $row->return_exists) {
                $invoice_no .= ' <small class="label bg-red label-round no-print"><i class="fas fa-undo"></i></small>';
            }
            if (property_exists($row, 'is_recurring') && $row->is_recurring) {
                $invoice_no .= ' <small class="label bg-red label-round no-print"><i class="fas fa-recycle"></i></small>';
            }
            if (property_exists($row, 'recur_parent_id') && $row->recur_parent_id) {
                $invoice_no .= ' <small class="label bg-info label-round no-print"><i class="fas fa-recycle"></i></small>';
            }
            if (property_exists($row, 'is_export') && $row->is_export) {
                $invoice_no .= '<br><small class="label label-default no-print">' . __('lang_v1.export') . '</small>';
            }
            if (property_exists($row, 'crm_is_order_request') && $row->crm_is_order_request) {
                $invoice_no .= ' <small class="label bg-yellow label-round no-print"><i class="fas fa-tasks"></i></small>';
            }
            // CAPA 4: badge visual si la venta fue editada después de finalizada.
            // Umbral: 1+ edición = badge amarillo; 3+ = badge rojo (más sospechoso).
            $edit_count = property_exists($row, 'edit_count') ? (int) $row->edit_count : 0;
            if ($edit_count > 0) {
                $last_ed = property_exists($row, 'last_edited_at') && $row->last_edited_at
                    ? \Carbon\Carbon::parse($row->last_edited_at)->format('d/m/Y H:i')
                    : '';
                $badge_color = $edit_count >= 3 ? 'bg-red' : 'bg-yellow';
                $tooltip = sprintf('Editada %d %s. Última edición: %s',
                    $edit_count, ($edit_count === 1 ? 'vez' : 'veces'), $last_ed);
                $invoice_no .= ' <small class="label ' . $badge_color . ' label-round no-print" title="' . e($tooltip) . '"><i class="fas fa-pencil-alt"></i> ' . $edit_count . '</small>';
            }

            // Formatear devoluciones: mostrar en la misma celda cuánto se reembolsó al
            // cliente ("Devuelto") y cuánto queda pendiente ("Pendiente"). Antes solo se
            // veía el pendiente, entonces con devoluciones parciales el gerente no sabía
            // si el cliente ya recibió algo de vuelta o no.
            $return_due_html = '';
            if (property_exists($row, 'return_exists') && $row->return_exists) {
                $refunded = (float) (property_exists($row, 'return_paid') ? $row->return_paid : 0);
                $return_due = (float) (property_exists($row, 'amount_return') ? $row->amount_return : 0) - $refunded;
                $return_due_html = '<div style="text-align:left; font-size:11px; line-height:1.4;">'
                    . '<span style="color:#2e7d32;">Devuelto: <strong>' . $this->transactionUtil->num_f($refunded, true) . '</strong></span><br>'
                    . '<span class="sell_return_due" data-orig-value="' . $return_due . '" style="color:' . ($return_due > 0.01 ? '#c62828' : '#757575') . ';">Pendiente: <strong>' . $this->transactionUtil->num_f($return_due, true) . '</strong></span>'
                    . '</div>';
            }

            // Formatear shipping_status
            $shipping_status_html = '';
            if (!empty($row->shipping_status)) {
                $status_color = $this->shipping_status_colors[$row->shipping_status] ?? 'bg-gray';
                $shipping_status_html = '<span class="label ' . $status_color . '">' . 
                    ($shipping_statuses[$row->shipping_status] ?? '') . '</span>';
            }

            // Formatear payment_status
            $payment_status = Transaction::getPaymentStatus($row);
            $payment_status_html = (string) view('sell.partials.payment_status', ['payment_status' => $payment_status, 'id' => $row->id]);

            // Action buttons simplificados
            $action_html = '<div class="btn-group">
                <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-info dropdown-toggle"
                    data-toggle="dropdown">' . __('messages.actions') . '</button>
                <ul class="dropdown-menu dropdown-menu-left" role="menu">
                    <li><a href="#" data-href="' . action([\App\Http\Controllers\SellController::class, 'show'], [$row->id]) . '"
                        class="btn-modal" data-container=".view_modal"><i class="fas fa-eye"></i> ' . __('messages.view') . '</a></li>';

            if ($row->is_direct_sale == 0) {
                $action_html .= '<li><a target="_blank" href="' . action([\App\Http\Controllers\SellPosController::class, 'edit'], [$row->id]) . '">
                    <i class="fas fa-edit"></i> ' . __('messages.edit') . '</a></li>';
            } else {
                $action_html .= '<li><a target="_blank" href="' . action([\App\Http\Controllers\SellController::class, 'edit'], [$row->id]) . '">
                    <i class="fas fa-edit"></i> ' . __('messages.edit') . '</a></li>';
            }

            $action_html .= '<li><a href="' . route('sell.printInvoice', [$row->id]) . '" class="print-invoice"><i class="fas fa-print"></i> ' . __('messages.print') . '</a></li>';

            if (auth()->user()->can('access_sell_return') && $row->status == 'final') {
                $action_html .= '<li><a href="' . action([\App\Http\Controllers\SellReturnController::class, 'add'], [$row->id]) . '"><i class="fas fa-undo"></i> ' . __('lang_v1.sell_return') . '</a></li>';
            }

            if (auth()->user()->can('direct_sell.delete')) {
                $action_html .= '<li><a href="' . action([\App\Http\Controllers\SellPosController::class, 'destroy'], [$row->id]) . '" class="delete-sale"><i class="fas fa-trash"></i> ' . __('messages.delete') . '</a></li>';
            }

            $action_html .= '</ul></div>';

            $formattedData[] = [
                'DT_RowId' => 'row_' . $row->id,
                'DT_RowAttr' => [
                    'data-href' => action([\App\Http\Controllers\SellController::class, 'show'], [$row->id])
                ],
                'action' => $action_html,
                'transaction_date' => \Carbon\Carbon::parse($row->transaction_date)->format('d/m/Y H:i'),
                'invoice_no' => $invoice_no,
                'conatct_name' => (!empty($row->supplier_business_name) ? $row->supplier_business_name . ', <br>' : '') . ($row->name ?? ''),
                'mobile' => $row->mobile ?? '',
                'business_location' => $row->business_location ?? '',
                'payment_status' => $payment_status_html,
                'payment_methods' => $payment_method_html,
                'final_total' => '<span class="final-total" data-orig-value="' . $row->final_total . '">' . 
                    $this->transactionUtil->num_f($row->final_total, true) . '</span>',
                'total_paid' => '<span class="total-paid" data-orig-value="' . ($row->total_paid ?? 0) . '">' . 
                    $this->transactionUtil->num_f($row->total_paid ?? 0, true) . '</span>',
                'total_remaining' => '<span class="payment_due" data-orig-value="' . $total_remaining . '">' . 
                    $this->transactionUtil->num_f($total_remaining, true) . '</span>',
                'return_due' => $return_due_html,
                'shipping_status' => $shipping_status_html,
                'total_items' => $this->transactionUtil->num_f($row->total_items ?? 0),
                'types_of_service_name' => '<span class="service-type-label">' . (property_exists($row, 'types_of_service_name') ? $row->types_of_service_name : '') . '</span>',
                'service_custom_field_1' => property_exists($row, 'service_custom_field_1') ? $row->service_custom_field_1 : '',
                'custom_field_1' => $row->custom_field_1 ?? '',
                'custom_field_2' => $row->custom_field_2 ?? '',
                'custom_field_3' => $row->custom_field_3 ?? '',
                'custom_field_4' => $row->custom_field_4 ?? '',
                'added_by' => $row->added_by ?? '',
                'additional_notes' => $row->additional_notes ?? '',
                'staff_note' => $row->staff_note ?? '',
                'shipping_details' => $row->shipping_details ?? '',
                'table_name' => property_exists($row, 'table_name') ? $row->table_name : '',
                'waiter' => property_exists($row, 'waiter') ? $row->waiter : ''
            ];
        }

        // OPTIMIZACIÓN 6: Respuesta directa sin usar DataTables collection
        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $recordsFiltered,
            'recordsFiltered' => $recordsFiltered,
            'data' => $formattedData
        ]);
    }

    // Vista normal (no AJAX) - mantén tu código existente
    $business_locations = BusinessLocation::forDropdown($business_id, false);
    $customers = Contact::customersDropdown($business_id, false);
    $sales_representative = User::forDropdown($business_id, false, false, true);

    $is_cmsn_agent_enabled = request()->session()->get('business.sales_cmsn_agnt');
    $commission_agents = [];
    if (! empty($is_cmsn_agent_enabled)) {
        $commission_agents = User::forDropdown($business_id, false, true, true);
    }

    $service_staffs = null;
    if ($this->productUtil->isModuleEnabled('service_staff')) {
        $service_staffs = $this->productUtil->serviceStaffDropdown($business_id);
    }

    $shipping_statuses = $this->transactionUtil->shipping_statuses();

    $sources = $this->transactionUtil->getSources($business_id);
    if ($is_woocommerce) {
        $sources['woocommerce'] = 'Woocommerce';
    }

    $payment_types = $this->transactionUtil->payment_types(null, true, $business_id);

    return view('sell.index')
    ->with(compact('business_locations', 'customers', 'is_woocommerce', 'sales_representative', 'is_cmsn_agent_enabled', 'commission_agents', 'service_staffs', 'is_tables_enabled', 'is_service_staff_enabled', 'is_types_service_enabled', 'shipping_statuses', 'sources', 'payment_types'));
}
    
    
    
    
   
   
    /**
 * Display a listing of the resource.
 * VERSIÓN REALMENTE OPTIMIZADA - Query Builder + Paginación Real
 *
 * @return \Illuminate\Http\Response
 */


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $sale_type = request()->get('sale_type', '');

        if ($sale_type == 'sales_order') {
            if (! auth()->user()->can('so.create')) {
                abort(403, 'Unauthorized action.');
            }
        } else {
            if (! auth()->user()->can('direct_sell.access')) {
                abort(403, 'Unauthorized action.');
            }
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not, then check for users quota
        if (! $this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        } elseif (! $this->moduleUtil->isQuotaAvailable('invoices', $business_id)) {
            return $this->moduleUtil->quotaExpiredResponse('invoices', $business_id, action([\App\Http\Controllers\SellController::class, 'index']));
        }

        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);

        $business_details = $this->businessUtil->getDetails($business_id);
        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);

        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

        $default_location = null;
        foreach ($business_locations as $id => $name) {
            $default_location = BusinessLocation::findOrFail($id);
            break;
        }

        $commsn_agnt_setting = $business_details->sales_cmsn_agnt;
        $commission_agent = [];
        if ($commsn_agnt_setting == 'user') {
            $commission_agent = User::forDropdown($business_id);
        } elseif ($commsn_agnt_setting == 'cmsn_agnt') {
            $commission_agent = User::saleCommissionAgentsDropdown($business_id);
        }

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }
        if (auth()->user()->can('supplier.create') && auth()->user()->can('customer.create')) {
            $types['both'] = __('lang_v1.both_supplier_customer');
        }
        $customer_groups = CustomerGroup::forDropdown($business_id);

        $payment_line = $this->dummyPaymentLine;
        $payment_types = $this->transactionUtil->payment_types(null, true, $business_id);

        //Selling Price Group Dropdown
        $price_groups = SellingPriceGroup::forDropdown($business_id);

        $default_price_group_id = ! empty($default_location->selling_price_group_id) && array_key_exists($default_location->selling_price_group_id, $price_groups) ? $default_location->selling_price_group_id : null;

        $default_datetime = $this->businessUtil->format_date('now', true);

        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

        $invoice_schemes = InvoiceScheme::forDropdown($business_id);
        $default_invoice_schemes = InvoiceScheme::getDefault($business_id);
        if (! empty($default_location) && !empty($default_location->sale_invoice_scheme_id)) {
            $default_invoice_schemes = InvoiceScheme::where('business_id', $business_id)
                                        ->findorfail($default_location->sale_invoice_scheme_id);
        }
        $shipping_statuses = $this->transactionUtil->shipping_statuses();

        //Types of service
        $types_of_service = [];
        if ($this->moduleUtil->isModuleEnabled('types_of_service')) {
            $types_of_service = TypesOfService::forDropdown($business_id);
        }

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }

        $status = request()->get('status', '');

        $statuses = Transaction::sell_statuses();

        if ($sale_type == 'sales_order') {
            $status = 'ordered';
        }

        $is_order_request_enabled = false;
        $is_crm = $this->moduleUtil->isModuleInstalled('Crm');
        if ($is_crm) {
            $crm_settings = Business::where('id', auth()->user()->business_id)
                                ->value('crm_settings');
            $crm_settings = ! empty($crm_settings) ? json_decode($crm_settings, true) : [];

            if (! empty($crm_settings['enable_order_request'])) {
                $is_order_request_enabled = true;
            }
        }

        //Added check because $users is of no use if enable_contact_assign if false
        $users = config('constants.enable_contact_assign') ? User::forDropdown($business_id, false, false, false, true) : [];

        $change_return = $this->dummyPaymentLine;

        return view('sell.create')
            ->with(compact(
                'business_details',
                'taxes',
                'walk_in_customer',
                'business_locations',
                'bl_attributes',
                'default_location',
                'commission_agent',
                'types',
                'customer_groups',
                'payment_line',
                'payment_types',
                'price_groups',
                'default_datetime',
                'pos_settings',
                'invoice_schemes',
                'default_invoice_schemes',
                'types_of_service',
                'accounts',
                'shipping_statuses',
                'status',
                'sale_type',
                'statuses',
                'is_order_request_enabled',
                'users',
                'default_price_group_id',
                'change_return'
            ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // if (!auth()->user()->can('sell.view') && !auth()->user()->can('direct_sell.access') && !auth()->user()->can('view_own_sell_only')) {
        //     abort(403, 'Unauthorized action.');
        // }

        $business_id = request()->session()->get('user.business_id');
        $taxes = TaxRate::where('business_id', $business_id)
                            ->pluck('name', 'id');
        $query = Transaction::where('business_id', $business_id)
                    ->where('id', $id)
                    ->with(['contact', 'delivery_person_user', 'sell_lines' => function ($q) {
                        $q->whereNull('parent_sell_line_id');
                    }, 'sell_lines.product', 'sell_lines.product.unit', 'sell_lines.product.second_unit', 'sell_lines.variations', 'sell_lines.variations.product_variation', 'payment_lines', 'sell_lines.modifiers', 'sell_lines.lot_details', 'tax', 'sell_lines.sub_unit', 'table', 'service_staff', 'sell_lines.service_staff', 'types_of_service', 'sell_lines.warranties', 'media']);

        if (! auth()->user()->can('sell.view') && ! auth()->user()->can('direct_sell.access') && auth()->user()->can('view_own_sell_only')) {
            $query->where('transactions.created_by', request()->session()->get('user.id'));
        }

        $sell = $query->firstOrFail();

        $activities = Activity::forSubject($sell)
           ->with(['causer', 'subject'])
           ->latest()
           ->get();

        $line_taxes = [];
        foreach ($sell->sell_lines as $key => $value) {
            if (! empty($value->sub_unit_id)) {
                $formated_sell_line = $this->transactionUtil->recalculateSellLineTotals($business_id, $value);
                $sell->sell_lines[$key] = $formated_sell_line;
            }

            if (! empty($taxes[$value->tax_id])) {
                if (isset($line_taxes[$taxes[$value->tax_id]])) {
                    $line_taxes[$taxes[$value->tax_id]] += ($value->item_tax * $value->quantity);
                } else {
                    $line_taxes[$taxes[$value->tax_id]] = ($value->item_tax * $value->quantity);
                }
            }
        }

        $payment_types = $this->transactionUtil->payment_types($sell->location_id, true);
        $order_taxes = [];
        if (! empty($sell->tax)) {
            if ($sell->tax->is_tax_group) {
                $order_taxes = $this->transactionUtil->sumGroupTaxDetails($this->transactionUtil->groupTaxDetails($sell->tax, $sell->tax_amount));
            } else {
                $order_taxes[$sell->tax->name] = $sell->tax_amount;
            }
        }

        $business_details = $this->businessUtil->getDetails($business_id);
        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);
        $shipping_statuses = $this->transactionUtil->shipping_statuses();
        $shipping_status_colors = $this->shipping_status_colors;
        $common_settings = session()->get('business.common_settings');
        $is_warranty_enabled = ! empty($common_settings['enable_product_warranty']) ? true : false;

        $statuses = Transaction::sell_statuses();

        if ($sell->type == 'sales_order') {
            $sales_order_statuses = Transaction::sales_order_statuses(true);
            $statuses = array_merge($statuses, $sales_order_statuses);
        }
        $status_color_in_activity = Transaction::sales_order_statuses();
        $sales_orders = $sell->salesOrders();

        return view('sale_pos.show')
            ->with(compact(
                'taxes',
                'sell',
                'payment_types',
                'order_taxes',
                'pos_settings',
                'shipping_statuses',
                'shipping_status_colors',
                'is_warranty_enabled',
                'activities',
                'statuses',
                'status_color_in_activity',
                'sales_orders',
                'line_taxes'
            ));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (! auth()->user()->can('direct_sell.update') && ! auth()->user()->can('so.update')) {
            abort(403, 'Unauthorized action.');
        }

        // CAPA 1: bloqueo de edición de ventas finalizadas por no-admin.
        // Mismo control que en SellPosController::edit — ver reporte
        // docs/reporte-caso-factura-72650.md
        $business_id_c = request()->session()->get('user.business_id');
        $tx_check = Transaction::where('business_id', $business_id_c)->findOrFail($id);
        if ($tx_check->status === 'final'
            && !auth()->user()->can('superadmin')
            && !auth()->user()->can('business_settings.access')) {
            return back()->with('status', [
                'success' => 0,
                'msg' => 'Esta venta ya está finalizada. Solo un administrador puede modificarla. Si hay un error, pídele al gerente que la anule y crea una nueva desde cero.',
            ]);
        }

        //Check if the transaction can be edited or not.
        $edit_days = request()->session()->get('business.transaction_edit_days');
        if (! $this->transactionUtil->canBeEdited($id, $edit_days)) {
            return back()
                ->with('status', ['success' => 0,
                    'msg' => __('messages.transaction_edit_not_allowed', ['days' => $edit_days]), ]);
        }

        //Check if return exist then not allowed
        if ($this->transactionUtil->isReturnExist($id)) {
            return back()->with('status', ['success' => 0,
                'msg' => __('lang_v1.return_exist')]);
        }

        $business_id = request()->session()->get('user.business_id');

        $business_details = $this->businessUtil->getDetails($business_id);
        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);

        $transaction = Transaction::where('business_id', $business_id)
                            ->with(['price_group', 'types_of_service', 'media', 'media.uploaded_by_user'])
                            ->whereIn('type', ['sell', 'sales_order'])
                            ->findorfail($id);

        // If ZATCA module is installed and this transaction is successfully synced, prevent edit
        $moduleUtil = new ModuleUtil();
        if ($moduleUtil->isModuleInstalled('ZatcaIntegrationKsa')) {
            if (!empty($transaction) && $transaction->zatca_status === 'success') {
                return back()->with('status', ['success' => 0,
                    'msg' => __('lang_v1.invoice_synced_to_zatca_cannot_be_edited')]);
            }
        }

        if ($transaction->type == 'sales_order' && ! auth()->user()->can('so.update')) {
            abort(403, 'Unauthorized action.');
        }

        $location_id = $transaction->location_id;
        $location_printer_type = BusinessLocation::find($location_id)->receipt_printer_type;

        $sell_details = TransactionSellLine::join(
                            'products AS p',
                            'transaction_sell_lines.product_id',
                            '=',
                            'p.id'
                        )
                        ->join(
                            'variations AS variations',
                            'transaction_sell_lines.variation_id',
                            '=',
                            'variations.id'
                        )
                        ->join(
                            'product_variations AS pv',
                            'variations.product_variation_id',
                            '=',
                            'pv.id'
                        )
                        ->leftjoin('variation_location_details AS vld', function ($join) use ($location_id) {
                            $join->on('variations.id', '=', 'vld.variation_id')
                                ->where('vld.location_id', '=', $location_id);
                        })
                        ->leftjoin('units', 'units.id', '=', 'p.unit_id')
                        ->leftjoin('units as u', 'p.secondary_unit_id', '=', 'u.id')
                        ->where('transaction_sell_lines.transaction_id', $id)
                        ->with(['warranties', 'so_line'])
                        ->select(
                            DB::raw("IF(pv.is_dummy = 0, CONCAT(p.name, ' (', pv.name, ':',variations.name, ')'), p.name) AS product_name"),
                            'p.id as product_id',
                            'p.image as product_image',
                            'p.enable_stock',
                            'p.name as product_actual_name',
                            'p.type as product_type',
                            'pv.name as product_variation_name',
                            'pv.is_dummy as is_dummy',
                            'variations.name as variation_name',
                            'variations.sub_sku',
                            'p.barcode_type',
                            'p.enable_sr_no',
                            'variations.id as variation_id',
                            'units.short_name as unit',
                            'units.allow_decimal as unit_allow_decimal',
                            'u.short_name as second_unit',
                            'transaction_sell_lines.secondary_unit_quantity',
                            'transaction_sell_lines.tax_id as tax_id',
                            'transaction_sell_lines.item_tax as item_tax',
                            'transaction_sell_lines.unit_price as default_sell_price',
                            'transaction_sell_lines.unit_price_inc_tax as sell_price_inc_tax',
                            'transaction_sell_lines.unit_price_before_discount as unit_price_before_discount',
                            'transaction_sell_lines.id as transaction_sell_lines_id',
                            'transaction_sell_lines.id',
                            'transaction_sell_lines.quantity as quantity_ordered',
                            'transaction_sell_lines.sell_line_note as sell_line_note',
                            'transaction_sell_lines.parent_sell_line_id',
                            'transaction_sell_lines.lot_no_line_id',
                            'transaction_sell_lines.line_discount_type',
                            'transaction_sell_lines.line_discount_amount',
                            'transaction_sell_lines.res_service_staff_id',
                            'units.id as unit_id',
                            'transaction_sell_lines.sub_unit_id',
                            'transaction_sell_lines.so_line_id',
                            DB::raw('vld.qty_available + transaction_sell_lines.quantity AS qty_available')
                        )
                        ->get();

        if (! empty($sell_details)) {
            foreach ($sell_details as $key => $value) {

                $variation = Variation::with('media')->findOrFail($value->variation_id);
                $sell_details[$key]->media = $variation->media;

                //If modifier or combo sell line then unset
                if (! empty($sell_details[$key]->parent_sell_line_id)) {
                    unset($sell_details[$key]);
                } else {
                    if ($transaction->status != 'final') {
                        $actual_qty_avlbl = $value->qty_available - $value->quantity_ordered;
                        $sell_details[$key]->qty_available = $actual_qty_avlbl;
                        $value->qty_available = $actual_qty_avlbl;
                    }

                    $sell_details[$key]->formatted_qty_available = $this->productUtil->num_f($value->qty_available, false, null, true);
                    $lot_numbers = [];
                    if (request()->session()->get('business.enable_lot_number') == 1) {
                        $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($value->variation_id, $business_id, $location_id);
                        foreach ($lot_number_obj as $lot_number) {
                            //If lot number is selected added ordered quantity to lot quantity available
                            if ($value->lot_no_line_id == $lot_number->purchase_line_id) {
                                $lot_number->qty_available += $value->quantity_ordered;
                            }

                            $lot_number->qty_formated = $this->transactionUtil->num_f($lot_number->qty_available);
                            $lot_numbers[] = $lot_number;
                        }
                    }
                    $sell_details[$key]->lot_numbers = $lot_numbers;

                    if (! empty($value->sub_unit_id)) {
                        $value = $this->productUtil->changeSellLineUnit($business_id, $value);
                        $sell_details[$key] = $value;
                    }

                    if ($this->transactionUtil->isModuleEnabled('modifiers')) {
                        //Add modifier details to sel line details
                        $sell_line_modifiers = TransactionSellLine::where('parent_sell_line_id', $sell_details[$key]->transaction_sell_lines_id)
                            ->where('children_type', 'modifier')
                            ->get();
                        $modifiers_ids = [];
                        if (count($sell_line_modifiers) > 0) {
                            $sell_details[$key]->modifiers = $sell_line_modifiers;
                            foreach ($sell_line_modifiers as $sell_line_modifier) {
                                $modifiers_ids[] = $sell_line_modifier->variation_id;
                            }
                        }
                        $sell_details[$key]->modifiers_ids = $modifiers_ids;

                        //add product modifier sets for edit
                        $this_product = Product::find($sell_details[$key]->product_id);
                        if (count($this_product->modifier_sets) > 0) {
                            $sell_details[$key]->product_ms = $this_product->modifier_sets;
                        }
                    }

                    //Get details of combo items
                    if ($sell_details[$key]->product_type == 'combo') {
                        $sell_line_combos = TransactionSellLine::where('parent_sell_line_id', $sell_details[$key]->transaction_sell_lines_id)
                            ->where('children_type', 'combo')
                            ->get()
                            ->toArray();
                        if (! empty($sell_line_combos)) {
                            $sell_details[$key]->combo_products = $sell_line_combos;
                        }

                        //calculate quantity available if combo product
                        $combo_variations = [];
                        foreach ($sell_line_combos as $combo_line) {
                            $combo_variations[] = [
                                'variation_id' => $combo_line['variation_id'],
                                'quantity' => $combo_line['quantity'] / $sell_details[$key]->quantity_ordered,
                                'unit_id' => null,
                            ];
                        }
                        $sell_details[$key]->qty_available =
                        $this->productUtil->calculateComboQuantity($location_id, $combo_variations);

                        if ($transaction->status == 'final') {
                            $sell_details[$key]->qty_available = $sell_details[$key]->qty_available + $sell_details[$key]->quantity_ordered;
                        }

                        $sell_details[$key]->formatted_qty_available = $this->productUtil->num_f($sell_details[$key]->qty_available, false, null, true);
                    }
                }
            }
        }

        $commsn_agnt_setting = $business_details->sales_cmsn_agnt;
        $commission_agent = [];
        if ($commsn_agnt_setting == 'user') {
            $commission_agent = User::forDropdown($business_id);
        } elseif ($commsn_agnt_setting == 'cmsn_agnt') {
            $commission_agent = User::saleCommissionAgentsDropdown($business_id);
        }

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }
        if (auth()->user()->can('supplier.create') && auth()->user()->can('customer.create')) {
            $types['both'] = __('lang_v1.both_supplier_customer');
        }
        $customer_groups = CustomerGroup::forDropdown($business_id);

        $transaction->transaction_date = $this->transactionUtil->format_date($transaction->transaction_date, true);

        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

        $waiters = [];
        if ($this->productUtil->isModuleEnabled('service_staff') && ! empty($pos_settings['inline_service_staff'])) {
            $waiters = $this->productUtil->serviceStaffDropdown($business_id);
        }

        $invoice_schemes = [];
        $default_invoice_schemes = null;

        if ($transaction->status == 'draft') {
            $invoice_schemes = InvoiceScheme::forDropdown($business_id);
            $default_invoice_schemes = InvoiceScheme::getDefault($business_id);
        }

        $redeem_details = [];
        if (request()->session()->get('business.enable_rp') == 1) {
            $redeem_details = $this->transactionUtil->getRewardRedeemDetails($business_id, $transaction->contact_id);

            $redeem_details['points'] += $transaction->rp_redeemed;
            $redeem_details['points'] -= $transaction->rp_earned;
        }

        $edit_discount = auth()->user()->can('edit_product_discount_from_sale_screen');
        $edit_price = auth()->user()->can('edit_product_price_from_sale_screen');

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }

        $shipping_statuses = $this->transactionUtil->shipping_statuses();

        $common_settings = session()->get('business.common_settings');
        $is_warranty_enabled = ! empty($common_settings['enable_product_warranty']) ? true : false;
        $warranties = $is_warranty_enabled ? Warranty::forDropdown($business_id) : [];

        $statuses = Transaction::sell_statuses();

        $is_order_request_enabled = false;
        $is_crm = $this->moduleUtil->isModuleInstalled('Crm');
        if ($is_crm) {
            $crm_settings = Business::where('id', auth()->user()->business_id)
                                ->value('crm_settings');
            $crm_settings = ! empty($crm_settings) ? json_decode($crm_settings, true) : [];

            if (! empty($crm_settings['enable_order_request'])) {
                $is_order_request_enabled = true;
            }
        }

        $sales_orders = [];
        if (! empty($pos_settings['enable_sales_order']) || $is_order_request_enabled) {
            $sales_orders = Transaction::where('business_id', $business_id)
                                ->where('type', 'sales_order')
                                ->where('contact_id', $transaction->contact_id)
                                ->where(function ($q) use ($transaction) {
                                    $q->where('status', '!=', 'completed');

                                    if (! empty($transaction->sales_order_ids)) {
                                        $q->orWhereIn('id', $transaction->sales_order_ids);
                                    }
                                })
                                ->pluck('invoice_no', 'id');
        }

        $payment_types = $this->transactionUtil->payment_types($transaction->location_id, false, $business_id);

        $payment_lines = $this->transactionUtil->getPaymentDetails($id);
        //If no payment lines found then add dummy payment line.
        if (empty($payment_lines)) {
            $payment_lines[] = $this->dummyPaymentLine;
        }

        $change_return = $this->dummyPaymentLine;

        $customer_due = $this->transactionUtil->getContactDue($transaction->contact_id, $transaction->business_id);

        $customer_due = $customer_due != 0 ? $this->transactionUtil->num_f($customer_due, true) : '';

        //Added check because $users is of no use if enable_contact_assign if false
        $users = config('constants.enable_contact_assign') ? User::forDropdown($business_id, false, false, false, true) : [];

        return view('sell.edit')
            ->with(compact('business_details', 'taxes', 'sell_details', 'transaction', 'commission_agent', 'types', 'customer_groups', 'pos_settings', 'waiters', 'invoice_schemes', 'default_invoice_schemes', 'redeem_details', 'edit_discount', 'edit_price', 'shipping_statuses', 'warranties', 'statuses', 'sales_orders', 'payment_types', 'accounts', 'payment_lines', 'change_return', 'is_order_request_enabled', 'customer_due', 'users'));
    }

    /**
     * Display a listing sell drafts.
     *
     * @return \Illuminate\Http\Response
     */
    public function getDrafts()
    {
        if (! auth()->user()->can('draft.view_all') && ! auth()->user()->can('draft.view_own')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);

        $sales_representative = User::forDropdown($business_id, false, false, true);

        return view('sale_pos.draft')
            ->with(compact('business_locations', 'customers', 'sales_representative'));
    }

    /**
     * Display a listing sell quotations.
     *
     * @return \Illuminate\Http\Response
     */
    public function getQuotations()
    {
        if (! auth()->user()->can('quotation.view_all') && ! auth()->user()->can('quotation.view_own')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);

        $sales_representative = User::forDropdown($business_id, false, false, true);

        return view('sale_pos.quotations')
                ->with(compact('business_locations', 'customers', 'sales_representative'));
    }

    /**
     * Send the datatable response for draft or quotations.
     *
     * @return \Illuminate\Http\Response
     */
    public function getDraftDatables()
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $is_quotation = request()->input('is_quotation', 0);

            $is_woocommerce = $this->moduleUtil->isModuleInstalled('Woocommerce');

            $sells = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
                ->join(
                    'business_locations AS bl',
                    'transactions.location_id',
                    '=',
                    'bl.id'
                )
                ->leftJoin('transaction_sell_lines as tsl', function ($join) {
                    $join->on('transactions.id', '=', 'tsl.transaction_id')
                        ->whereNull('tsl.parent_sell_line_id');
                })
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'draft')
                ->select(
                    'transactions.id',
                    'transaction_date',
                    'invoice_no',
                    'contacts.name',
                    'contacts.mobile',
                    'contacts.supplier_business_name',
                    'bl.name as business_location',
                    'is_direct_sale',
                    'sub_status',
                    DB::raw('COUNT( DISTINCT tsl.id) as total_items'),
                    DB::raw('SUM(tsl.quantity) as total_quantity'),
                    DB::raw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as added_by"),
                    'transactions.is_export'
                );

            if ($is_quotation == 1) {
                $sells->where('transactions.sub_status', 'quotation');

                if (! auth()->user()->can('quotation.view_all') && auth()->user()->can('quotation.view_own')) {
                    $sells->where('transactions.created_by', request()->session()->get('user.id'));
                }
            } else {
                if (! auth()->user()->can('draft.view_all') && auth()->user()->can('draft.view_own')) {
                    $sells->where('transactions.created_by', request()->session()->get('user.id'));
                }
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            }

            if (! empty(request()->start_date) && ! empty(request()->end_date)) {
                $start = request()->start_date;
                $end = request()->end_date;
                $sells->whereDate('transaction_date', '>=', $start)
                            ->whereDate('transaction_date', '<=', $end);
            }

            if (request()->has('location_id')) {
                $location_id = request()->get('location_id');
                if (! empty($location_id)) {
                    $sells->where('transactions.location_id', $location_id);
                }
            }

            if (request()->has('created_by')) {
                $created_by = request()->get('created_by');
                if (! empty($created_by)) {
                    $sells->where('transactions.created_by', $created_by);
                }
            }

            if (! empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $sells->where('contacts.id', $customer_id);
            }

            if ($is_woocommerce) {
                $sells->addSelect('transactions.woocommerce_order_id');
            }

            $sells->groupBy('transactions.id');

            return Datatables::of($sells)
                 ->addColumn(
                    'action', function ($row) {
                        $html = '<div class="btn-group">
                                <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-info tw-w-max dropdown-toggle" 
                                    data-toggle="dropdown" aria-expanded="false">'.
                                    __('messages.actions').
                                    '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                    </span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                    <li>
                                    <a href="#" data-href="'.action([\App\Http\Controllers\SellController::class, 'show'], [$row->id]).'" class="btn-modal" data-container=".view_modal">
                                        <i class="fas fa-eye" aria-hidden="true"></i>'.__('messages.view').'
                                    </a>
                                    </li>';

                        if (auth()->user()->can('draft.update') || auth()->user()->can('quotation.update')) {
                            if ($row->is_direct_sale == 1) {
                                $html .= '<li>
                                            <a target="_blank" href="'.action([\App\Http\Controllers\SellController::class, 'edit'], [$row->id]).'">
                                                <i class="fas fa-edit"></i>'.__('messages.edit').'
                                            </a>
                                        </li>';
                            } else {
                                $html .= '<li>
                                            <a target="_blank" href="'.action([\App\Http\Controllers\SellPosController::class, 'edit'], [$row->id]).'">
                                                <i class="fas fa-edit"></i>'.__('messages.edit').'
                                            </a>
                                        </li>';
                            }
                        }

                        $html .= '<li>
                                    <a href="#" class="print-invoice" data-href="'.route('sell.printInvoice', [$row->id]).'"><i class="fas fa-print" aria-hidden="true"></i>'.__('messages.print').'</a>
                                </li>';

                        if (config('constants.enable_download_pdf')) {
                            $sub_status = $row->sub_status == 'proforma' ? 'proforma' : '';
                            $html .= '<li>
                                        <a href="'.route('quotation.downloadPdf', ['id' => $row->id, 'sub_status' => $sub_status]).'" target="_blank">
                                            <i class="fas fa-print" aria-hidden="true"></i>'.__('lang_v1.download_pdf').'
                                        </a>
                                    </li>';
                        }

                        if ((auth()->user()->can('sell.create') || auth()->user()->can('direct_sell.access')) && config('constants.enable_convert_draft_to_invoice')) {
                            $html .= '<li>
                                        <a href="'.action([\App\Http\Controllers\SellPosController::class, 'convertToInvoice'], [$row->id]).'" class="convert-draft"><i class="fas fa-sync-alt"></i>'.__('lang_v1.convert_to_invoice').'</a>
                                    </li>';
                        }

                        if ($row->sub_status != 'proforma') {
                            $html .= '<li>
                                        <a href="'.action([\App\Http\Controllers\SellPosController::class, 'convertToProforma'], [$row->id]).'" class="convert-to-proforma"><i class="fas fa-sync-alt"></i>'.__('lang_v1.convert_to_proforma').'</a>
                                    </li>';
                        }

                        if (auth()->user()->can('draft.delete') || auth()->user()->can('quotation.delete')) {
                            $html .= '<li>
                                <a href="'.action([\App\Http\Controllers\SellPosController::class, 'destroy'], [$row->id]).'" class="delete-sale"><i class="fas fa-trash"></i>'.__('messages.delete').'</a>
                                </li>';
                        }

                        if ($row->sub_status == 'quotation') {
                            $html .= '<li>
                                        <a href="'.action([\App\Http\Controllers\SellPosController::class, 'copyQuotation'],[$row->id]).'" 
                                        class="copy_quotation"><i class="fas fa-copy"></i>'.
                                        __("lang_v1.copy_quotation").'</a>
                                    </li>
                                    <li>
                                        <a href="#" data-href="'.action("\App\Http\Controllers\NotificationController@getTemplate", ["transaction_id" => $row->id,"template_for" => "new_quotation"]).'" class="btn-modal" data-container=".view_modal"><i class="fa fa-envelope" aria-hidden="true"></i>' . __("lang_v1.new_quotation_notification") . '
                                        </a>
                                    </li>';

                            $html .= '<li>
                                        <a href="'.action("\App\Http\Controllers\SellPosController@showInvoiceUrl", [$row->id]).'" class="view_invoice_url"><i class="fas fa-eye"></i>'.__("lang_v1.view_quote_url").'</a>
                                    </li>';
                        }

                        $html .= '</ul></div>';

                        return $html;
                    })
                ->removeColumn('id')
                ->editColumn('invoice_no', function ($row) {
                    $invoice_no = $row->invoice_no;
                    if (! empty($row->woocommerce_order_id)) {
                        $invoice_no .= ' <i class="fab fa-wordpress text-primary no-print" title="'.__('lang_v1.synced_from_woocommerce').'"></i>';
                    }

                    if ($row->sub_status == 'proforma') {
                        $invoice_no .= '<br><span class="label bg-gray">'.__('lang_v1.proforma_invoice').'</span>';
                    }

                    if (! empty($row->is_export)) {
                        $invoice_no .= '</br><small class="label label-default no-print" title="'.__('lang_v1.export').'">'.__('lang_v1.export').'</small>';
                    }

                    return $invoice_no;
                })
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn('total_items', '{{@format_quantity($total_items)}}')
                ->editColumn('total_quantity', '{{@format_quantity($total_quantity)}}')
                ->addColumn('conatct_name', '@if(!empty($supplier_business_name)) {{$supplier_business_name}}, <br>@endif {{$name}}')
                ->filterColumn('conatct_name', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('contacts.name', 'like', "%{$keyword}%")
                        ->orWhere('contacts.supplier_business_name', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('added_by', function ($query, $keyword) {
                    $query->whereRaw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) like ?", ["%{$keyword}%"]);
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can('sell.view')) {
                            return  action([\App\Http\Controllers\SellController::class, 'show'], [$row->id]);
                        } else {
                            return '';
                        }
                    }, ])
                ->rawColumns(['action', 'invoice_no', 'transaction_date', 'conatct_name'])
                ->make(true);
        }
    }

    /**
     * Creates copy of the requested sale.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function duplicateSell($id)
    {
        if (! auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $user_id = request()->session()->get('user.id');

            $transaction = Transaction::where('business_id', $business_id)
                            ->where('type', 'sell')
                            ->findorfail($id);
            $duplicate_transaction_data = [];
            foreach ($transaction->toArray() as $key => $value) {
                if (! in_array($key, ['id', 'created_at', 'updated_at'])) {
                    $duplicate_transaction_data[$key] = $value;
                }
            }
            $duplicate_transaction_data['status'] = 'draft';
            $duplicate_transaction_data['payment_status'] = null;
            $duplicate_transaction_data['transaction_date'] = \Carbon::now();
            $duplicate_transaction_data['created_by'] = $user_id;
            $duplicate_transaction_data['invoice_token'] = null;

            DB::beginTransaction();
            $duplicate_transaction_data['invoice_no'] = $this->transactionUtil->getInvoiceNumber($business_id, 'draft', $duplicate_transaction_data['location_id']);

            //Create duplicate transaction
            $duplicate_transaction = Transaction::create($duplicate_transaction_data);

            //Create duplicate transaction sell lines
            $duplicate_sell_lines_data = [];

            foreach ($transaction->sell_lines as $sell_line) {
                $new_sell_line = [];
                foreach ($sell_line->toArray() as $key => $value) {
                    if (! in_array($key, ['id', 'transaction_id', 'created_at', 'updated_at', 'lot_no_line_id'])) {
                        $new_sell_line[$key] = $value;
                    }
                }

                $duplicate_sell_lines_data[] = $new_sell_line;
            }

            $duplicate_transaction->sell_lines()->createMany($duplicate_sell_lines_data);

            DB::commit();

            $output = ['success' => 0,
                'msg' => trans('lang_v1.duplicate_sell_created_successfully'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => trans('messages.something_went_wrong'),
            ];
        }

        if (! empty($duplicate_transaction)) {
            if ($duplicate_transaction->is_direct_sale == 1) {
                return redirect()->action([\App\Http\Controllers\SellController::class, 'edit'], [$duplicate_transaction->id])->with(['status', $output]);
            } else {
                return redirect()->action([\App\Http\Controllers\SellPosController::class, 'edit'], [$duplicate_transaction->id])->with(['status', $output]);
            }
        } else {
            abort(404, 'Not Found.');
        }
    }

    /**
     * Shows modal to edit shipping details.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function editShipping($id)
    {
        $is_admin = $this->businessUtil->is_admin(auth()->user());

        if (! $is_admin && ! auth()->user()->hasAnyPermission(['access_shipping', 'access_own_shipping', 'access_commission_agent_shipping'])) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $transaction = Transaction::where('business_id', $business_id)
                                ->with(['media', 'media.uploaded_by_user'])
                                ->findorfail($id);

        $users = User::forDropdown($business_id, false, false, false);

        $shipping_statuses = $this->transactionUtil->shipping_statuses();

        $activities = Activity::forSubject($transaction)
           ->with(['causer', 'subject'])
           ->where('activity_log.description', 'shipping_edited')
           ->latest()
           ->get();

        return view('sell.partials.edit_shipping')
               ->with(compact('transaction', 'shipping_statuses', 'activities', 'users'));
    }

    /**
     * Update shipping.
     *
     * @param  Request  $request, int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateShipping(Request $request, $id)
    {
        $is_admin = $this->businessUtil->is_admin(auth()->user());

        if (! $is_admin && ! auth()->user()->hasAnyPermission(['access_shipping', 'access_own_shipping', 'access_commission_agent_shipping'])) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only([
                'shipping_details', 'shipping_address',
                'shipping_status', 'delivered_to', 'delivery_person', 'shipping_custom_field_1', 'shipping_custom_field_2', 'shipping_custom_field_3', 'shipping_custom_field_4', 'shipping_custom_field_5',
            ]);


            $business_id = $request->session()->get('user.business_id');

            $transaction = Transaction::where('business_id', $business_id)
                                ->findOrFail($id);

            $transaction_before = $transaction->replicate();

            $transaction->update($input);

            $activity_property = ['update_note' => $request->input('shipping_note', '')];
            $this->transactionUtil->activityLog($transaction, 'shipping_edited', $transaction_before, $activity_property);

            $output = ['success' => 1,
                'msg' => trans('lang_v1.updated_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => trans('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Display list of shipments.
     *
     * @return \Illuminate\Http\Response
     */
    public function shipments()
    {
        $is_admin = $this->businessUtil->is_admin(auth()->user());

        if (! $is_admin && ! auth()->user()->hasAnyPermission(['access_shipping', 'access_own_shipping', 'access_commission_agent_shipping'])) {
            abort(403, 'Unauthorized action.');
        }

        $shipping_statuses = $this->transactionUtil->shipping_statuses();

        $business_id = request()->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);

        $sales_representative = User::forDropdown($business_id, false, false, true);

        $is_service_staff_enabled = $this->transactionUtil->isModuleEnabled('service_staff');

        //Service staff filter
        $service_staffs = null;
        if ($this->productUtil->isModuleEnabled('service_staff')) {
            $service_staffs = $this->productUtil->serviceStaffDropdown($business_id);
        }

        $delevery_person = User::forDropdown($business_id, false, false, true);

        return view('sell.shipments')->with(compact('shipping_statuses'))
                ->with(compact('business_locations', 'customers', 'sales_representative', 'is_service_staff_enabled', 'service_staffs', 'delevery_person'));
    }

    public function viewMedia($model_id)
    {
        if (request()->ajax()) {
            $model_type = request()->input('model_type');
            $business_id = request()->session()->get('user.business_id');

            $query = Media::where('business_id', $business_id)
                        ->where('model_id', $model_id)
                        ->where('model_type', $model_type);

            $title = __('lang_v1.attachments');
            if (! empty(request()->input('model_media_type'))) {
                $query->where('model_media_type', request()->input('model_media_type'));
                $title = __('lang_v1.shipping_documents');
            }

            $medias = $query->get();

            return view('sell.view_media')->with(compact('medias', 'title'));
        }
    }

    public function resetMapping()
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        Artisan::call('pos:mapPurchaseSell');

        echo 'Mapping reset success';
        exit;
    }

    /**
     * Checks if invoice number exists
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkInvoiceNumber(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $invoice_no = $request->input('invoice_no');
        $transaction_id = $request->input('transaction_id');

        $query = Transaction::where('business_id', $business_id)
                        ->where('invoice_no', $invoice_no);
        
        if (!empty($transaction_id)) {
            $query->where('id', '!=', $transaction_id);
        }
        
        $count = $query->count();
        
        if ($count == 0) {
            echo 'true';
            exit;
        } else {
            echo 'false';
            exit;
        }
    }
}
