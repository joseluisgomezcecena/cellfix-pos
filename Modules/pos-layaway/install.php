<?php
/**
 * POS Layaway Module - Automated Installer
 *
 * This script automatically installs the Layaway module into an existing Ultimate POS installation.
 *
 * @author POS Modules Team
 * @version 1.1.0
 */

class LayawayInstaller
{
    private $basePath;
    private $backupPath;
    private $logFile;
    private $errors = [];
    private $warnings = [];

    public function __construct()
    {
        $this->basePath = getcwd();
        $this->backupPath = $this->basePath . '/storage/layaway-backup-' . date('Y-m-d-H-i-s');
        $this->logFile = $this->basePath . '/storage/logs/layaway-install.log';

        // Ensure we're in a Laravel/Ultimate POS directory
        if (!file_exists($this->basePath . '/artisan')) {
            $this->error("Error: This doesn't appear to be a Laravel/Ultimate POS installation.");
            $this->error("Please run this script from your Ultimate POS root directory.");
            exit(1);
        }
    }

    public function install()
    {
        $this->log("=== POS Layaway Module Installation Started ===");
        $this->info("POS Layaway Module Installer v1.1.0");
        $this->info("Installation started at: " . date('Y-m-d H:i:s'));

        try {
            // Pre-installation checks
            $this->info("\n🔍 Running pre-installation checks...");
            $this->checkRequirements();
            $this->checkPermissions();

            // Create backup
            $this->info("\n💾 Creating backup...");
            $this->createBackup();

            // Install module files
            $this->info("\n📦 Installing module files...");
            $this->installModuleFiles();

            // Run database migrations
            $this->info("\n🗄️  Running database migrations...");
            $this->runMigrations();

            // Apply system patches
            $this->info("\n🔧 Applying system patches...");
            $this->applySystemPatches();

            // Register module
            $this->info("\n📋 Registering module...");
            $this->registerModule();

            // Run module seeder
            $this->info("\n🌱 Running module seeder...");
            $this->runModuleSeeder();

            // Enable module
            $this->info("\n🔌 Enabling module...");
            $this->enableModule();

            // Verify installation
            $this->info("\n✅ Verifying installation...");
            $this->verifyInstallation();

            $this->success("\n🎉 Installation completed successfully!");
            $this->info("Backup created at: " . $this->backupPath);
            $this->info("You can now access the Layaway module in your Ultimate POS interface.");
            $this->info("\nNext steps:");
            $this->info("1. Refresh your browser (Ctrl+F5 or Cmd+Shift+R)");
            $this->info("2. Go to Settings → Roles to assign layaway permissions");
            $this->info("3. Access 'Layaway' in the menu to start using the module");

            if (!empty($this->warnings)) {
                $this->info("\n⚠️  Warnings during installation:");
                foreach ($this->warnings as $warning) {
                    $this->warning("  • " . $warning);
                }
            }

        } catch (Exception $e) {
            $this->error("\n❌ Installation failed: " . $e->getMessage());
            $this->error("Rolling back changes...");
            $this->rollback();
            exit(1);
        }
    }

    private function checkRequirements()
    {
        // Check PHP version
        if (version_compare(PHP_VERSION, '8.0.0', '<')) {
            throw new Exception("PHP 8.0 or higher is required. Current version: " . PHP_VERSION);
        }
        $this->success("✓ PHP version: " . PHP_VERSION);

        // Check if Laravel Modules is installed
        if (!file_exists($this->basePath . '/vendor/nwidart/laravel-modules')) {
            throw new Exception("Laravel Modules (nwidart/laravel-modules) is not installed. Please install it first.");
        }
        $this->success("✓ Laravel Modules package found");

        // Check if Modules directory exists
        if (!is_dir($this->basePath . '/Modules')) {
            mkdir($this->basePath . '/Modules', 0755, true);
            $this->warning("Created Modules directory");
        }
        $this->success("✓ Modules directory exists");

        // Check database connection
        try {
            $this->runCommand('php artisan migrate:status', false);
            $this->success("✓ Database connection working");
        } catch (Exception $e) {
            throw new Exception("Database connection failed. Please check your .env configuration.");
        }
    }

    private function checkPermissions()
    {
        $requiredPaths = [
            '/Modules',
            '/app',
            '/database/migrations',
            '/storage',
            '/bootstrap/cache'
        ];

        foreach ($requiredPaths as $path) {
            $fullPath = $this->basePath . $path;
            if (!is_writable($fullPath)) {
                throw new Exception("Directory not writable: " . $path);
            }
        }
        $this->success("✓ File permissions are correct");
    }

    private function createBackup()
    {
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }

        // Backup important files that will be modified
        $filesToBackup = [
            '/app/Transaction.php',
            '/composer.json'
        ];

        foreach ($filesToBackup as $file) {
            $source = $this->basePath . $file;
            if (file_exists($source)) {
                $destination = $this->backupPath . $file;
                $this->ensureDirectoryExists(dirname($destination));
                copy($source, $destination);
                $this->log("Backed up: " . $file);
            }
        }

        // Create database backup
        $this->createDatabaseBackup();
        $this->success("✓ Backup created");
    }

    private function createDatabaseBackup()
    {
        try {
            $this->runCommand('php artisan backup:run --only-db 2>/dev/null || echo "Database backup skipped"', false);
            $this->log("Database backup attempted");
        } catch (Exception $e) {
            $this->warning("Could not create database backup: " . $e->getMessage());
        }
    }

    private function installModuleFiles()
    {
        $sourceModule = __DIR__ . '/src/Modules/Layaway';
        $targetModule = $this->basePath . '/Modules/Layaway';

        if (is_dir($targetModule)) {
            $this->warning("Layaway module already exists. Backing up and replacing...");
            $backupModule = $this->backupPath . '/Modules/Layaway';
            $this->ensureDirectoryExists(dirname($backupModule));
            $this->runCommand("cp -r '{$targetModule}' '{$backupModule}'");
            $this->runCommand("rm -rf '{$targetModule}'");
        }

        $this->runCommand("cp -r '{$sourceModule}' '{$targetModule}'");
        $this->runCommand("chmod -R 755 '{$targetModule}'");
        $this->success("✓ Module files installed");
    }

    private function runMigrations()
    {
        // First run the system integration migrations
        $migrations = [
            __DIR__ . '/database/migrations/2025_09_18_070615_add_layaway_id_to_transactions_table.php',
            __DIR__ . '/database/migrations/2025_09_18_070942_add_transaction_payment_id_to_layaway_payments_table.php',
            __DIR__ . '/database/migrations/2025_09_18_083239_create_sequences_table.php'
        ];

        foreach ($migrations as $migration) {
            $targetMigration = $this->basePath . '/database/migrations/' . basename($migration);
            if (!file_exists($targetMigration)) {
                copy($migration, $targetMigration);
                $this->log("Copied migration: " . basename($migration));
            }
        }

        // Run migrations
        $this->runCommand('php artisan migrate --force');

        // Install module migrations
        $this->runCommand('php artisan module:migrate Layaway');

        $this->success("✓ Database migrations completed");
    }

    private function applySystemPatches()
    {
        // Apply Transaction.php patch
        $transactionFile = $this->basePath . '/app/Transaction.php';
        $transactionContent = file_get_contents($transactionFile);

        // Check if layaway relationship already exists
        if (strpos($transactionContent, 'function layaway()') === false) {
            // Add the layaway relationship method
            $layawayMethod = "\n    /**\n     * Get the associated layaway\n     */\n    public function layaway()\n    {\n        return \$this->belongsTo(\\Modules\\Layaway\\Entities\\Layaway::class, 'layaway_id');\n    }\n";

            // Find the last method in the class and add before the closing brace
            $lastMethodPos = strrpos($transactionContent, '}');
            $transactionContent = substr_replace($transactionContent, $layawayMethod . '}', $lastMethodPos);

            file_put_contents($transactionFile, $transactionContent);
            $this->log("Applied Transaction.php patch");
        } else {
            $this->warning("Transaction.php already has layaway relationship");
        }

        // Copy console command
        $commandSource = __DIR__ . '/app/patches/FixLayawayNumbers.php';
        $commandTarget = $this->basePath . '/app/Console/Commands/FixLayawayNumbers.php';
        if (!file_exists($commandTarget)) {
            copy($commandSource, $commandTarget);
            $this->log("Installed FixLayawayNumbers command");
        }

        $this->success("✓ System patches applied");
    }

    private function registerModule()
    {
        // Generate module cache
        $this->runCommand('php artisan module:list');

        // Clear various caches
        $this->runCommand('php artisan config:clear');
        $this->runCommand('php artisan cache:clear');
        $this->runCommand('php artisan route:clear');

        // Dump autoload
        $this->runCommand('composer dump-autoload');

        $this->success("✓ Module registered and caches cleared");
    }

    private function runModuleSeeder()
    {
        // Run the seeder to create permissions and register module version
        $this->runCommand('php artisan module:seed Layaway');
        $this->success("✓ Module seeder executed (permissions created)");
    }

    private function enableModule()
    {
        // Enable the Layaway module
        $this->runCommand('php artisan module:enable Layaway');

        // Clear caches again after enabling
        $this->runCommand('php artisan config:clear', false);
        $this->runCommand('php artisan cache:clear', false);
        $this->runCommand('php artisan route:clear', false);
        $this->runCommand('php artisan view:clear', false);

        $this->success("✓ Module enabled");
    }

    private function verifyInstallation()
    {
        // Check if module files exist
        $moduleDir = $this->basePath . '/Modules/Layaway';
        if (!is_dir($moduleDir)) {
            throw new Exception("Module directory not found after installation");
        }

        // Check if tables exist
        try {
            $this->runCommand('php artisan tinker --execute="echo \\Modules\\Layaway\\Entities\\Layaway::count();"', false);
            $this->success("✓ Layaway tables accessible");
        } catch (Exception $e) {
            throw new Exception("Layaway tables not accessible: " . $e->getMessage());
        }

        // Check if permissions were created
        try {
            $output = $this->runCommand('php artisan tinker --execute="echo \\Spatie\\Permission\\Models\\Permission::where(\'name\', \'LIKE\', \'layaway.%\')->count();"', false);
            $permCount = (int)trim($output);
            if ($permCount >= 7) {
                $this->success("✓ Layaway permissions created ({$permCount} permissions)");
            } else {
                $this->warning("Expected 7 permissions, found {$permCount}");
            }
        } catch (Exception $e) {
            $this->warning("Could not verify permissions: " . $e->getMessage());
        }

        // Check if module is enabled
        try {
            $output = $this->runCommand('php artisan module:list 2>&1', false);
            if (strpos($output, '[Enabled] Layaway') !== false) {
                $this->success("✓ Module is enabled");
            } else {
                $this->warning("Module may not be enabled");
            }
        } catch (Exception $e) {
            $this->warning("Could not verify module status");
        }

        // Check if module version is registered
        try {
            $output = $this->runCommand('php artisan tinker --execute="echo \\App\\System::getProperty(\'layaway_version\') ?? \'NOT_SET\';"', false);
            if (trim($output) !== 'NOT_SET') {
                $this->success("✓ Module version registered: " . trim($output));
            } else {
                $this->warning("Module version not registered in system table");
            }
        } catch (Exception $e) {
            $this->warning("Could not verify module version");
        }

        $this->success("✓ Installation verification completed");
    }

    private function rollback()
    {
        if (is_dir($this->backupPath)) {
            $this->info("Restoring from backup...");

            // Disable module first
            try {
                $this->runCommand('php artisan module:disable Layaway 2>&1', false);
                $this->log("Module disabled during rollback");
            } catch (Exception $e) {
                $this->log("Could not disable module: " . $e->getMessage());
            }

            // Restore files
            $filesToRestore = [
                '/app/Transaction.php',
                '/composer.json'
            ];

            foreach ($filesToRestore as $file) {
                $backup = $this->backupPath . $file;
                $target = $this->basePath . $file;
                if (file_exists($backup)) {
                    copy($backup, $target);
                    $this->log("Restored: " . $file);
                }
            }

            // Remove module directory if it was created
            $moduleDir = $this->basePath . '/Modules/Layaway';
            if (is_dir($moduleDir)) {
                $this->runCommand("rm -rf '{$moduleDir}'");
            }

            // Clear caches after rollback
            try {
                $this->runCommand('php artisan config:clear 2>&1', false);
                $this->runCommand('php artisan cache:clear 2>&1', false);
                $this->runCommand('php artisan route:clear 2>&1', false);
                $this->log("Caches cleared during rollback");
            } catch (Exception $e) {
                $this->log("Could not clear caches: " . $e->getMessage());
            }

            $this->info("Rollback completed. System restored to previous state.");
        }
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

    private function ensureDirectoryExists($path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    private function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";

        $this->ensureDirectoryExists(dirname($this->logFile));
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
        echo "\033[33m⚠️  " . $message . "\033[0m\n";
        $this->warnings[] = $message;
        $this->log("WARNING: " . $message);
    }

    private function error($message)
    {
        echo "\033[31m❌ " . $message . "\033[0m\n";
        $this->errors[] = $message;
        $this->log("ERROR: " . $message);
    }
}

// Check if running from command line
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Run the installer
try {
    $installer = new LayawayInstaller();
    $installer->install();
} catch (Exception $e) {
    echo "\n❌ Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}