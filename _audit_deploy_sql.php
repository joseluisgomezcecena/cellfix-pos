<?php
// Genera el SQL de despliegue a produccion comparando el esquema de celfix_dev (final)
// contra celfix_prod_audit (esquema real de produccion). Salida: docs/deploy_produccion.sql

$dev  = new PDO('mysql:host=127.0.0.1;port=3306;dbname=celfix_dev', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$prod = new PDO('mysql:host=127.0.0.1;port=3306;dbname=celfix_prod_audit', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

function tables($pdo, $schema) {
    return $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='$schema' AND table_type='BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
}
function cols($pdo, $schema, $table) {
    $out = [];
    $st = $pdo->query("SELECT column_name, column_type, is_nullable, column_default, extra, column_comment
                       FROM information_schema.columns
                       WHERE table_schema='$schema' AND table_name='".$table."' ORDER BY ordinal_position");
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $r = array_change_key_case($r, CASE_LOWER);
        $out[$r['column_name']] = $r;
    }
    return $out;
}
function colDDL($r) {
    $def = '`'.$r['column_name'].'` '.$r['column_type'];
    $def .= ($r['is_nullable'] === 'NO') ? ' NOT NULL' : ' NULL';
    if ($r['column_default'] !== null) {
        $d = $r['column_default'];
        if (preg_match('/^(CURRENT_TIMESTAMP|current_timestamp)/', $d)) $def .= ' DEFAULT '.$d;
        else $def .= " DEFAULT '".str_replace("'", "''", $d)."'";
    }
    if (!empty($r['column_comment'])) $def .= " COMMENT '".str_replace("'", "''", $r['column_comment'])."'";
    return $def;
}

$devTables  = tables($dev, 'celfix_dev');
$prodTables = array_flip(tables($prod, 'celfix_prod_audit'));

// Tablas nuevas relevantes de este release (excluye vendors/vendor_locations que se dropearon)
$skip = ['vendors','vendor_locations'];
$newTables = [];
foreach ($devTables as $t) {
    if (!isset($prodTables[$t]) && !in_array($t, $skip)) $newTables[] = $t;
}

// Ordenar por dependencias: una tabla que es referenciada por otra (FK) debe ir primero.
$createCache = [];
foreach ($newTables as $t) {
    $createCache[$t] = $dev->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_ASSOC)['Create Table'];
}
$ordered = [];
$visiting = [];
$visit = function ($t) use (&$visit, &$ordered, &$visiting, $newTables, $createCache) {
    if (in_array($t, $ordered)) return;
    $visiting[$t] = true;
    foreach ($newTables as $dep) {
        if ($dep === $t) continue;
        // si $t referencia a $dep (otra tabla nueva), $dep va primero
        if (preg_match('/REFERENCES `'.preg_quote($dep, '/').'`/', $createCache[$t]) && empty($visiting[$dep])) {
            $visit($dep);
        }
    }
    $visiting[$t] = false;
    if (!in_array($t, $ordered)) $ordered[] = $t;
};
foreach ($newTables as $t) $visit($t);
$newTables = $ordered;

$sql  = "-- ====================================================================\n";
$sql .= "-- DESPLIEGUE A PRODUCCION - Celfix POS (feature/pos-improved)\n";
$sql .= "-- Generado: ".date('Y-m-d H:i')."\n";
$sql .= "-- Aplica en phpMyAdmin sobre la base de PRODUCCION. Hacer BACKUP antes.\n";
$sql .= "-- ====================================================================\n\n";
$sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

// 1) Tablas nuevas (CREATE TABLE IF NOT EXISTS)
$sql .= "-- ============ TABLAS NUEVAS ============\n\n";
foreach ($newTables as $t) {
    $create = $dev->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_ASSOC)['Create Table'];
    $create = preg_replace('/^CREATE TABLE/', 'CREATE TABLE IF NOT EXISTS', $create);
    $create = preg_replace('/ AUTO_INCREMENT=\d+/', '', $create); // arrancar limpio
    $sql .= $create.";\n\n";
}

// 2) Columnas nuevas en tablas existentes
$sql .= "-- ============ COLUMNAS NUEVAS EN TABLAS EXISTENTES ============\n\n";
$addedColsReport = [];
foreach ($devTables as $t) {
    if (!isset($prodTables[$t])) continue; // tabla nueva, ya cubierta
    $dCols = cols($dev, 'celfix_dev', $t);
    $pCols = cols($prod, 'celfix_prod_audit', $t);
    $missing = array_diff(array_keys($dCols), array_keys($pCols));
    if (empty($missing)) continue;
    foreach ($missing as $c) {
        $alter = "ALTER TABLE `$t` ADD COLUMN ".colDDL($dCols[$c]);
        $alterEsc = str_replace("'", "''", $alter); // escapar comillas para el literal
        $sql .= "-- columna $t.$c (solo si no existe)\n";
        $sql .= "SET @e := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '$t' AND column_name = '$c');\n";
        $sql .= "SET @s := IF(@e = 0, '$alterEsc', 'DO 1');\n";
        $sql .= "PREPARE st FROM @s;\n";
        $sql .= "EXECUTE st;\n";
        $sql .= "DEALLOCATE PREPARE st;\n\n";
        $addedColsReport[] = "$t.$c";
    }
}

// 3) Limpieza de datos: marcas con formula de Excel sin uso
$sql .= "-- ============ LIMPIEZA DE DATOS (marcas con formula de Excel) ============\n";
$sql .= "DELETE FROM `brands`\n WHERE `name` LIKE '=%'\n   AND `id` NOT IN (SELECT brand_id FROM (SELECT DISTINCT brand_id FROM products WHERE brand_id IS NOT NULL) x);\n\n";

$sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

file_put_contents(__DIR__.'/docs/deploy_produccion.sql', $sql);

echo "Tablas nuevas a crear (".count($newTables)."):\n";
foreach ($newTables as $t) echo "  + $t\n";
echo "\nColumnas nuevas a agregar (".count($addedColsReport)."):\n";
foreach ($addedColsReport as $c) echo "  + $c\n";
echo "\nSQL -> docs/deploy_produccion.sql\n";
