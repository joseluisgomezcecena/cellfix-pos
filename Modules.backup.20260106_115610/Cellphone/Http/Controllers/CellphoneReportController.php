<?php

namespace Modules\Cellphone\Http\Controllers;

use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Cellphone\Entities\Cellphone;
use Excel;

class CellphoneReportController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $commonUtil;

    /**
     * Constructor
     *
     * @param Util $commonUtil
     * @return void
     */
    public function __construct(Util $commonUtil)
    {
        $this->commonUtil = $commonUtil;
    }

    /**
     * Display cellphone reports page
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('cellphone.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        return view('cellphone::reports.index');
    }

    /**
     * Export cellphone inventory
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        if (!auth()->user()->can('cellphone.export')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        try {
            $query = Cellphone::cellphones()
                ->where('business_id', $business_id)
                ->with(['brand', 'warranty', 'unit', 'category']);

            // Apply filters if provided
            $filters = $request->only(['marca', 'modelo', 'imei', 'estado', 'warranty_id', 'ubicacion']);
            if (!empty($filters)) {
                $query->searchCellphones($filters);
            }

            $cellphones = $query->get();

            $export_data = [];

            // Header row
            $export_data[] = [
                __('cellphone::lang.imei'),
                __('cellphone::lang.marca'),
                __('cellphone::lang.modelo'),
                __('product.product_name'),
                __('cellphone::lang.estado'),
                __('cellphone::lang.warranty'),
                __('cellphone::lang.ubicacion'),
                __('product.brand'),
                __('product.category'),
                __('product.unit'),
                __('cellphone::lang.observaciones'),
            ];

            // Data rows
            foreach ($cellphones as $cellphone) {
                $estado_options = config('cellphone.estado_options');
                $estado_label = $estado_options[$cellphone->estado] ?? $cellphone->estado;

                $export_data[] = [
                    $cellphone->sku, // IMEI
                    $cellphone->marca,
                    $cellphone->modelo,
                    $cellphone->name,
                    $estado_label,
                    $cellphone->warranty ? $cellphone->warranty->display_name : '-',
                    $cellphone->ubicacion,
                    $cellphone->brand ? $cellphone->brand->name : '-',
                    $cellphone->category ? $cellphone->category->name : '-',
                    $cellphone->unit ? $cellphone->unit->actual_name : '-',
                    $cellphone->observaciones,
                ];
            }

            $file_name = 'cellphones_inventory_' . date('Y-m-d_H-i-s') . '.xlsx';

            return Excel::download(function($excel) use ($export_data) {
                $excel->sheet('Cellphones', function($sheet) use ($export_data) {
                    $sheet->fromArray($export_data, null, 'A1', false, false);

                    // Style header row
                    $sheet->row(1, function($row) {
                        $row->setBackground('#4CAF50');
                        $row->setFontColor('#FFFFFF');
                        $row->setFontWeight('bold');
                    });

                    // Auto-size columns
                    foreach(range('A', 'K') as $col) {
                        $sheet->getColumnDimension($col)->setAutoSize(true);
                    }
                });
            }, $file_name);

        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];

            return redirect()->back()->with('status', $output);
        }
    }

    /**
     * Get cellphone statistics for reports
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getStatistics(Request $request)
    {
        if (!auth()->user()->can('cellphone.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $stats = Cellphone::getStatistics($business_id);

        return response()->json($stats);
    }
}
