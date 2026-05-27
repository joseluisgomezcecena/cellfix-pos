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

/**
 * One tab per technician with their week's repairs, day subtotals and weekly total.
 */
class TechniciansReportExport implements WithMultipleSheets
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

        // Summary sheet at the front
        $sheets[] = new TechniciansSummarySheet($this->data, $this->start, $this->end);

        // One sheet per technician (even those with no repairs)
        foreach ($this->data as $tech_data) {
            $sheets[] = new TechnicianSheet($tech_data, $this->start, $this->end);
        }

        return $sheets;
    }
}

class TechniciansSummarySheet implements FromArray, WithTitle, WithEvents
{
    private $data;
    private $start;
    private $end;

    public function __construct($data, $start, $end)
    {
        $this->data = $data;
        $this->start = $start;
        $this->end = $end;
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function array(): array
    {
        $rows = [];
        $rows[] = ['REPORTE DE TÉCNICOS — RESUMEN'];
        $rows[] = ['Periodo:', $this->start->format('d/m/Y') . ' a ' . $this->end->format('d/m/Y')];
        $rows[] = [];
        $rows[] = ['Técnico', '# Reparaciones', 'Total facturado', 'Total a pagar'];

        $total_count = 0;
        $total_billed = 0;
        $total_commission = 0;

        foreach ($this->data as $td) {
            $tech = $td['technician'];
            $rows[] = [
                $tech->name,
                $td['week_count'],
                $td['week_total'],
                $td['commission_due'],
            ];
            $total_count += $td['week_count'];
            $total_billed += $td['week_total'];
            $total_commission += $td['commission_due'];
        }

        $rows[] = [];
        $rows[] = ['TOTALES', $total_count, $total_billed, $total_commission];

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('A4:D4')->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2196F3']],
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                ]);
                $sheet->getStyle('C5:C100')->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('D5:D100')->getNumberFormat()->setFormatCode('#,##0.00');
                foreach (range('A', 'D') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}

class TechnicianSheet implements FromArray, WithTitle, WithEvents
{
    private $tech_data;
    private $start;
    private $end;
    private $has_commission;
    private $total_rows;
    private $day_abbr = [0 => 'DO', 1 => 'LU', 2 => 'MA', 3 => 'MI', 4 => 'JU', 5 => 'VI', 6 => 'SA'];

    public function __construct($tech_data, $start, $end)
    {
        $this->tech_data = $tech_data;
        $this->start = $start;
        $this->end = $end;
        $this->has_commission = true; // comisión por producto: la columna PAGO se muestra siempre
    }

    public function title(): string
    {
        return substr(strtoupper($this->tech_data['technician']->name), 0, 31);
    }

    public function array(): array
    {
        $tech = $this->tech_data['technician'];
        $rows = [];
        $rows[] = [strtoupper($tech->name) . ' — REPARACIONES (' . $this->start->format('d/m/Y') . ' a ' . $this->end->format('d/m/Y') . ')'];
        $rows[] = [];

        $header = [
            'ORDEN', 'DÍA', 'NOTA', 'CLIENTE', 'TIPO DE REPARACIÓN',
            'TOTAL', 'ANTICIPO', 'DEBE', 'TIPO DE PAGO',
            'FECHA ENTRADA', 'FECHA SALIDA', 'TIPO DE CAMBIO',
            'TOTAL EN PESOS', 'SUCURSAL', 'VENDEDOR',
        ];
        if ($this->has_commission) {
            $header[] = 'PAGO ' . strtoupper($tech->name);
        }
        $rows[] = $header;

        $order = 1;
        $empty_cols_after_total = $this->has_commission ? 9 : 8; // padding for subtotal rows

        if ($this->tech_data['week_count'] == 0) {
            $rows[] = ['Sin reparaciones en este periodo'];
        } else {
            foreach ($this->tech_data['by_day'] as $day_info) {
                foreach ($day_info['lines'] as $line) {
                    $row = [
                        $order++,
                        $this->day_abbr[$day_info['date']->dayOfWeek],
                        $line['invoice_no'],
                        $line['customer'],
                        $line['product'],
                        $line['total'],
                        $line['anticipo'] > 0 ? $line['anticipo'] : '',
                        $line['debe'] > 0 ? $line['debe'] : '',
                        $line['tipo_pago'],
                        !empty($line['entry_date']) ? \Carbon\Carbon::parse($line['entry_date'])->format('d/m/Y') : '',
                        \Carbon\Carbon::parse($line['transaction_date'])->format('d/m/Y'),
                        $line['tipo_cambio'] ?: '',
                        $line['total_en_pesos'],
                        $line['location'],
                        $line['vendor'] ?: '—',
                    ];
                    if ($this->has_commission) {
                        $row[] = (float) ($line['commission'] ?? 0);
                    }
                    $rows[] = $row;
                }
                // Day subtotal
                $sub_row = ['', '', '', '', 'SUBTOTAL ' . $this->day_abbr[$day_info['date']->dayOfWeek] . ' (' . $day_info['count'] . ' rep.)', $day_info['subtotal']];
                for ($i = 0; $i < $empty_cols_after_total; $i++) $sub_row[] = '';
                if ($this->has_commission) {
                    $sub_row[] = array_sum(array_column($day_info['lines'], 'commission'));
                }
                $rows[] = $sub_row;
            }
            // Week total
            $week_row = ['', '', '', '', 'TOTAL SEMANA (' . $this->tech_data['week_count'] . ' reparaciones)', $this->tech_data['week_total']];
            for ($i = 0; $i < $empty_cols_after_total; $i++) $week_row[] = '';
            if ($this->has_commission) {
                $week_row[] = $this->tech_data['commission_due'];
            }
            $rows[] = $week_row;
        }

        $this->total_rows = count($rows);

        return $rows;
    }

    public function registerEvents(): array
    {
        $has_commission = $this->has_commission;
        // 15 base columns (A..O) + optional 16th (P) for commission
        $lastCol = $has_commission ? 'P' : 'O';

        return [
            AfterSheet::class => function (AfterSheet $event) use ($lastCol, $has_commission) {
                $sheet = $event->sheet->getDelegate();

                // Title row big bold
                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                ]);
                $sheet->mergeCells("A1:{$lastCol}1");

                // Header row blue
                $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2196F3']],
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Number formats for currency columns: F=TOTAL, G=ANTICIPO, H=DEBE, L=TIPO DE CAMBIO, M=TOTAL EN PESOS
                $rowCount = $this->total_rows + 5;
                foreach (['F', 'G', 'H', 'L', 'M'] as $col) {
                    $sheet->getStyle("{$col}4:{$col}{$rowCount}")->getNumberFormat()->setFormatCode('#,##0.00');
                }
                if ($has_commission) {
                    $sheet->getStyle("P4:P{$rowCount}")->getNumberFormat()->setFormatCode('#,##0.00');
                }

                // Auto-size
                foreach (range('A', $lastCol) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Borders
                $sheet->getStyle("A3:{$lastCol}{$rowCount}")
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }
}
