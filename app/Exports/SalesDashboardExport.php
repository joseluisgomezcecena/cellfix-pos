<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SalesDashboardExport implements WithMultipleSheets
{
    use Exportable;

    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            new DashEquiposSheet($this->data),
            new DashDetalleSheet($this->data),
            new DashCategoriasSheet($this->data),
            new DashResumenSheet($this->data),
        ];
    }
}

/** Helper de estilos comunes */
trait DashSheetStyle
{
    protected function fillRow($sheet, $range, $rgb, $fontColor = '000000')
    {
        $sheet->getStyle($range)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rgb]],
            'font' => ['bold' => true, 'color' => ['rgb' => $fontColor]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }

    protected function autosize($sheet, $lastCol)
    {
        foreach (range('A', $lastCol) as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }
    }

    protected function col($n)
    {
        // 1 => A
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($n);
    }
}

class DashEquiposSheet implements FromArray, WithTitle, WithEvents
{
    use DashSheetStyle;

    private $data;
    private $rowsCount = 0;
    private $headerRow1; // tabla por dia
    private $totalRow; private $metaRow; private $faltanRow;
    private $matrixHeaderRow; private $matrixTotalRow; private $matrixLastCol;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'EQUIPOS';
    }

    public function array(): array
    {
        $d = $this->data;
        $rows = [];
        $rows[] = ['EQUIPOS — '.$d['start']->format('d/m/Y').' a '.$d['end']->format('d/m/Y')];
        $rows[] = [];
        $this->headerRow1 = 3;
        $rows[] = ['SEMANA', 'EQUIPOS', 'TOTAL'];
        foreach ($d['days'] as $day) {
            $rows[] = [$day['label'], (int) ($d['eq_by_day'][$day['key']]['qty'] ?? 0), round($d['eq_by_day'][$day['key']]['amount'] ?? 0, 2)];
        }
        $this->totalRow = 3 + 1 + count($d['days']);
        $rows[] = ['TOTAL', (int) $d['eq_total_qty'], round($d['eq_total_amount'], 2)];
        $this->metaRow = $this->totalRow + 1;
        $rows[] = ['META', (int) $d['meta_qty'], ''];
        $this->faltanRow = $this->metaRow + 1;
        $rows[] = ['FALTAN', (int) $d['faltan'], ''];
        $rows[] = [];

        // Matriz por vendedor
        $this->matrixHeaderRow = $this->faltanRow + 2;
        $header = ['SEMANA'];
        foreach ($d['eq_vendors'] as $v) {
            $header[] = $v;
        }
        $header[] = 'TOTAL';
        $this->matrixLastCol = $this->col(count($header));
        $rows[] = $header;
        foreach ($d['days'] as $day) {
            $row = [$day['label']];
            $rt = 0;
            foreach ($d['eq_vendors'] as $v) {
                $c = (int) ($d['eq_matrix'][$v][$day['key']] ?? 0);
                $row[] = $c;
                $rt += $c;
            }
            $row[] = $rt;
            $rows[] = $row;
        }
        $this->matrixTotalRow = $this->matrixHeaderRow + 1 + count($d['days']);
        $trow = ['TOTALES'];
        foreach ($d['eq_vendors'] as $v) {
            $ct = 0;
            foreach ($d['days'] as $day) {
                $ct += $d['eq_matrix'][$v][$day['key']] ?? 0;
            }
            $trow[] = (int) $ct;
        }
        $trow[] = (int) $d['eq_total_qty'];
        $rows[] = $trow;

        $this->rowsCount = count($rows);

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $this->fillRow($sheet, "A{$this->headerRow1}:C{$this->headerRow1}", '2196F3', 'FFFFFF');
                $this->fillRow($sheet, "A{$this->totalRow}:C{$this->totalRow}", 'BBDEFB');
                $this->fillRow($sheet, "A{$this->metaRow}:C{$this->metaRow}", '2196F3', 'FFFFFF');
                $this->fillRow($sheet, "A{$this->faltanRow}:C{$this->faltanRow}", 'E53935', 'FFFFFF');
                $this->fillRow($sheet, "A{$this->matrixHeaderRow}:{$this->matrixLastCol}{$this->matrixHeaderRow}", '8E24AA', 'FFFFFF');
                $this->fillRow($sheet, "A{$this->matrixTotalRow}:{$this->matrixLastCol}{$this->matrixTotalRow}", 'E1BEE7');
                $this->autosize($sheet, $this->matrixLastCol);
            },
        ];
    }
}

class DashDetalleSheet implements FromArray, WithTitle, WithEvents
{
    use DashSheetStyle;

    private $data; private $count = 0;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'DETALLE EQUIPOS';
    }

    public function array(): array
    {
        $rows = [];
        $rows[] = ['ORDEN', 'VENDEDOR', 'EQUIPO', 'SKU/IMEI', 'COSTO', 'TIPO DE PAGO', 'FECHA', 'TOTAL'];
        foreach ($this->data['detail'] as $r) {
            $rows[] = [
                $r['order'], $r['vendor'], $r['product'], $r['sku'],
                round($r['cost'], 2), strtoupper(substr($r['pay_method'] ?? '', 0, 1)),
                $r['date'], round($r['amount'], 2),
            ];
        }
        if (count($rows) === 1) {
            $rows[] = ['Sin equipos vendidos en este periodo'];
        }
        $this->count = count($rows);

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $this->fillRow($sheet, 'A1:H1', '00ACC1', 'FFFFFF');
                $sheet->getStyle("A1:H{$this->count}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $this->autosize($sheet, 'H');
            },
        ];
    }
}

class DashCategoriasSheet implements FromArray, WithTitle, WithEvents
{
    use DashSheetStyle;

    private $data; private $lastCol; private $count = 0;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'CATEGORIAS';
    }

    public function array(): array
    {
        $d = $this->data;
        $vendors = $d['allCatVendors'];
        $header = ['CATEGORIA'];
        foreach ($vendors as $v) {
            $header[] = $v;
        }
        $header[] = 'TOTAL';
        $this->lastCol = $this->col(count($header));
        $rows = [$header];
        foreach ($d['buckets'] as $bname => $bd) {
            if ($bd['qty'] <= 0) {
                continue;
            }
            $row = [$bname];
            foreach ($vendors as $v) {
                $row[] = isset($bd['vendors'][$v]) ? (int) $bd['vendors'][$v]['qty'] : 0;
            }
            $row[] = (int) $bd['qty'];
            $rows[] = $row;
        }
        $this->count = count($rows);

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $this->fillRow($sheet, "A1:{$this->lastCol}1", 'FB8C00', 'FFFFFF');
                $sheet->getStyle("A1:{$this->lastCol}{$this->count}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $this->autosize($sheet, $this->lastCol);
            },
        ];
    }
}

class DashResumenSheet implements FromArray, WithTitle, WithEvents
{
    use DashSheetStyle;

    private $data; private $count = 0; private $totalRow;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'RESUMEN';
    }

    public function array(): array
    {
        $rows = [['CATEGORIA', 'CANTIDAD', 'TOTAL $']];
        $gq = 0; $ga = 0;
        foreach ($this->data['buckets'] as $bname => $bd) {
            if ($bd['qty'] <= 0 && $bd['amount'] <= 0) {
                continue;
            }
            $rows[] = [$bname, (int) $bd['qty'], round($bd['amount'], 2)];
            $gq += $bd['qty'];
            $ga += $bd['amount'];
        }
        $rows[] = ['TOTAL', (int) $gq, round($ga, 2)];
        $this->count = count($rows);
        $this->totalRow = $this->count;

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $this->fillRow($sheet, 'A1:C1', '43A047', 'FFFFFF');
                $this->fillRow($sheet, "A{$this->totalRow}:C{$this->totalRow}", 'DFF0D8');
                $sheet->getStyle("A1:C{$this->count}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $this->autosize($sheet, 'C');
            },
        ];
    }
}
