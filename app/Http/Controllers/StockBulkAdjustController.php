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
use Maatwebsite\Excel\Facades\Excel;

class StockBulkAdjustController extends Controller
{
    protected $productUtil;

    public function __construct(ProductUtil $productUtil)
    {
        $this->productUtil = $productUtil;
    }

    private function authorizeAccess()
    {
        if (! auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index()
    {
        $this->authorizeAccess();

        $business_id = request()->session()->get('user.business_id');
        $locations = BusinessLocation::forDropdown($business_id);

        return view('stock_bulk_adjust.index', compact('locations'));
    }

    /**
     * Genera y descarga un archivo Excel con TODAS las variaciones del business activas
     * para una sucursal específica, con su stock actual. El usuario llena la columna
     * "stock_nuevo" y vuelve a subir el archivo.
     */
    public function downloadTemplate(Request $request)
    {
        $this->authorizeAccess();

        $request->validate(['location_id' => 'required|integer']);
        $business_id = $request->session()->get('user.business_id');
        $location_id = (int) $request->input('location_id');

        $location = BusinessLocation::where('business_id', $business_id)->findOrFail($location_id);

        $rows = DB::table('products as p')
            ->join('variations as v', 'v.product_id', '=', 'p.id')
            ->leftJoin('variation_location_details as vld', function ($j) use ($location_id) {
                $j->on('vld.variation_id', '=', 'v.id')->where('vld.location_id', '=', $location_id);
            })
            ->where('p.business_id', $business_id)
            ->where('p.is_inactive', 0)
            ->where('p.enable_stock', 1)
            ->whereNull('v.deleted_at')
            ->select(
                'v.id as variation_id',
                'p.sku as product_sku',
                'v.sub_sku',
                'p.name as product_name',
                'v.name as variation_name',
                DB::raw('COALESCE(vld.qty_available, 0) as stock_actual')
            )
            ->orderBy('p.name')
            ->get();

        $filename = 'ajuste_stock_' . str_replace(' ', '_', strtolower($location->name)) . '_loc' . $location->id . '_' . now()->toDateString() . '.xlsx';

        return Excel::download(new \App\Exports\StockBulkAdjustTemplateExport($rows, $location->name, $location->id), $filename);
    }

    /**
     * Procesa el Excel subido por el usuario. Por cada fila con stock_nuevo distinto
     * del stock_actual, aplica una Entrada (sumar diferencia) o Salida (restar diferencia)
     * usando la misma mecánica de stock_corrections (opening_stock + purchase_line para
     * que las ventas posteriores no fallen).
     */
    public function import(Request $request)
    {
        $this->authorizeAccess();

        $request->validate([
            'location_id' => 'required|integer',
            'file' => 'required|file|mimes:xlsx,xls,csv|max:20480', // 20 MB
        ]);

        // Archivos de inventario completo pueden tener miles de filas — dar margen.
        @ini_set('memory_limit', '512M');
        @set_time_limit(600);

        $business_id = $request->session()->get('user.business_id');
        $location_id = (int) $request->input('location_id');
        $note = $request->input('note');

        $location = BusinessLocation::where('business_id', $business_id)->findOrFail($location_id);

        try {
            $sheets = Excel::toArray([], $request->file('file'));
        } catch (\Throwable $e) {
            return redirect()->route('stock-bulk-adjust.index')
                ->with('status', ['success' => 0, 'msg' => 'No se pudo leer el archivo Excel: ' . $e->getMessage()]);
        }
        if (empty($sheets) || empty($sheets[0])) {
            return redirect()->route('stock-bulk-adjust.index')
                ->with('status', ['success' => 0, 'msg' => 'El archivo está vacío o no pudo leerse.']);
        }
        $data = $sheets[0];

        // === Validación de sucursal vía marcador embebido en el título ===
        // El template descargado tiene en A1 algo como:
        //   "AJUSTE MASIVO DE STOCK — SUCURSAL AMERICAS [LOCATION_ID:6]"
        // Si el usuario por error sube un archivo de otra sucursal, lo detectamos aquí.
        $title_cell = isset($data[0][0]) ? (string) $data[0][0] : '';
        if (!preg_match('/\[LOCATION_ID:(\d+)\]/', $title_cell, $m)) {
            return redirect()->route('stock-bulk-adjust.index')
                ->with('status', ['success' => 0, 'msg' => 'El archivo no es una plantilla válida (falta el marcador de sucursal en A1). Descarga una plantilla nueva y vuelve a intentar.']);
        }
        $file_location_id = (int) $m[1];
        if ($file_location_id !== $location_id) {
            $file_loc = BusinessLocation::where('business_id', $business_id)->find($file_location_id);
            $file_loc_name = $file_loc ? $file_loc->name : "id $file_location_id";
            return redirect()->route('stock-bulk-adjust.index')
                ->with('status', [
                    'success' => 0,
                    'msg' => "<strong>SUCURSAL EQUIVOCADA — no se aplicó ningún cambio.</strong><br>El archivo es de <strong>{$file_loc_name}</strong> pero seleccionaste <strong>{$location->name}</strong>. Vuelve a subirlo seleccionando la sucursal correcta.",
                ]);
        }

        $valid_variation_ids = DB::table('variations as v')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->where('p.business_id', $business_id)
            ->pluck('v.id')
            ->flip(); // O(1) lookup

        $summary = [
            'updated_add' => 0,
            'updated_deduct' => 0,
            'unchanged' => 0,
            'invalid' => 0,
            'errors' => [],
        ];

        // Para detectar duplicados de variation_id dentro del mismo archivo.
        // Si una variación aparece varias veces se rechazan las repeticiones
        // (mantenemos solo la PRIMERA ocurrencia para evitar dobles movimientos).
        $seen_variation_rows = [];

        $header_seen = false;
        foreach ($data as $row_index => $row) {
            $sheet_row = $row_index + 1; // Número de fila visible en Excel (1-based)

            // Saltar fila de encabezado (la primera fila con texto en col 0)
            if (!$header_seen) {
                $header_seen = true;
                if (!is_numeric($row[0] ?? null)) {
                    continue;
                }
            }

            $raw_vid = $row[0] ?? null;
            $variation_id = is_numeric($raw_vid) ? (int) $raw_vid : 0;
            $stock_nuevo_raw = isset($row[5]) ? trim((string) $row[5]) : '';

            // Sin variation_id (filas de instrucciones o header): skip silencioso.
            if ($variation_id <= 0) {
                continue;
            }

            // Sin stock_nuevo: usuario decidió no tocar este producto (válido).
            if ($stock_nuevo_raw === '') {
                continue;
            }

            // Normalizar formato: aceptar "$1,500", "1 500", "1500.5", "$ 1,500.50", etc.
            $stock_nuevo = $this->normalizeNumber($stock_nuevo_raw);
            if ($stock_nuevo === null) {
                $summary['invalid']++;
                $summary['errors'][] = "Fila {$sheet_row}: stock_nuevo \"{$stock_nuevo_raw}\" no es un número válido — esa fila NO se aplicó.";
                continue;
            }

            if (!$valid_variation_ids->has($variation_id)) {
                $summary['invalid']++;
                $summary['errors'][] = "Fila {$sheet_row}: variation_id {$variation_id} no pertenece a este negocio — esa fila NO se aplicó.";
                continue;
            }

            if ($stock_nuevo < 0) {
                $summary['invalid']++;
                $summary['errors'][] = "Fila {$sheet_row}: stock_nuevo {$stock_nuevo} no puede ser negativo — esa fila NO se aplicó.";
                continue;
            }

            // Detección de duplicados — solo la primera aparición de cada variation_id se procesa.
            if (isset($seen_variation_rows[$variation_id])) {
                $first_row = $seen_variation_rows[$variation_id];
                $summary['invalid']++;
                $summary['errors'][] = "Fila {$sheet_row}: variation_id {$variation_id} ya apareció en la fila {$first_row} — esa fila se ignoró para evitar doble ajuste.";
                continue;
            }
            $seen_variation_rows[$variation_id] = $sheet_row;

            // Cada fila se procesa atómicamente. Si una fila falla, las anteriores se mantienen.
            try {
                $qty_before = (float) (VariationLocationDetails::where('variation_id', $variation_id)
                    ->where('location_id', $location_id)
                    ->value('qty_available') ?? 0);

                $diff = round($stock_nuevo - $qty_before, 4);
                if (abs($diff) < 0.0001) {
                    $summary['unchanged']++;
                    continue;
                }

                DB::transaction(function () use ($business_id, $location_id, $location, $variation_id, $stock_nuevo, $qty_before, $diff, $note, &$summary) {
                    $variation = Variation::find($variation_id);
                    $product = Product::with('product_tax')->find($variation->product_id);
                    $type = $diff > 0 ? 'add' : 'deduct';

                    if ($diff > 0) {
                        $this->applyAdd($business_id, $location_id, $product, $variation, $diff, $note);
                    } else {
                        $this->productUtil->decreaseProductQuantity($product->id, $variation_id, $location_id, abs($diff));
                    }

                    $qty_after = (float) (VariationLocationDetails::where('variation_id', $variation_id)
                        ->where('location_id', $location_id)
                        ->value('qty_available') ?? 0);

                    StockCorrection::create([
                        'business_id' => $business_id,
                        'location_id' => $location_id,
                        'product_id' => $product->id,
                        'variation_id' => $variation_id,
                        'type' => $type,
                        'quantity' => abs($diff),
                        'reason' => 'conteo_fisico',
                        'qty_before' => $qty_before,
                        'qty_after' => $qty_after,
                        'note' => $note ?: 'Ajuste masivo por Excel — ' . $location->name,
                        'created_by' => auth()->id(),
                    ]);

                    if ($type === 'add') {
                        $summary['updated_add']++;
                    } else {
                        $summary['updated_deduct']++;
                    }
                });
            } catch (\Throwable $e) {
                $summary['invalid']++;
                $summary['errors'][] = "Fila {$sheet_row}: error procesando variation_id {$variation_id} — " . $e->getMessage();
                \Log::warning("[stock-bulk-adjust] row={$sheet_row} v={$variation_id} failed: " . $e->getMessage());
                // continuar al siguiente registro
            }
        }

        $total_errors = count($summary['errors']);
        $msg = "Procesado. <strong>Entradas: {$summary['updated_add']}</strong> · <strong>Salidas: {$summary['updated_deduct']}</strong> · Sin cambio: {$summary['unchanged']} · <strong style=\"color:#c0392b;\">Errores: {$summary['invalid']}</strong>";
        if ($total_errors > 0) {
            $shown = array_slice($summary['errors'], 0, 10);
            $msg .= '<br><br><strong>Detalle de errores:</strong><ul style="margin-bottom:0;">';
            foreach ($shown as $err) {
                $msg .= '<li>' . e($err) . '</li>';
            }
            if ($total_errors > 10) {
                $msg .= '<li><em>... y ' . ($total_errors - 10) . ' más (revisa storage/logs/laravel-*.log para la lista completa)</em></li>';
            }
            $msg .= '</ul>';
        }

        return redirect()->route('stock-bulk-adjust.index')
            ->with('status', ['success' => $total_errors > 0 ? 0 : 1, 'msg' => $msg]);
    }

    /**
     * Normaliza valores numéricos comunes que la gente suele pegar en Excel:
     *   "$1,500"      → 1500.0
     *   "1,500.50"    → 1500.5
     *   "1 500"       → 1500.0
     *   "1500.5"      → 1500.5
     *   "abc"         → null (inválido)
     *
     * Asume coma como separador de miles (formato MX). Si el valor no se puede
     * interpretar como número válido, devuelve null.
     */
    private function normalizeNumber($raw)
    {
        // Quita signo de pesos y espacios (incluye non-breaking space U+00A0).
        $s = trim(str_replace(['$', ' ', "\xc2\xa0"], '', (string) $raw));
        if ($s === '') {
            return null;
        }
        if (is_numeric($s)) {
            return (float) $s;
        }
        // Si SIN comas resulta numérico, asumimos coma = separador de miles.
        $no_commas = str_replace(',', '', $s);
        if (is_numeric($no_commas)) {
            return (float) $no_commas;
        }
        return null;
    }

    /**
     * Replica el path "Entrada" de StockCorrectionController: crea un purchase_line
     * + transaction opening_stock para que el POS pueda vender la nueva existencia
     * sin lanzar "desajuste compra/venta".
     */
    private function applyAdd($business_id, $location_id, $product, $variation, $qty, $note)
    {
        $tax_percent = !empty($product->product_tax->amount) ? $product->product_tax->amount : 0;
        $tax_id = !empty($product->product_tax->id) ? $product->product_tax->id : null;
        $purchase_price = !empty($variation->default_purchase_price) ? $variation->default_purchase_price : 0;
        $item_tax = $this->productUtil->calc_percentage($purchase_price, $tax_percent);
        $purchase_price_inc_tax = $purchase_price + $item_tax;

        $purchase_line = new PurchaseLine();
        $purchase_line->product_id = $product->id;
        $purchase_line->variation_id = $variation->id;
        $purchase_line->quantity = $qty;
        $purchase_line->item_tax = $item_tax;
        $purchase_line->tax_id = $tax_id;
        $purchase_line->pp_without_discount = $purchase_price;
        $purchase_line->purchase_price = $purchase_price;
        $purchase_line->purchase_price_inc_tax = $purchase_price_inc_tax;

        $this->productUtil->updateProductQuantity($location_id, $product->id, $variation->id, $qty, 0, null, false);

        $transaction = Transaction::create([
            'type' => 'opening_stock',
            'opening_stock_product_id' => $product->id,
            'status' => 'received',
            'business_id' => $business_id,
            'transaction_date' => \Carbon\Carbon::now()->toDateTimeString(),
            'additional_notes' => 'Ajuste masivo por Excel' . ($note ? ' — ' . $note : ''),
            'total_before_tax' => $purchase_price_inc_tax,
            'location_id' => $location_id,
            'final_total' => $purchase_price_inc_tax * $qty,
            'payment_status' => 'paid',
            'created_by' => auth()->id(),
        ]);

        $transaction->purchase_lines()->saveMany([$purchase_line]);
        $this->productUtil->adjustStockOverSelling($transaction);
    }
}
