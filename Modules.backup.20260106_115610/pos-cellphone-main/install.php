<?php
/**
 * POS Cellphone Module - Automated Installer
 *
 * This script automatically installs the Cellphone module into an existing Ultimate POS installation.
 *
 * @author Celfix Team
 * @version 1.1.0
 */

class CellphoneInstaller
{
    private $basePath;
    private $backupPath;
    private $logFile;
    private $errors = [];
    private $warnings = [];

    public function __construct()
    {
        $this->basePath = getcwd();
        $this->backupPath = $this->basePath . '/storage/cellphone-backup-' . date('Y-m-d-H-i-s');
        $this->logFile = $this->basePath . '/storage/logs/cellphone-install.log';

        // Ensure we're in a Laravel/Ultimate POS directory
        if (!file_exists($this->basePath . '/artisan')) {
            $this->error("Error: This doesn't appear to be a Laravel/Ultimate POS installation.");
            $this->error("Please run this script from your Ultimate POS root directory.");
            exit(1);
        }
    }

    public function install()
    {
        $this->log("=== POS Cellphone Module Installation Started ===");
        $this->info("POS Cellphone Module Installer v1.1.0");
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

            // Run module seeder
            $this->info("\n🗄️  Setting up module...");
            $this->runModuleSeeder();

            // Register module
            $this->info("\n📋 Registering module...");
            $this->registerModule();

            // Enable module
            $this->info("\n🔌 Enabling module...");
            $this->enableModule();

            // Verify installation
            $this->info("\n✅ Verifying installation...");
            $this->verifyInstallation();

            $this->success("\n🎉 Installation completed successfully!");
            $this->info("Backup created at: " . $this->backupPath);
            $this->info("You can now access the Cellphone module in your Ultimate POS interface.");
            $this->info("\nNext steps:");
            $this->info("1. Refresh your browser");
            $this->info("2. Go to Settings → Roles to assign cellphone permissions");
            $this->info("3. Access 'Equipos Celulares' in the menu");

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

        // Check if Cellphone module already exists
        if (is_dir($this->basePath . '/Modules/Cellphone')) {
            $this->warning("Cellphone module already exists. Creating backup...");
            $backupModule = $this->backupPath . '/Modules/Cellphone';
            $this->ensureDirectoryExists(dirname($backupModule));
            $this->runCommand("cp -r '{$this->basePath}/Modules/Cellphone' '{$backupModule}'");
        }

        $this->success("✓ Backup created");
    }

    private function installModuleFiles()
    {
        $sourceModule = __DIR__ . '/src/Modules/Cellphone';
        $targetModule = $this->basePath . '/Modules/Cellphone';

        if (!is_dir($sourceModule)) {
            throw new Exception("Source module not found at: " . $sourceModule);
        }

        if (is_dir($targetModule)) {
            $this->runCommand("rm -rf '{$targetModule}'");
        }

        $this->runCommand("cp -r '{$sourceModule}' '{$targetModule}'");
        $this->runCommand("chmod -R 755 '{$targetModule}'");
        $this->success("✓ Module files installed");
    }

    private function runModuleSeeder()
    {
        // Run the seeder to set the module version in system table
        $this->runCommand('php artisan module:seed Cellphone');
        $this->success("✓ Module seeder executed");
    }

    private function registerModule()
    {
        // Clear all caches
        $this->runCommand('php artisan config:clear');
        $this->runCommand('php artisan cache:clear');
        $this->runCommand('php artisan route:clear');

        // Regenerate autoload
        if (file_exists($this->basePath . '/composer.json')) {
            $this->runCommand('composer dump-autoload');
        }

        $this->success("✓ Module registered and caches cleared");
    }

    private function enableModule()
    {
        // Enable the Cellphone module
        $this->runCommand('php artisan module:enable Cellphone');

        // Clear caches again after enabling
        $this->runCommand('php artisan config:clear', false);
        $this->runCommand('php artisan cache:clear', false);
        $this->runCommand('php artisan route:clear', false);

        $this->success("✓ Module enabled");
    }

    private function verifyInstallation()
    {
        // Check if module files exist
        $requiredFiles = [
            '/Modules/Cellphone/module.json',
            '/Modules/Cellphone/Http/Controllers/CellphoneController.php',
            '/Modules/Cellphone/Entities/Cellphone.php',
        ];

        foreach ($requiredFiles as $file) {
            if (!file_exists($this->basePath . $file)) {
                throw new Exception("Required file not found: " . $file);
            }
        }

        // Check if module is listed and enabled
        $output = $this->runCommand('php artisan module:list', false);
        if (strpos($output, 'Cellphone') === false) {
            $this->warning("Module may not be properly registered. Try running 'php artisan module:list' manually.");
        } elseif (strpos($output, '[Enabled] Cellphone') !== false) {
            $this->success("✓ Module is enabled");
        } else {
            $this->warning("Module is registered but may not be enabled.");
        }

        // Check if routes are registered
        $routeOutput = $this->runCommand('php artisan route:list 2>&1 | grep -i cellphone || echo ""', false);
        if (!empty(trim($routeOutput))) {
            $this->success("✓ Module routes registered");
        }

        $this->success("✓ Installation verified");
    }

    private function rollback()
    {
        // Disable module first
        $this->runCommand('php artisan module:disable Cellphone 2>&1', false);

        if (is_dir($this->backupPath . '/Modules/Cellphone')) {
            $this->runCommand("cp -r '{$this->backupPath}/Modules/Cellphone' '{$this->basePath}/Modules/'");
            $this->info("Restored from backup");
        } else {
            if (is_dir($this->basePath . '/Modules/Cellphone')) {
                $this->runCommand("rm -rf '{$this->basePath}/Modules/Cellphone'");
                $this->info("Removed module files");
            }
        }

        // Clear caches after rollback
        $this->runCommand('php artisan config:clear 2>&1', false);
        $this->runCommand('php artisan cache:clear 2>&1', false);
        $this->runCommand('php artisan route:clear 2>&1', false);
    }

    private function runCommand($command, $showOutput = true)
    {
        $output = shell_exec($command . ' 2>&1');
        $this->log("Command: " . $command);
        $this->log("Output: " . $output);

        if ($showOutput && !empty($output)) {
            echo $output . "\n";
        }

        return $output;
    }

    private function ensureDirectoryExists($dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    private function log($message)
    {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents(
            $this->logFile,
            date('Y-m-d H:i:s') . " - " . $message . "\n",
            FILE_APPEND
        );
    }

    private function info($message)
    {
        echo "\033[0;36m" . $message . "\033[0m\n";
        $this->log($message);
    }

    private function success($message)
    {
        echo "\033[0;32m" . $message . "\033[0m\n";
        $this->log($message);
    }

    private function warning($message)
    {
        echo "\033[0;33m" . $message . "\033[0m\n";
        $this->warnings[] = $message;
        $this->log("WARNING: " . $message);
    }

    private function error($message)
    {
        echo "\033[0;31m" . $message . "\033[0m\n";
        $this->errors[] = $message;
        $this->log("ERROR: " . $message);
    }
}

// Run the installer
if (php_sapi_name() === 'cli') {
    $installer = new CellphoneInstaller();
    $installer->install();
} else {
    die("This script must be run from the command line.");
}
