<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class WeeklyVendorReportExport implements WithMultipleSheets
{
    use Exportable;

    private $data;
    private $start;
    private $end;

    public function __construct($data, $start, $end)
    {
        $this->data = $data;
        $this->start = $start;
        $this->end = $end;
    }

    public function sheets(): array
    {
        $sheets = [];
        // Combined totals sheet first
        $sheets[] = new VendorReportCombinedSheet($this->data, $this->start, $this->end);

        // One sheet per vendor
        foreach ($this->data['vendors'] as $vendor_data) {
            $sheets[] = new VendorReportSheet($vendor_data, $this->data['brands'], $this->data['days'], $this->start, $this->end);
        }

        return $sheets;
    }
}

class VendorReportCombinedSheet implements FromArray, WithTitle, WithEvents
{
    private $data;
    private $start;
    private $end;
    private $total_cols;

    public function __construct($data, $start, $end)
    {
        $this->data = $data;
        $this->start = $start;
        $this->end = $end;
    }

    public function title(): string
    {
        return 'TOTAL';
    }

    public function array(): array
    {
        $day_short_map = [0 => 'DOMINGO', 1 => 'LUNES', 2 => 'MARTES', 3 => 'MIÉRCOLES', 4 => 'JUEVES', 5 => 'VIERNES', 6 => 'SÁBADO'];
        $brands = $this->data['brands'];
        $days = $this->data['days'];
        $combined = $this->data['combined'];
        $combined_totals = $this->data['combined_totals'];

        $rows = [];
        $rows[] = ['TOTAL DE TODOS LOS VENDEDORES — ' . $this->start->format('d/m/Y') . ' a ' . $this->end->format('d/m/Y')];
        $rows[] = [];

        $header = ['DÍA'];
        foreach ($brands as $b) {
            $header[] = strtoupper($b->name);
        }
        $header[] = 'N.DIA';
        $header[] = 'TOTAL';
        $rows[] = $header;

        foreach ($days as $d) {
            $key = $d->toDateString();
            $row = [($day_short_map[$d->dayOfWeek] ?? '') . ' ' . $d->format('d/m')];
            foreach ($brands as $b) {
                $row[] = (int) ($combined[$key][$b->id] ?? 0);
            }
            $row[] = (int) ($combined[$key]['n_dia'] ?? 0);
            $row[] = (int) ($combined[$key]['total'] ?? 0);
            $rows[] = $row;
        }

        // TOTALES row
        $totals_row = ['TOTALES'];
        foreach ($brands as $b) {
            $totals_row[] = (int) ($combined_totals['brands'][$b->id] ?? 0);
        }
        $totals_row[] = (int) $combined_totals['n_dia'];
        $totals_row[] = (int) $combined_totals['total'];
        $rows[] = $totals_row;

        $this->total_cols = count($header);

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $totalCols = $this->total_cols ?? 5;
                $lastCol = chr(64 + $totalCols);

                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                ]);

                // Header row yellow
                $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC107']],
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Last row (TOTALES) bold
                $lastRow = 3 + 7 + 1;
                $sheet->getStyle("A{$lastRow}:{$lastCol}{$lastRow}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BBDEFB']],
                    'font' => ['bold' => true],
                ]);

                foreach (range('A', $lastCol) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                $sheet->getStyle("A3:{$lastCol}{$lastRow}")
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }
}

class VendorReportSheet implements FromArray, WithTitle, WithEvents
{
    private $vendor_data;
    private $brands;
    private $days;
    private $start;
    private $end;
    private $total_cols;

    public function __construct($vendor_data, $brands, $days, $start, $end)
    {
        $this->vendor_data = $vendor_data;
        $this->brands = $brands;
        $this->days = $days;
        $this->start = $start;
        $this->end = $end;
    }

    public function title(): string
    {
        $u = $this->vendor_data['user'];

        return substr(strtoupper(trim($u->first_name . ' ' . $u->last_name)), 0, 31);
    }

    public function array(): array
    {
        $day_short_map = [0 => 'DOMINGO', 1 => 'LUNES', 2 => 'MARTES', 3 => 'MIÉRCOLES', 4 => 'JUEVES', 5 => 'VIERNES', 6 => 'SÁBADO'];
        $u = $this->vendor_data['user'];

        $rows = [];
        $rows[] = [strtoupper(trim($u->first_name . ' ' . $u->last_name)) . ' — ' . $this->start->format('d/m/Y') . ' a ' . $this->end->format('d/m/Y')];
        $rows[] = [];

        $header = ['DÍA'];
        foreach ($this->brands as $b) {
            $header[] = strtoupper($b->name);
        }
        $header[] = 'N.DIA';
        $header[] = 'TOTAL';
        $rows[] = $header;

        foreach ($this->days as $idx => $d) {
            $row_data = $this->vendor_data['rows'][$idx];
            $row = [($day_short_map[$d->dayOfWeek] ?? '') . ' ' . $d->format('d/m')];
            foreach ($this->brands as $b) {
                $row[] = (int) ($row_data['brands'][$b->id] ?? 0);
            }
            $row[] = (int) $row_data['n_dia'];
            $row[] = (int) $row_data['total'];
            $rows[] = $row;
        }

        // TOTALES row
        $totals_row = ['TOTALES'];
        foreach ($this->brands as $b) {
            $totals_row[] = (int) ($this->vendor_data['totals'][$b->id] ?? 0);
        }
        $totals_row[] = (int) $this->vendor_data['totals']['n_dia'];
        $totals_row[] = (int) $this->vendor_data['totals']['total'];
        $rows[] = $totals_row;

        // META row
        $commissions = $this->vendor_data['commissions'] ?? [];
        $meta_row = ['META (unidades)'];
        foreach ($this->brands as $b) {
            $c = $commissions[$b->id] ?? null;
            $meta_row[] = $c ? (int) $c['meta'] : 0;
        }
        $meta_row[] = '';
        $meta_row[] = '';
        $rows[] = $meta_row;

        // COMISIÓN row
        $commission_total = (float) ($this->vendor_data['totals']['commission_total'] ?? 0);
        $comm_row = ['COMISIÓN ($)'];
        foreach ($this->brands as $b) {
            $c = $commissions[$b->id] ?? null;
            $comm_row[] = $c ? round((float) $c['commission'], 2) : 0;
        }
        $comm_row[] = '';
        $comm_row[] = round($commission_total, 2);
        $rows[] = $comm_row;

        $this->total_cols = count($header);

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $totalCols = $this->total_cols ?? 5;
                $lastCol = chr(64 + $totalCols);

                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                ]);

                $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EC407A']],
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // TOTALES row (azul claro)
                $totalsRow = 3 + 7 + 1;
                $sheet->getStyle("A{$totalsRow}:{$lastCol}{$totalsRow}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BBDEFB']],
                    'font' => ['bold' => true],
                ]);
                // META row (amarillo)
                $metaRow = $totalsRow + 1;
                $sheet->getStyle("A{$metaRow}:{$lastCol}{$metaRow}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF9C4']],
                ]);
                // COMISIÓN row (verde)
                $commRow = $totalsRow + 2;
                $sheet->getStyle("A{$commRow}:{$lastCol}{$commRow}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C8E6C9']],
                    'font' => ['bold' => true],
                ]);
                $lastRow = $commRow;

                foreach (range('A', $lastCol) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                $sheet->getStyle("A3:{$lastCol}{$lastRow}")
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }
}
