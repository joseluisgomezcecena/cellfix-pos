<?php

namespace App\Exports;

use App\BusinessLocation;
use App\DailyCut;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Weekly cut export — one tab per location, with 7 days side-by-side
 * mirroring the team's "CORTE POR DIA" Excel layout.
 */
class WeeklyCutByLocationExport implements WithMultipleSheets
{
    use Exportable;

    private $business_id;
    private $start_date;
    private $location_id;

    public function __construct($business_id, $start_date, $location_id = null)
    {
        $this->business_id = $business_id;
        $this->start_date = $start_date;
        $this->location_id = $location_id;
    }

    public function sheets(): array
    {
        $start = Carbon::parse($this->start_date);
        $end = $start->copy()->addDays(6);

        $query = DailyCut::where('business_id', $this->business_id)
            ->whereBetween('cut_date', [$start->toDateString(), $end->toDateString()])
            ->with('location');

        if (!empty($this->location_id)) {
            $query->where('location_id', $this->location_id);
        }

        $cuts = $query->get();

        // Group cuts by location
        $by_location = $cuts->groupBy('location_id');

        // Show every active location, even if it has no data, when no specific filter
        $loc_query = BusinessLocation::where('business_id', $this->business_id)
            ->where('is_active', 1)
            ->orderBy('name');
        if (!empty($this->location_id)) {
            $loc_query->where('id', $this->location_id);
        }
        $locations = $loc_query->get();

        $sheets = [];

        // First sheet — consolidated total across all locations (only if no location filter)
        if (empty($this->location_id) && $locations->count() > 1) {
            $sheets[] = new WeeklyCutSheet('TOTAL CONSOLIDADO', $cuts, $start);
        }

        foreach ($locations as $loc) {
            $loc_cuts = $by_location->get($loc->id, collect());
            $sheets[] = new WeeklyCutSheet($loc->name, $loc_cuts, $start);
        }

        return $sheets;
    }
}

class WeeklyCutSheet implements FromArray, WithTitle, WithEvents
{
    private $title;
    private $cuts;
    private $start;

    public function __construct($title, $cuts, $start)
    {
        // Sheet title is limited to 31 chars in Excel
        $this->title = substr($title, 0, 31);
        $this->cuts = $cuts;
        $this->start = $start;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function array(): array
    {
        // Group cuts by date string (cut_date is Carbon, can't ->where('cut_date', '2026-05-08'))
        $cuts_by_date = $this->cuts->groupBy(function ($c) {
            return $c->cut_date->toDateString();
        });

        // Build day buckets
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $this->start->copy()->addDays($i);
            $key = $date->toDateString();
            $days[$key] = [
                'date' => $date,
                'day_name' => $this->dayName($date->dayOfWeek),
                'cuts' => $cuts_by_date->get($key, collect()),
            ];
        }

        // Discover all brands and terminals across the week
        $brands = [];
        $terminals = [];
        foreach ($this->cuts as $cut) {
            foreach ($cut->summary['sales_by_brand'] ?? [] as $b) {
                $brand = strtoupper($b['brand'] ?? 'SIN MARCA');
                if (!in_array($brand, $brands)) $brands[] = $brand;
            }
            foreach ($cut->summary['card_by_terminal'] ?? [] as $t) {
                $name = strtoupper($t['name'] ?? '—');
                if (!in_array($name, $terminals)) $terminals[] = $name;
            }
        }
        sort($brands);
        sort($terminals);

        // Aggregate per day
        $day_data = [];
        $exchange_rate = '';
        foreach ($days as $key => $d) {
            $brand_totals = array_fill_keys($brands, 0);
            $terminal_totals = array_fill_keys($terminals, 0);
            $cash = 0; $transfer = 0; $cheque = 0; $card = 0; $expenses = 0; $sales = 0;

            foreach ($d['cuts'] as $cut) {
                foreach ($cut->summary['sales_by_brand'] ?? [] as $b) {
                    $brand = strtoupper($b['brand'] ?? 'SIN MARCA');
                    $brand_totals[$brand] = ($brand_totals[$brand] ?? 0) + ($b['subtotal'] ?? 0);
                }
                foreach ($cut->summary['card_by_terminal'] ?? [] as $t) {
                    $name = strtoupper($t['name'] ?? '—');
                    $terminal_totals[$name] = ($terminal_totals[$name] ?? 0) + ($t['total'] ?? 0);
                }
                $cash += $cut->total_cash;
                $card += $cut->total_card;
                $transfer += $cut->total_transfer;
                $cheque += $cut->total_cheque;
                $expenses += $cut->total_expenses;
                $sales += $cut->total_sales;

                // Try to capture an exchange rate if any cut has USD
                if (!empty($cut->summary['usd']['in_mxn']) && empty($exchange_rate)) {
                    // Best-effort: derive rate from raw data isn't simple; use business setting
                    $exchange_rate = optional(\App\Business::find($cut->business_id))->cash_exchange_rate;
                }
            }

            // Card without terminal (legacy data)
            $term_sum = array_sum($terminal_totals);
            $card_no_terminal = max(0, $card - $term_sum);

            $total_dinero = $cash + $card + $transfer + $cheque;
            $diff = $total_dinero - $sales;

            $day_data[$key] = [
                'date' => $d['date'],
                'day_name' => $d['day_name'],
                'brands' => $brand_totals,
                'sales' => $sales,
                'cash' => $cash,
                'card' => $card,
                'terminals' => $terminal_totals,
                'card_no_terminal' => $card_no_terminal,
                'transfer' => $transfer,
                'cheque' => $cheque,
                'total_dinero' => $total_dinero,
                'diff' => $diff,
                'expenses' => $expenses,
            ];
        }

        // Build the array — concept column + 7 day columns + TOTAL column
        $rows = [];
        $day_keys = array_keys($day_data);

        // Row 1: TC
        $tc_row = ['TC'];
        foreach ($day_keys as $k) {
            $tc_row[] = $exchange_rate ?: '';
        }
        $tc_row[] = ''; // total column
        $rows[] = $tc_row;

        // Row 2: Day names header
        $header_row = [''];
        foreach ($day_keys as $k) {
            $header_row[] = $day_data[$k]['day_name'] . ' ' . $day_data[$k]['date']->format('d/m');
        }
        $header_row[] = 'TOTAL';
        $rows[] = $header_row;

        // Brand rows (REPARACIONES, SERVICIOS, etc.)
        foreach ($brands as $brand) {
            $row = [$brand];
            $week_total = 0;
            foreach ($day_keys as $k) {
                $val = $day_data[$k]['brands'][$brand] ?? 0;
                $row[] = $val;
                $week_total += $val;
            }
            $row[] = $week_total;
            $rows[] = $row;
        }

        // TOTAL ventas row
        $total_row = ['TOTAL'];
        $week_sales = 0;
        foreach ($day_keys as $k) {
            $row[] = $day_data[$k]['sales'];
            $total_row[] = $day_data[$k]['sales'];
            $week_sales += $day_data[$k]['sales'];
        }
        $total_row[] = $week_sales;
        $rows[] = $total_row;

        // EFECTIVO
        $cash_row = ['EFECTIVO'];
        $week_cash = 0;
        foreach ($day_keys as $k) {
            $cash_row[] = $day_data[$k]['cash'];
            $week_cash += $day_data[$k]['cash'];
        }
        $cash_row[] = $week_cash;
        $rows[] = $cash_row;

        // Terminals
        foreach ($terminals as $term) {
            $row = ['TERMINAL ' . $term];
            $week_term = 0;
            foreach ($day_keys as $k) {
                $val = $day_data[$k]['terminals'][$term] ?? 0;
                $row[] = $val;
                $week_term += $val;
            }
            $row[] = $week_term;
            $rows[] = $row;
        }

        // If there's any card payment without a terminal assigned, show it as a row
        $has_no_term = false;
        foreach ($day_keys as $k) {
            if ($day_data[$k]['card_no_terminal'] > 0.01) { $has_no_term = true; break; }
        }
        if ($has_no_term) {
            $row = ['TARJETA SIN TERMINAL'];
            $week_no_term = 0;
            foreach ($day_keys as $k) {
                $row[] = $day_data[$k]['card_no_terminal'];
                $week_no_term += $day_data[$k]['card_no_terminal'];
            }
            $row[] = $week_no_term;
            $rows[] = $row;
        }

        // TRANSFERENCIAS
        $row = ['TRANSFERENCIAS'];
        $week_transfer = 0;
        foreach ($day_keys as $k) {
            $row[] = $day_data[$k]['transfer'];
            $week_transfer += $day_data[$k]['transfer'];
        }
        $row[] = $week_transfer;
        $rows[] = $row;

        // CHEQUE (only if any)
        $has_cheque = false;
        foreach ($day_keys as $k) {
            if ($day_data[$k]['cheque'] > 0.01) { $has_cheque = true; break; }
        }
        if ($has_cheque) {
            $row = ['CHEQUE'];
            $week_cheque = 0;
            foreach ($day_keys as $k) {
                $row[] = $day_data[$k]['cheque'];
                $week_cheque += $day_data[$k]['cheque'];
            }
            $row[] = $week_cheque;
            $rows[] = $row;
        }

        // TOTAL DINERO
        $row = ['TOTAL'];
        $week_dinero = 0;
        foreach ($day_keys as $k) {
            $row[] = $day_data[$k]['total_dinero'];
            $week_dinero += $day_data[$k]['total_dinero'];
        }
        $row[] = $week_dinero;
        $rows[] = $row;

        // DIFERENCIA
        $row = ['DIFERENCIA'];
        $week_diff = 0;
        foreach ($day_keys as $k) {
            $row[] = $day_data[$k]['diff'];
            $week_diff += $day_data[$k]['diff'];
        }
        $row[] = $week_diff;
        $rows[] = $row;

        // Empty row before gastos
        $rows[] = [];

        // GASTOS
        $row = ['GASTOS'];
        $week_expenses = 0;
        foreach ($day_keys as $k) {
            $row[] = $day_data[$k]['expenses'];
            $week_expenses += $day_data[$k]['expenses'];
        }
        $row[] = $week_expenses;
        $rows[] = $row;

        // Save these so the events handler can find specific rows for styling
        $this->_brands_count = count($brands);
        $this->_terminals_count = count($terminals);
        $this->_has_no_term = $has_no_term;
        $this->_has_cheque = $has_cheque;
        $this->_total_cols = count($day_keys) + 2; // concept + 7 days + total

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $totalCols = $this->_total_cols ?? 9;
                $lastCol = chr(64 + $totalCols); // A=65, B=66...

                // Day-name header row (row 2) — yellow highlight
                $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFEB3B'],
                    ],
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Find row indexes for highlighted totals
                // Layout: row1=TC, row2=days header, then brands_count rows, then TOTAL ventas
                $brands_count = $this->_brands_count ?? 0;
                $total_ventas_row = 3 + $brands_count;

                // Highlight TOTAL ventas
                $sheet->getStyle("A{$total_ventas_row}:{$lastCol}{$total_ventas_row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'C8E6C9'],
                    ],
                    'font' => ['bold' => true],
                ]);

                // Find TOTAL DINERO row (after EFECTIVO + terminals + maybe noterm + transfer + maybe cheque)
                $offset_after_total_ventas = 1; // EFECTIVO
                $offset_after_total_ventas += ($this->_terminals_count ?? 0);
                if ($this->_has_no_term) $offset_after_total_ventas++;
                $offset_after_total_ventas++; // TRANSFERENCIAS
                if ($this->_has_cheque) $offset_after_total_ventas++;
                $total_dinero_row = $total_ventas_row + $offset_after_total_ventas + 1;

                // Highlight TOTAL DINERO
                $sheet->getStyle("A{$total_dinero_row}:{$lastCol}{$total_dinero_row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFE0B2'],
                    ],
                    'font' => ['bold' => true],
                ]);

                // DIFERENCIA row (right below TOTAL DINERO) — light blue
                $diff_row = $total_dinero_row + 1;
                $sheet->getStyle("A{$diff_row}:{$lastCol}{$diff_row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'BBDEFB'],
                    ],
                ]);

                // Format numeric columns
                $sheet->getStyle("B3:{$lastCol}{$diff_row}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');

                // Auto-size columns
                foreach (range('A', $lastCol) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Borders for the main table
                $sheet->getStyle("A1:{$lastCol}{$diff_row}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }

    private function dayName($dow)
    {
        $names = [0 => 'DOMINGO', 1 => 'LUNES', 2 => 'MARTES', 3 => 'MIÉRCOLES', 4 => 'JUEVES', 5 => 'VIERNES', 6 => 'SÁBADO'];

        return $names[$dow] ?? '';
    }

    // Properties used between array() and registerEvents()
    public $_brands_count = 0;
    public $_terminals_count = 0;
    public $_has_no_term = false;
    public $_has_cheque = false;
    public $_total_cols = 9;
}
