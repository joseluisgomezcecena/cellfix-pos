<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class StockBulkAdjustTemplateExport implements FromArray, WithTitle, WithEvents
{
    private $rows;
    private $location_name;
    private $location_id;

    public function __construct($rows, $location_name, $location_id)
    {
        $this->rows = $rows;
        $this->location_name = $location_name;
        $this->location_id = (int) $location_id;
    }

    public function title(): string
    {
        return substr('Ajuste ' . $this->location_name, 0, 31);
    }

    public function array(): array
    {
        $out = [];
        // Marcador [LOCATION_ID:N] embebido en el título. Se valida al importar para evitar
        // que un archivo descargado para una sucursal se aplique a otra por error.
        $out[] = ['AJUSTE MASIVO DE STOCK — ' . strtoupper($this->location_name) . ' [LOCATION_ID:' . $this->location_id . ']'];
        $out[] = ['Llene la columna "STOCK_NUEVO" con el conteo físico. Deje vacía la celda para NO tocar ese producto.'];
        $out[] = ['IMPORTANTE: NO modifique las columnas variation_id, sku, nombre, ni el título de arriba (incluye marcador de sucursal).'];
        $out[] = [];
        $out[] = ['variation_id', 'sku', 'producto', 'variación', 'stock_actual', 'STOCK_NUEVO'];

        foreach ($this->rows as $r) {
            $sku = $r->sub_sku ?: $r->product_sku;
            $variation = ($r->variation_name && $r->variation_name !== 'DUMMY') ? $r->variation_name : '';
            $out[] = [
                (int) $r->variation_id,
                $sku,
                $r->product_name,
                $variation,
                (float) $r->stock_actual,
                '', // STOCK_NUEVO vacío — para llenar
            ];
        }

        return $out;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Título grande (fila 1)
                $sheet->mergeCells('A1:F1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2196F3']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Instrucciones (filas 2-3)
                $sheet->mergeCells('A2:F2');
                $sheet->mergeCells('A3:F3');
                $sheet->getStyle('A2:F3')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 10],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF9C4']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(28);
                $sheet->getRowDimension(3)->setRowHeight(28);

                // Encabezado (fila 5)
                $sheet->getStyle('A5:F5')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1976D2']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Resaltar columna STOCK_NUEVO en verde claro
                $highestRow = $sheet->getHighestRow();
                if ($highestRow > 5) {
                    $sheet->getStyle("F6:F{$highestRow}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C8E6C9']],
                        'font' => ['bold' => true],
                    ]);
                    $sheet->getStyle("A6:F{$highestRow}")
                        ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    // Columnas read-only en gris muy claro
                    $sheet->getStyle("A6:E{$highestRow}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F5F5']],
                    ]);
                }

                // Anchos
                $sheet->getColumnDimension('A')->setWidth(14);
                $sheet->getColumnDimension('B')->setWidth(18);
                $sheet->getColumnDimension('C')->setWidth(45);
                $sheet->getColumnDimension('D')->setWidth(20);
                $sheet->getColumnDimension('E')->setWidth(14);
                $sheet->getColumnDimension('F')->setWidth(16);

                // Congelar las filas de encabezado
                $sheet->freezePane('A6');
            },
        ];
    }
}
