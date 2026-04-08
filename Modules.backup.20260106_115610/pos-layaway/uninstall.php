<?php
/**
 * POS Layaway Module - Uninstaller
 *
 * This script safely removes the Layaway module from Ultimate POS.
 *
 * @author POS Modules Team
 * @version 1.0.0
 */

class LayawayUninstaller
{
    private $basePath;
    private $backupPath;
    private $logFile;

    public function __construct()
    {
        $this->basePath = getcwd();
        $this->backupPath = $this->basePath . '/storage/layaway-uninstall-backup-' . date('Y-m-d-H-i-s');
        $this->logFile = $this->basePath . '/storage/logs/layaway-uninstall.log';

        if (!file_exists($this->basePath . '/artisan')) {
            $this->error("Error: This doesn't appear to be a Laravel/Ultimate POS installation.");
            exit(1);
        }
    }

    public function uninstall()
    {
        $this->log("=== POS Layaway Module Uninstallation Started ===");
        $this->info("POS Layaway Module Uninstaller v1.0.0");
        $this->info("Uninstallation started at: " . date('Y-m-d H:i:s'));

        // Warning and confirmation
        $this->warning("⚠️  WARNING: This will completely remove the Layaway module!");
        $this->warning("⚠️  All layaway data will be PERMANENTLY DELETED!");
        $this->info("\nData that will be removed:");
        $this->info("• All layaway records");
        $this->info("• All layaway payments");
        $this->info("• All layaway items");
        $this->info("• Module files and configuration");
        $this->info("• Database tables and relationships");

        $confirmation = $this->confirm("\nAre you sure you want to proceed? (yes/no): ");
        if (!$confirmation) {
            $this->info("Uninstallation cancelled.");
            exit(0);
        }

        $dataBackup = $this->confirm("Do you want to backup layaway data before removal? (yes/no): ");

        try {
            // Create backup if requested
            if ($dataBackup) {
                $this->info("\n💾 Creating data backup...");
                $this->createDataBackup();
            }

            // Export data for records
            $this->info("\n📊 Exporting data summary...");
            $this->exportDataSummary();

            // Remove module files
            $this->info("\n🗂️  Removing module files...");
            $this->removeModuleFiles();

            // Revert system changes
            $this->info("\n🔄 Reverting system modifications...");
            $this->revertSystemChanges();

            // Remove database tables
            $this->info("\n🗄️  Removing database tables...");
            $this->removeDatabaseTables();

            // Clean up
            $this->info("\n🧹 Cleaning up...");
            $this->cleanup();

            $this->success("\n✅ Uninstallation completed successfully!");
            $this->info("Module has been completely removed from your system.");

            if ($dataBackup) {
                $this->info("Backup created at: " . $this->backupPath);
            }

        } catch (Exception $e) {
            $this->error("\n❌ Uninstallation failed: " . $e->getMessage());
            exit(1);
        }
    }

    private function createDataBackup()
    {
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }

        try {
            // Export layaway data
            $this->runCommand("php artisan tinker --execute=\"
                file_put_contents('{$this->backupPath}/layaways.json',
                    \\Modules\\Layaway\\Entities\\Layaway::with(['items', 'payments'])->get()->toJson(JSON_PRETTY_PRINT)
                );
                echo 'Data exported successfully';
            \"");

            $this->success("✓ Data backup created");
        } catch (Exception $e) {
            $this->warning("Could not create data backup: " . $e->getMessage());
        }
    }

    private function exportDataSummary()
    {
        try {
            $summary = $this->runCommand("php artisan tinker --execute=\"
                \$layaways = \\Modules\\Layaway\\Entities\\Layaway::count();
                \$payments = \\Modules\\Layaway\\Entities\\LayawayPayment::count();
                \$items = \\Modules\\Layaway\\Entities\\LayawayItem::count();
                echo 'Layaways: ' . \$layaways . ', Payments: ' . \$payments . ', Items: ' . \$items;
            \"");

            file_put_contents($this->backupPath . '/summary.txt',
                "Layaway Module Removal Summary\n" .
                "Date: " . date('Y-m-d H:i:s') . "\n" .
                "Data: " . $summary . "\n"
            );

            $this->success("✓ Data summary exported");
        } catch (Exception $e) {
            $this->warning("Could not export data summary");
        }
    }

    private function removeModuleFiles()
    {
        $moduleDir = $this->basePath . '/Modules/Layaway';

        if (is_dir($moduleDir)) {
            // Backup module files
            if (is_dir($this->backupPath)) {
                $this->runCommand("cp -r '{$moduleDir}' '{$this->backupPath}/Layaway-module'");
            }

            // Remove module directory
            $this->runCommand("rm -rf '{$moduleDir}'");
            $this->success("✓ Module files removed");
        } else {
            $this->warning("Module directory not found");
        }

        // Remove console command
        $commandFile = $this->basePath . '/app/Console/Commands/FixLayawayNumbers.php';
        if (file_exists($commandFile)) {
            copy($commandFile, $this->backupPath . '/FixLayawayNumbers.php');
            unlink($commandFile);
            $this->log("Removed FixLayawayNumbers command");
        }
    }

    private function revertSystemChanges()
    {
        // Revert Transaction.php changes
        $transactionFile = $this->basePath . '/app/Transaction.php';
        if (file_exists($transactionFile)) {
            $content = file_get_contents($transactionFile);

            // Remove layaway relationship method
            $pattern = '/\/\*\*\s*\*\s*Get the associated layaway\s*\*\/\s*public function layaway\(\)\s*\{\s*return[^}]+\}\s*/s';
            $content = preg_replace($pattern, '', $content);

            file_put_contents($transactionFile, $content);
            $this->success("✓ System modifications reverted");
        }
    }

    private function removeDatabaseTables()
    {
        $this->warning("⚠️  This will permanently delete all layaway data!");
        $finalConfirm = $this->confirm("Final confirmation - Delete all layaway data? (yes/no): ");

        if (!$finalConfirm) {
            $this->info("Database removal cancelled. Module files have been removed but data preserved.");
            return;
        }

        try {
            // Drop tables in correct order (respect foreign keys)
            $this->runCommand("php artisan tinker --execute=\"
                \\DB::statement('SET FOREIGN_KEY_CHECKS=0');
                \\DB::statement('DROP TABLE IF EXISTS layaway_payments');
                \\DB::statement('DROP TABLE IF EXISTS layaway_items');
                \\DB::statement('DROP TABLE IF EXISTS layaways');
                \\DB::statement('DROP TABLE IF EXISTS sequences');
                \\DB::statement('SET FOREIGN_KEY_CHECKS=1');
                echo 'Tables dropped successfully';
            \"");

            // Remove layaway_id column from transactions table
            $this->runCommand("php artisan tinker --execute=\"
                if (\\Schema::hasColumn('transactions', 'layaway_id')) {
                    \\Schema::table('transactions', function (\\$table) {
                        \\$table->dropForeign(['layaway_id']);
                        \\$table->dropColumn('layaway_id');
                    });
                    echo 'Transactions table cleaned';
                }
            \"");

            $this->success("✓ Database tables removed");
        } catch (Exception $e) {
            $this->error("Failed to remove database tables: " . $e->getMessage());
            throw $e;
        }
    }

    private function cleanup()
    {
        // Clear caches
        $this->runCommand('php artisan config:clear');
        $this->runCommand('php artisan cache:clear');
        $this->runCommand('php artisan route:clear');
        $this->runCommand('composer dump-autoload');

        $this->success("✓ System caches cleared");
    }

    private function confirm($message)
    {
        echo $message;
        $handle = fopen("php://stdin", "r");
        $response = trim(fgets($handle));
        fclose($handle);

        return strtolower($response) === 'yes' || strtolower($response) === 'y';
    }

    private function runCommand($command, $throwOnError = true)
    {
        $this->log("Executing: " . $command);

        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);

        $outputStr = implode("\n", $output);
        $this->log("Output: " . $outputStr);

        if ($returnCode !== 0 && $throwOnError) {
            throw new Exception("Command failed: " . $command . "\nOutput: " . $outputStr);
        }

        return $outputStr;
    }

    private function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";

        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    private function info($message)
    {
        echo $message . "\n";
        $this->log("INFO: " . $message);
    }

    private function success($message)
    {
        echo "\033[32m" . $message . "\033[0m\n";
        $this->log("SUCCESS: " . $message);
    }

    private function warning($message)
    {
        echo "\033[33m" . $message . "\033[0m\n";
        $this->log("WARNING: " . $message);
    }

    private function error($message)
    {
        echo "\033[31m" . $message . "\033[0m\n";
        $this->log("ERROR: " . $message);
    }
}

// Check if running from command line
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Run the uninstaller
try {
    $uninstaller = new LayawayUninstaller();
    $uninstaller->uninstall();
} catch (Exception $e) {
    echo "\n❌ Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}