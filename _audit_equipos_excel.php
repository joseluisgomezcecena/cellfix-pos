<?php
// Genera la "Relación de equipos en stock según sistema" para conteo físico.
// Una hoja por sucursal + hoja RESUMEN. Equipos viejos = SKU CF- + 4-6 dígitos.

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=celfix_prod_audit', 'root', 'root', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$sql = "
SELECT p.sku, p.name, vld.location_id, vld.qty_available,
       COALESCE(outf.qty,0) AS sold, outf.last_sale,
       COALESCE(inf.qty,0) AS inflow
FROM variation_location_details vld
JOIN variations v ON v.id = vld.variation_id
JOIN products p ON p.id = v.product_id
LEFT JOIN (
  SELECT pl.variation_id, t.location_id, SUM(pl.quantity) qty
  FROM purchase_lines pl JOIN transactions t ON t.id = pl.transaction_id
  WHERE t.business_id = 2 AND t.type IN ('opening_stock','purchase','purchase_transfer')
  GROUP BY pl.variation_id, t.location_id
) inf ON inf.variation_id = vld.variation_id AND inf.location_id = vld.location_id
LEFT JOIN (
  SELECT tsl.variation_id, t.location_id, SUM(tsl.quantity) qty, MAX(t.transaction_date) last_sale
  FROM transaction_sell_lines tsl JOIN transactions t ON t.id = tsl.transaction_id
  WHERE t.business_id = 2 AND t.type IN ('sell','sell_transfer') AND t.status = 'final'
  GROUP BY tsl.variation_id, t.location_id
) outf ON outf.variation_id = vld.variation_id AND outf.location_id = vld.location_id
WHERE p.business_id = 2 AND p.enable_stock = 1 AND vld.qty_available > 0
  AND p.sku REGEXP '^CF-[0-9]{4,6}$'
ORDER BY vld.location_id, p.name
";

$locNames = [];
foreach ($pdo->query("SELECT id,name FROM business_locations WHERE business_id=2") as $r) {
    $locNames[$r['id']] = $r['name'];
}

$byLoc = [];
foreach ($pdo->query($sql) as $r) {
    $byLoc[$r['location_id']][] = $r;
}

$spreadsheet = new Spreadsheet();
$spreadsheet->removeSheetByIndex(0);

$headers = ['SKU', 'EQUIPO', 'IMEI', 'STOCK SISTEMA', 'ÚLTIMA VENTA REGISTRADA',
            'ESTADO FÍSICO (PRESENTE / VENDIDO / NO ENCONTRADO)', 'NOTAS DEL GERENTE'];

// ---- RESUMEN sheet ----
$resumen = $spreadsheet->createSheet();
$resumen->setTitle('RESUMEN');
$resumen->setCellValue('A1', 'RELACIÓN DE EQUIPOS EN STOCK (según sistema) — para conteo físico');
$resumen->setCellValue('A2', 'Equipos antiguos: SKU CF- + 4 a 6 dígitos. Generado: ' . date('d/m/Y H:i'));
$resumen->mergeCells('A1:C1');
$resumen->getStyle('A1')->getFont()->setBold(true)->setSize(13);
$resumen->setCellValue('A4', 'SUCURSAL');
$resumen->setCellValue('B4', 'EQUIPOS EN STOCK');
$resumen->getStyle('A4:B4')->getFont()->setBold(true);
$resRow = 5;
$granTotal = 0;

ksort($byLoc);
foreach ($byLoc as $locId => $rows) {
    $count = count($rows);
    $granTotal += $count;
    $locName = $locNames[$locId] ?? ('Loc ' . $locId);
    $resumen->setCellValue('A' . $resRow, $locName);
    $resumen->setCellValue('B' . $resRow, $count);
    $resRow++;

    // sheet por sucursal (titulo <=31 chars)
    $title = substr($locName, 0, 31);
    $sheet = $spreadsheet->createSheet();
    $sheet->setTitle($title);

    $sheet->setCellValue('A1', strtoupper($locName) . ' — equipos que el sistema cree en stock');
    $sheet->mergeCells('A1:G1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);

    $col = 'A';
    foreach ($headers as $h) {
        $sheet->setCellValue($col . '3', $h);
        $col++;
    }
    $sheet->getStyle('A3:G3')->applyFromArray([
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2196F3']],
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);

    $row = 4;
    foreach ($rows as $r) {
        $imei = '';
        if (preg_match('/IMEI\s*([0-9]+)/i', $r['name'], $m)) {
            $imei = $m[1];
        }
        $lastSale = $r['last_sale'] ? date('d/m/Y', strtotime($r['last_sale'])) : '';
        $sheet->setCellValueExplicit('A' . $row, $r['sku'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('B' . $row, $r['name']);
        $sheet->setCellValueExplicit('C' . $row, $imei, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('D' . $row, (float) $r['qty_available']);
        $sheet->setCellValue('E' . $row, $lastSale);
        // F (estado fisico) y G (notas) se dejan vacios para el gerente
        $row++;
    }

    $lastRow = $row - 1;
    if ($lastRow >= 4) {
        $sheet->getStyle("A3:G{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }
    foreach (range('A', 'G') as $c) {
        $sheet->getColumnDimension($c)->setAutoSize(true);
    }
    // resaltar columna a llenar
    if ($lastRow >= 4) {
        $sheet->getStyle("F4:F{$lastRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF9C4');
    }
}

$resumen->setCellValue('A' . $resRow, 'TOTAL');
$resumen->setCellValue('B' . $resRow, $granTotal);
$resumen->getStyle("A{$resRow}:B{$resRow}")->getFont()->setBold(true);
$resumen->getColumnDimension('A')->setAutoSize(true);
$resumen->getColumnDimension('B')->setAutoSize(true);

$out = __DIR__ . '/docs/relacion_equipos_' . date('Y-m-d') . '.xlsx';
(new Xlsx($spreadsheet))->save($out);
echo 'OK -> ' . $out . PHP_EOL;
echo 'Total equipos en relacion: ' . $granTotal . PHP_EOL;
