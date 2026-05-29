<?php
// Streaming importer for a large mysqldump into a separate audit DB.
// Reads line-by-line, executes one statement at a time. Never loads whole file.

ini_set('memory_limit', '1024M');
set_time_limit(0);

$dump   = 'C:\\Users\\Jose Luis\\Downloads\\pos_celfix_mx_2026-05-21_02-45-14_mysql_data_PlfFs.sql\\pos_celfix_mx_2026-05-21_02-45-14_mysql_data_PlfFs.sql';
$dbname = 'celfix_prod_audit';
$progress = __DIR__ . '/_audit_import_progress.log';
$errors   = __DIR__ . '/_audit_import_errors.log';

@unlink($progress);
@unlink($errors);

$pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', 'root', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$pdo->exec("DROP DATABASE IF EXISTS `$dbname`");
$pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$dbname`");
$pdo->exec("SET FOREIGN_KEY_CHECKS=0");
$pdo->exec("SET UNIQUE_CHECKS=0");

$fh = fopen($dump, 'r');
if (!$fh) {
    file_put_contents($progress, "ERROR: cannot open dump file\n");
    exit(1);
}

$buffer = '';
$count  = 0;
$errCount = 0;
$start  = time();

while (($line = fgets($fh)) !== false) {
    $ltrim = ltrim($line);
    // skip pure comment lines and blank lines
    if ($ltrim === '' || strncmp($ltrim, '--', 2) === 0) {
        continue;
    }
    $buffer .= $line;
    if (substr(rtrim($line), -1) === ';') {
        try {
            $pdo->exec($buffer);
        } catch (PDOException $e) {
            $errCount++;
            file_put_contents($errors, substr($e->getMessage(), 0, 300) . "\n", FILE_APPEND);
        }
        $buffer = '';
        $count++;
        if ($count % 200 === 0) {
            file_put_contents($progress, "running stmts=$count errors=$errCount elapsed=" . (time() - $start) . "s\n");
        }
    }
}
fclose($fh);

file_put_contents($progress, "DONE stmts=$count errors=$errCount elapsed=" . (time() - $start) . "s\n", FILE_APPEND);
echo "DONE stmts=$count errors=$errCount\n";
