<?php

namespace App\Exports;

use App\BusinessLocation;
use App\DailyCut;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class DailyCutsExport implements WithMultipleSheets
{
    use Exportable;

    private $business_id;
    private $start_date;
    private $end_date;
    private $location_id;
    private $cuts;
    private $locations;

    public function __construct($business_id, $start_date, $end_date, $location_id = null)
    {
        $this->business_id = $business_id;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->location_id = $location_id;

        $query = DailyCut::where('business_id', $business_id)
            ->whereBetween('cut_date', [$start_date, $end_date])
            ->with('location')
            ->orderBy('cut_date');

        if (!empty($location_id)) {
            $query->where('location_id', $location_id);
        }

        $this->cuts = $query->get();

        // Resolve locations actually present in the data set
        $loc_ids = $this->cuts->pluck('location_id')->unique()->toArray();
        $this->locations = BusinessLocation::whereIn('id', $loc_ids)
            ->orderBy('name')
            ->get()
            ->keyBy('id');
    }

    public function sheets(): array
    {
        return [
            new DailyCutsSummarySheet($this->cuts, $this->locations, $this->start_date, $this->end_date),
            new DailyCutsByLocationSheet($this->cuts, $this->locations),
            new DailyCutsCategoriesSheet($this->cuts, $this->locations),
            new DailyCutsTerminalsSheet($this->cuts, $this->locations),
            new DailyCutsMxnDenominationsSheet($this->cuts, $this->locations),
            new DailyCutsUsdDenominationsSheet($this->cuts, $this->locations),
        ];
    }
}

/**
 * Sheet 1 — Resumen general por día y sucursal
 */
class DailyCutsSummarySheet implements FromArray, WithTitle
{
    private $cuts;
    private $locations;
    private $start_date;
    private $end_date;

    public function __construct($cuts, $locations, $start_date, $end_date)
    {
        $this->cuts = $cuts;
        $this->locations = $locations;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function array(): array
    {
        $rows = [];
        $rows[] = ['CORTE DIARIO — RESUMEN'];
        $rows[] = ['Periodo:', $this->start_date . ' a ' . $this->end_date];
        $rows[] = [];
        $rows[] = [
            'Fecha', 'Sucursal', '# Trans.',
            'Total Ventas', 'Efectivo', 'Tarjeta', 'Transferencia', 'Cheque', 'Otros',
            'Gastos', 'Total Dinero',
        ];

        $totals = [
            'transactions' => 0, 'sales' => 0, 'cash' => 0, 'card' => 0,
            'transfer' => 0, 'cheque' => 0, 'other' => 0, 'expenses' => 0, 'dinero' => 0,
        ];

        foreach ($this->cuts as $cut) {
            $dinero = $cut->total_cash + $cut->total_card + $cut->total_transfer + $cut->total_cheque + $cut->total_other;
            $rows[] = [
                $cut->cut_date->format('d/m/Y'),
                $this->locations->get($cut->location_id)->name ?? '—',
                $cut->total_transactions,
                $cut->total_sales,
                $cut->total_cash,
                $cut->total_card,
                $cut->total_transfer,
                $cut->total_cheque,
                $cut->total_other,
                $cut->total_expenses,
                $dinero,
            ];

            $totals['transactions'] += $cut->total_transactions;
            $totals['sales'] += $cut->total_sales;
            $totals['cash'] += $cut->total_cash;
            $totals['card'] += $cut->total_card;
            $totals['transfer'] += $cut->total_transfer;
            $totals['cheque'] += $cut->total_cheque;
            $totals['other'] += $cut->total_other;
            $totals['expenses'] += $cut->total_expenses;
            $totals['dinero'] += $dinero;
        }

        $rows[] = [];
        $rows[] = [
            'TOTALES', '', $totals['transactions'],
            $totals['sales'], $totals['cash'], $totals['card'],
            $totals['transfer'], $totals['cheque'], $totals['other'],
            $totals['expenses'], $totals['dinero'],
        ];

        return $rows;
    }
}

/**
 * Sheet 2 — Totales por sucursal en el periodo
 */
class DailyCutsByLocationSheet implements FromArray, WithTitle
{
    private $cuts;
    private $locations;

    public function __construct($cuts, $locations)
    {
        $this->cuts = $cuts;
        $this->locations = $locations;
    }

    public function title(): string
    {
        return 'Por Sucursal';
    }

    public function array(): array
    {
        $rows = [];
        $rows[] = ['POR SUCURSAL'];
        $rows[] = [];
        $rows[] = [
            'Sucursal', '# Trans.', 'Total Ventas', 'Efectivo', 'Tarjeta',
            'Transferencia', 'Cheque', 'Otros', 'Gastos', 'Total Dinero',
        ];

        $by_loc = $this->cuts->groupBy('location_id');
        $totals = [
            'transactions' => 0, 'sales' => 0, 'cash' => 0, 'card' => 0,
            'transfer' => 0, 'cheque' => 0, 'other' => 0, 'expenses' => 0, 'dinero' => 0,
        ];

        foreach ($this->locations as $loc_id => $loc) {
            $loc_cuts = $by_loc->get($loc_id, collect());
            $sales = $loc_cuts->sum('total_sales');
            $cash = $loc_cuts->sum('total_cash');
            $card = $loc_cuts->sum('total_card');
            $transfer = $loc_cuts->sum('total_transfer');
            $cheque = $loc_cuts->sum('total_cheque');
            $other = $loc_cuts->sum('total_other');
            $expenses = $loc_cuts->sum('total_expenses');
            $tx = $loc_cuts->sum('total_transactions');
            $dinero = $cash + $card + $transfer + $cheque + $other;

            $rows[] = [
                $loc->name, $tx, $sales, $cash, $card, $transfer, $cheque, $other, $expenses, $dinero,
            ];

            $totals['transactions'] += $tx;
            $totals['sales'] += $sales;
            $totals['cash'] += $cash;
            $totals['card'] += $card;
            $totals['transfer'] += $transfer;
            $totals['cheque'] += $cheque;
            $totals['other'] += $other;
            $totals['expenses'] += $expenses;
            $totals['dinero'] += $dinero;
        }

        $rows[] = [];
        $rows[] = [
            'TOTALES',
            $totals['transactions'], $totals['sales'], $totals['cash'], $totals['card'],
            $totals['transfer'], $totals['cheque'], $totals['other'],
            $totals['expenses'], $totals['dinero'],
        ];

        return $rows;
    }
}

/**
 * Sheet 3 — Ventas por categoría (marca) por día y sucursal
 */
class DailyCutsCategoriesSheet implements FromArray, WithTitle
{
    private $cuts;
    private $locations;

    public function __construct($cuts, $locations)
    {
        $this->cuts = $cuts;
        $this->locations = $locations;
    }

    public function title(): string
    {
        return 'Por Categoría';
    }

    public function array(): array
    {
        $rows = [];
        $rows[] = ['VENTAS POR CATEGORÍA (MARCA)'];
        $rows[] = [];
        $rows[] = ['Fecha', 'Sucursal', 'Marca', 'Cantidad', 'Subtotal'];

        // Aggregate totals per brand
        $brand_totals = [];

        foreach ($this->cuts as $cut) {
            $brands = $cut->summary['sales_by_brand'] ?? [];
            foreach ($brands as $b) {
                $rows[] = [
                    $cut->cut_date->format('d/m/Y'),
                    $this->locations->get($cut->location_id)->name ?? '—',
                    $b['brand'] ?? '—',
                    $b['quantity'] ?? 0,
                    $b['subtotal'] ?? 0,
                ];

                $brand = $b['brand'] ?? '—';
                if (!isset($brand_totals[$brand])) {
                    $brand_totals[$brand] = ['quantity' => 0, 'subtotal' => 0];
                }
                $brand_totals[$brand]['quantity'] += $b['quantity'] ?? 0;
                $brand_totals[$brand]['subtotal'] += $b['subtotal'] ?? 0;
            }
        }

        $rows[] = [];
        $rows[] = ['TOTALES POR MARCA EN EL PERIODO'];
        $rows[] = ['Marca', 'Cantidad', 'Subtotal'];
        foreach ($brand_totals as $brand => $tot) {
            $rows[] = [$brand, $tot['quantity'], $tot['subtotal']];
        }

        return $rows;
    }
}

/**
 * Sheet 4 — Pagos por terminal bancaria
 */
class DailyCutsTerminalsSheet implements FromArray, WithTitle
{
    private $cuts;
    private $locations;

    public function __construct($cuts, $locations)
    {
        $this->cuts = $cuts;
        $this->locations = $locations;
    }

    public function title(): string
    {
        return 'Terminales';
    }

    public function array(): array
    {
        $rows = [];
        $rows[] = ['PAGOS POR TERMINAL BANCARIA'];
        $rows[] = [];
        $rows[] = ['Fecha', 'Sucursal', 'Terminal', 'Banco', 'Monto'];

        $terminal_totals = [];

        foreach ($this->cuts as $cut) {
            $terminals = $cut->summary['card_by_terminal'] ?? [];
            foreach ($terminals as $t) {
                $rows[] = [
                    $cut->cut_date->format('d/m/Y'),
                    $this->locations->get($cut->location_id)->name ?? '—',
                    $t['name'] ?? '—',
                    $t['bank'] ?? '',
                    $t['total'] ?? 0,
                ];

                $key = $t['name'] ?? '—';
                if (!isset($terminal_totals[$key])) {
                    $terminal_totals[$key] = ['bank' => $t['bank'] ?? '', 'total' => 0];
                }
                $terminal_totals[$key]['total'] += $t['total'] ?? 0;
            }
        }

        $rows[] = [];
        $rows[] = ['TOTALES POR TERMINAL EN EL PERIODO'];
        $rows[] = ['Terminal', 'Banco', 'Total'];
        foreach ($terminal_totals as $name => $tot) {
            $rows[] = [$name, $tot['bank'], $tot['total']];
        }

        return $rows;
    }
}

/**
 * Sheet 5 — Denominaciones MXN
 */
class DailyCutsMxnDenominationsSheet implements FromArray, WithTitle
{
    private $cuts;
    private $locations;
    private $faces = [1000, 500, 200, 100, 50, 20];

    public function __construct($cuts, $locations)
    {
        $this->cuts = $cuts;
        $this->locations = $locations;
    }

    public function title(): string
    {
        return 'Denominaciones MXN';
    }

    public function array(): array
    {
        $rows = [];
        $rows[] = ['DENOMINACIONES EN EFECTIVO — PESOS (MXN)'];
        $rows[] = [];
        $header = ['Fecha', 'Sucursal'];
        foreach ($this->faces as $f) {
            $header[] = '$' . $f;
        }
        $header[] = 'Monedas';
        $header[] = 'Subtotal MXN';
        $rows[] = $header;

        $totals = array_fill_keys($this->faces, 0);
        $totals_coins = 0;
        $totals_subtotal = 0;

        foreach ($this->cuts as $cut) {
            $mxn = $cut->summary['mxn'] ?? null;
            $denoms = $mxn['denominations'] ?? [];
            $coins = $mxn['coins'] ?? 0;
            $subtotal = $mxn['subtotal'] ?? 0;

            $row = [
                $cut->cut_date->format('d/m/Y'),
                $this->locations->get($cut->location_id)->name ?? '—',
            ];
            foreach ($this->faces as $f) {
                $count = (int) ($denoms[$f] ?? 0);
                $row[] = $count;
                $totals[$f] += $count;
            }
            $row[] = $coins;
            $row[] = $subtotal;
            $totals_coins += $coins;
            $totals_subtotal += $subtotal;
            $rows[] = $row;
        }

        $rows[] = [];
        $totals_row = ['TOTALES', ''];
        foreach ($this->faces as $f) {
            $totals_row[] = $totals[$f];
        }
        $totals_row[] = $totals_coins;
        $totals_row[] = $totals_subtotal;
        $rows[] = $totals_row;

        return $rows;
    }
}

/**
 * Sheet 6 — Denominaciones USD
 */
class DailyCutsUsdDenominationsSheet implements FromArray, WithTitle
{
    private $cuts;
    private $locations;
    private $faces = [1, 5, 10, 20, 50, 100];

    public function __construct($cuts, $locations)
    {
        $this->cuts = $cuts;
        $this->locations = $locations;
    }

    public function title(): string
    {
        return 'Denominaciones USD';
    }

    public function array(): array
    {
        $rows = [];
        $rows[] = ['DENOMINACIONES EN EFECTIVO — DÓLARES (USD)'];
        $rows[] = [];
        $header = ['Fecha', 'Sucursal'];
        foreach ($this->faces as $f) {
            $header[] = '$' . $f;
        }
        $header[] = 'Monedas';
        $header[] = 'Subtotal USD';
        $header[] = 'Equivalente MXN';
        $rows[] = $header;

        $totals = array_fill_keys($this->faces, 0);
        $totals_coins = 0;
        $totals_subtotal = 0;
        $totals_in_mxn = 0;

        foreach ($this->cuts as $cut) {
            $usd = $cut->summary['usd'] ?? null;
            $denoms = $usd['denominations'] ?? [];
            $coins = $usd['coins'] ?? 0;
            $subtotal = $usd['subtotal'] ?? 0;
            $in_mxn = $usd['in_mxn'] ?? 0;

            $row = [
                $cut->cut_date->format('d/m/Y'),
                $this->locations->get($cut->location_id)->name ?? '—',
            ];
            foreach ($this->faces as $f) {
                $count = (int) ($denoms[$f] ?? 0);
                $row[] = $count;
                $totals[$f] += $count;
            }
            $row[] = $coins;
            $row[] = $subtotal;
            $row[] = $in_mxn;
            $totals_coins += $coins;
            $totals_subtotal += $subtotal;
            $totals_in_mxn += $in_mxn;
            $rows[] = $row;
        }

        $rows[] = [];
        $totals_row = ['TOTALES', ''];
        foreach ($this->faces as $f) {
            $totals_row[] = $totals[$f];
        }
        $totals_row[] = $totals_coins;
        $totals_row[] = $totals_subtotal;
        $totals_row[] = $totals_in_mxn;
        $rows[] = $totals_row;

        return $rows;
    }
}
