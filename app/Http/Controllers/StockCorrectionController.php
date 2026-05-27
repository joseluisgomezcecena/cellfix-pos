<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Product;
use App\PurchaseLine;
use App\StockCorrection;
use App\Transaction;
use App\Variation;
use App\VariationLocationDetails;
use App\Utils\ProductUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class StockCorrectionController extends Controller
{
    protected $productUtil;

    public function __construct(ProductUtil $productUtil)
    {
        $this->productUtil = $productUtil;
    }

    /**
     * Allowed reasons (key => label translation key).
     */
    public static function reasons()
    {
        return [
            'producto_encontrado' => __('lang_v1.reason_producto_encontrado'),
            'conteo_fisico' => __('lang_v1.reason_conteo_fisico'),
            'merma' => __('lang_v1.reason_merma'),
            'robo' => __('lang_v1.reason_robo'),
            'ajuste' => __('lang_v1.reason_ajuste'),
        ];
    }

    private function authorizeAccess()
    {
        if (! auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index()
    {
        $this->authorizeAccess();

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $query = StockCorrection::where('stock_corrections.business_id', $business_id)
                ->leftJoin('business_locations as bl', 'bl.id', '=', 'stock_corrections.location_id')
                ->leftJoin('products as p', 'p.id', '=', 'stock_corrections.product_id')
                ->leftJoin('users as u', 'u.id', '=', 'stock_corrections.created_by')
                ->select([
                    'stock_corrections.id',
                    'stock_corrections.created_at',
                    'bl.name as location_name',
                    'p.name as product_name',
                    'p.sku as sku',
                    'stock_corrections.type',
                    'stock_corrections.quantity',
                    'stock_corrections.qty_before',
                    'stock_corrections.qty_after',
                    'stock_corrections.reason',
                    'stock_corrections.note',
                    DB::raw("TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) as user_name"),
                ]);

            $reasons = self::reasons();

            return DataTables::of($query)
                ->editColumn('created_at', function ($row) {
                    return \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i');
                })
                ->editColumn('type', function ($row) {
                    return $row->type === 'add'
                        ? '<span class="label label-success">'.__('lang_v1.type_add').'</span>'
                        : '<span class="label label-danger">'.__('lang_v1.type_deduct').'</span>';
                })
                ->editColumn('quantity', function ($row) {
                    $sign = $row->type === 'add' ? '+' : '−';
                    return $sign.' '.(float) $row->quantity;
                })
                ->editColumn('reason', function ($row) use ($reasons) {
                    return $reasons[$row->reason] ?? $row->reason;
                })
                ->addColumn('product_full', function ($row) {
                    return $row->product_name.($row->sku ? ' <small class="text-muted">('.$row->sku.')</small>' : '');
                })
                ->rawColumns(['type', 'product_full'])
                ->make(true);
        }

        return view('stock_correction.index');
    }

    public function create()
    {
        $this->authorizeAccess();

        $business_id = request()->session()->get('user.business_id');
        $locations = BusinessLocation::forDropdown($business_id);
        $reasons = self::reasons();

        return view('stock_correction.create', compact('locations', 'reasons'));
    }

    /**
     * AJAX product search for the chosen location (returns variations + current stock).
     */
    public function searchProducts(Request $request)
    {
        $this->authorizeAccess();

        $business_id = $request->session()->get('user.business_id');
        $term = $request->get('term', '');
        $location_id = $request->get('location_id');

        $results = DB::table('products as p')
            ->join('variations as v', 'v.product_id', '=', 'p.id')
            ->leftJoin('variation_location_details as vld', function ($join) use ($location_id) {
                $join->on('vld.variation_id', '=', 'v.id')
                    ->where('vld.location_id', '=', $location_id);
            })
            ->where('p.business_id', $business_id)
            ->where('p.enable_stock', 1)
            ->where(function ($q) use ($term) {
                $q->where('p.name', 'like', "%{$term}%")
                    ->orWhere('p.sku', 'like', "%{$term}%")
                    ->orWhere('v.sub_sku', 'like', "%{$term}%");
            })
            ->select([
                'p.id as product_id',
                'p.name',
                'p.sku',
                'v.id as variation_id',
                'v.name as variation_name',
                'v.sub_sku',
                DB::raw('COALESCE(vld.qty_available, 0) as current_stock'),
            ])
            ->orderBy('p.name')
            ->limit(30)
            ->get();

        $out = [];
        foreach ($results as $r) {
            $label = $r->name;
            if (! empty($r->variation_name) && $r->variation_name !== 'DUMMY') {
                $label .= ' - '.$r->variation_name;
            }
            $sku = $r->sub_sku ?: $r->sku;
            $out[] = [
                'id' => $r->variation_id,
                'text' => $label.' ('.$sku.') — '.__('lang_v1.current_stock').': '.(float) $r->current_stock,
                'product_id' => $r->product_id,
                'current_stock' => (float) $r->current_stock,
            ];
        }

        return response()->json(['results' => $out]);
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();

        $request->validate([
            'location_id' => 'required|integer',
            'product_id' => 'required|integer',
            'variation_id' => 'required|integer',
            'type' => 'required|in:add,deduct',
            'quantity' => 'required|numeric|min:0.0001',
            'reason' => 'required|string',
        ]);

        $business_id = $request->session()->get('user.business_id');
        $location_id = (int) $request->input('location_id');
        $product_id = (int) $request->input('product_id');
        $variation_id = (int) $request->input('variation_id');
        $type = $request->input('type');
        $qty = (float) $request->input('quantity');
        $reason = $request->input('reason');

        if (! array_key_exists($reason, self::reasons())) {
            return redirect()->back()->with('status', ['success' => 0, 'msg' => __('messages.something_went_wrong')]);
        }

        try {
            DB::beginTransaction();

            // Verify location belongs to this business
            BusinessLocation::where('business_id', $business_id)->findOrFail($location_id);

            $qty_before = (float) (VariationLocationDetails::where('variation_id', $variation_id)
                ->where('location_id', $location_id)
                ->value('qty_available') ?? 0);

            if ($type === 'add') {
                // Para que la venta pueda mapearse (FIFO compra->venta) NO basta con subir qty_available:
                // hay que crear el respaldo de entrada (transaccion opening_stock + purchase_line),
                // igual que la pantalla de Stock de apertura. Si no, el POS lanza
                // "desajuste entre la cantidad vendida y la compra".
                $product = Product::with('product_tax')->find($product_id);
                $variation = Variation::find($variation_id);

                $tax_percent = ! empty($product->product_tax->amount) ? $product->product_tax->amount : 0;
                $tax_id = ! empty($product->product_tax->id) ? $product->product_tax->id : null;
                $purchase_price = ! empty($variation->default_purchase_price) ? $variation->default_purchase_price : 0;
                $item_tax = $this->productUtil->calc_percentage($purchase_price, $tax_percent);
                $purchase_price_inc_tax = $purchase_price + $item_tax;

                $purchase_line = new PurchaseLine();
                $purchase_line->product_id = $product_id;
                $purchase_line->variation_id = $variation_id;
                $purchase_line->quantity = $qty;
                $purchase_line->item_tax = $item_tax;
                $purchase_line->tax_id = $tax_id;
                $purchase_line->pp_without_discount = $purchase_price;
                $purchase_line->purchase_price = $purchase_price;
                $purchase_line->purchase_price_inc_tax = $purchase_price_inc_tax;

                $this->productUtil->updateProductQuantity($location_id, $product_id, $variation_id, $qty, 0, null, false);

                $reason_label = self::reasons()[$reason] ?? $reason;
                $note_text = 'Corrección de inventario: '.$reason_label;
                if ($request->input('note')) {
                    $note_text .= ' - '.$request->input('note');
                }

                $transaction = Transaction::create([
                    'type' => 'opening_stock',
                    'opening_stock_product_id' => $product_id,
                    'status' => 'received',
                    'business_id' => $business_id,
                    'transaction_date' => \Carbon\Carbon::now()->toDateTimeString(),
                    'additional_notes' => $note_text,
                    'total_before_tax' => $purchase_price_inc_tax,
                    'location_id' => $location_id,
                    'final_total' => $purchase_price_inc_tax * $qty,
                    'payment_status' => 'paid',
                    'created_by' => auth()->id(),
                ]);

                $transaction->purchase_lines()->saveMany([$purchase_line]);

                // Mapea la nueva entrada contra ventas que hubieran quedado sin respaldo.
                $this->productUtil->adjustStockOverSelling($transaction);
            } else {
                $this->productUtil->decreaseProductQuantity($product_id, $variation_id, $location_id, $qty);
            }

            $qty_after = (float) (VariationLocationDetails::where('variation_id', $variation_id)
                ->where('location_id', $location_id)
                ->value('qty_available') ?? 0);

            StockCorrection::create([
                'business_id' => $business_id,
                'location_id' => $location_id,
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'type' => $type,
                'quantity' => $qty,
                'reason' => $reason,
                'qty_before' => $qty_before,
                'qty_after' => $qty_after,
                'note' => $request->input('note'),
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            $output = ['success' => 1, 'msg' => __('lang_v1.correction_saved', ['before' => $qty_before, 'after' => $qty_after])];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->route('stock-corrections.index')->with('status', $output);
    }
}
