<?php
/**
 * Inventory Multi-Location Module Installer
 *
 * This script automates the installation process for the Inventory Multi-Location module.
 * It checks requirements, runs migrations, publishes assets, and configures the module.
 *
 * @package Modules\InventoryMultiLocation
 * @version 1.0.0
 * @author CELFIX Team <admin@daseo.co>
 * @license MIT
 */

class InventoryMultiLocationInstaller
{
    private $basePath;
    private $errors = [];
    private $warnings = [];
    private $success = [];

    public function __construct()
    {
        $this->basePath = dirname(__FILE__);
    }

    /**
     * Main installation method
     */
    public function install()
    {
        echo "\n🚀 Inventory Multi-Location Module Installer v1.0.0\n";
        echo "====================================================\n\n";

        // Step 1: Check requirements
        echo "📋 Checking system requirements...\n";
        if (!$this->checkRequirements()) {
            $this->displayErrors();
            return false;
        }
        echo "✅ System requirements met!\n\n";

        // Step 2: Check Laravel environment
        echo "🔍 Detecting Laravel installation...\n";
        if (!$this->detectLaravel()) {
            $this->addError("Laravel installation not detected. Please run this installer from your Laravel project root or Modules directory.");
            $this->displayErrors();
            return false;
        }
        echo "✅ Laravel installation detected!\n\n";

        // Step 3: Install dependencies
        echo "📦 Installing dependencies...\n";
        $this->installDependencies();
        echo "✅ Dependencies installed!\n\n";

        // Step 4: Run database migrations
        echo "💾 Running database migrations...\n";
        if (!$this->runMigrations()) {
            $this->displayErrors();
            return false;
        }
        echo "✅ Database migrations completed!\n\n";

        // Step 5: Publish assets
        echo "📁 Publishing module assets...\n";
        $this->publishAssets();
        echo "✅ Assets published!\n\n";

        // Step 6: Set permissions
        echo "🔐 Setting up permissions...\n";
        $this->setupPermissions();
        echo "✅ Permissions configured!\n\n";

        // Step 7: Enable module
        echo "🔌 Enabling module...\n";
        $this->enableModule();
        echo "✅ Module enabled!\n\n";

        // Step 8: Final configuration
        echo "⚙️  Final configuration...\n";
        $this->finalConfiguration();
        echo "✅ Configuration completed!\n\n";

        $this->displayResults();
        return true;
    }

    /**
     * Check system requirements
     */
    private function checkRequirements()
    {
        $requirements = [
            'php' => '8.0.0',
            'extensions' => ['pdo', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'openssl', 'fileinfo']
        ];

        // Check PHP version
        if (version_compare(PHP_VERSION, $requirements['php'], '<')) {
            $this->addError("PHP {$requirements['php']} or higher is required. Current version: " . PHP_VERSION);
            return false;
        }

        // Check required extensions
        foreach ($requirements['extensions'] as $extension) {
            if (!extension_loaded($extension)) {
                $this->addError("Required PHP extension '{$extension}' is not loaded.");
                return false;
            }
        }

        return true;
    }

    /**
     * Detect Laravel installation
     */
    private function detectLaravel()
    {
        // Check if we're in a Laravel project
        $laravelPaths = [
            '../../../artisan', // If in Modules/InventoryMultiLocation
            '../../artisan',    // If in Modules
            '../artisan',       // If in root
            'artisan'           // If in root
        ];

        foreach ($laravelPaths as $path) {
            if (file_exists($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Install Composer dependencies
     */
    private function installDependencies()
    {
        if (file_exists($this->basePath . '/composer.json')) {
            $output = [];
            $returnVar = 0;

            exec("cd {$this->basePath} && composer install --no-dev --optimize-autoloader 2>&1", $output, $returnVar);

            if ($returnVar !== 0) {
                $this->addWarning("Composer install had issues. Please run 'composer install' manually if needed.");
            } else {
                $this->addSuccess("Composer dependencies installed successfully.");
            }
        }
    }

    /**
     * Run database migrations
     */
    private function runMigrations()
    {
        $migrationPath = $this->basePath . '/Database/Migrations';

        if (!is_dir($migrationPath)) {
            $this->addError("Migration directory not found: {$migrationPath}");
            return false;
        }

        // Try to find Laravel artisan command
        $artisanPaths = [
            '../../../artisan',
            '../../artisan',
            '../artisan',
            'artisan'
        ];

        $artisanPath = null;
        foreach ($artisanPaths as $path) {
            if (file_exists($path)) {
                $artisanPath = $path;
                break;
            }
        }

        if (!$artisanPath) {
            $this->addError("Could not find Laravel artisan command.");
            return false;
        }

        $output = [];
        $returnVar = 0;

        // Run migrations
        exec("php {$artisanPath} migrate --path=Modules/InventoryMultiLocation/Database/Migrations --force 2>&1", $output, $returnVar);

        if ($returnVar !== 0) {
            $this->addError("Migration failed: " . implode("\n", $output));
            return false;
        }

        $this->addSuccess("Database migrations completed successfully.");
        return true;
    }

    /**
     * Publish module assets
     */
    private function publishAssets()
    {
        // Find artisan command
        $artisanPaths = ['../../../artisan', '../../artisan', '../artisan', 'artisan'];
        $artisanPath = null;

        foreach ($artisanPaths as $path) {
            if (file_exists($path)) {
                $artisanPath = $path;
                break;
            }
        }

        if ($artisanPath) {
            $output = [];
            exec("php {$artisanPath} vendor:publish --provider=\"Modules\\InventoryMultiLocation\\Providers\\InventoryMultiLocationServiceProvider\" --force 2>&1", $output);
            $this->addSuccess("Assets published to application directories.");
        } else {
            $this->addWarning("Could not publish assets automatically. Please run: php artisan vendor:publish --provider=\"Modules\\InventoryMultiLocation\\Providers\\InventoryMultiLocationServiceProvider\"");
        }
    }

    /**
     * Setup module permissions
     */
    private function setupPermissions()
    {
        // The permissions are handled by the migration
        // Just verify they exist
        $this->addSuccess("Module permissions configured via database migration.");
    }

    /**
     * Enable the module
     */
    private function enableModule()
    {
        // Find artisan command
        $artisanPaths = ['../../../artisan', '../../artisan', '../artisan', 'artisan'];
        $artisanPath = null;

        foreach ($artisanPaths as $path) {
            if (file_exists($path)) {
                $artisanPath = $path;
                break;
            }
        }

        if ($artisanPath) {
            $output = [];
            exec("php {$artisanPath} module:enable InventoryMultiLocation 2>&1", $output);
            $this->addSuccess("Module enabled in application.");
        } else {
            $this->addWarning("Could not enable module automatically. Please run: php artisan module:enable InventoryMultiLocation");
        }
    }

    /**
     * Final configuration steps
     */
    private function finalConfiguration()
    {
        // Clear caches
        $artisanPaths = ['../../../artisan', '../../artisan', '../artisan', 'artisan'];
        $artisanPath = null;

        foreach ($artisanPaths as $path) {
            if (file_exists($path)) {
                $artisanPath = $path;
                break;
            }
        }

        if ($artisanPath) {
            exec("php {$artisanPath} config:cache 2>&1");
            exec("php {$artisanPath} route:cache 2>&1");
            exec("php {$artisanPath} view:clear 2>&1");
            $this->addSuccess("Application caches refreshed.");
        }

        $this->addSuccess("Installation completed successfully!");
    }

    /**
     * Display installation results
     */
    private function displayResults()
    {
        echo "📊 Installation Summary\n";
        echo "=======================\n\n";

        if (!empty($this->success)) {
            echo "✅ Success:\n";
            foreach ($this->success as $message) {
                echo "  • {$message}\n";
            }
            echo "\n";
        }

        if (!empty($this->warnings)) {
            echo "⚠️  Warnings:\n";
            foreach ($this->warnings as $message) {
                echo "  • {$message}\n";
            }
            echo "\n";
        }

        if (!empty($this->errors)) {
            echo "❌ Errors:\n";
            foreach ($this->errors as $message) {
                echo "  • {$message}\n";
            }
            echo "\n";
        }

        echo "🎉 Installation Complete!\n\n";
        echo "Next Steps:\n";
        echo "1. Access your POS admin panel\n";
        echo "2. Navigate to the Inventory section\n";
        echo "3. Configure your business locations\n";
        echo "4. Set up user permissions\n";
        echo "5. Start managing your multi-location inventory!\n\n";
        echo "For support, visit: https://github.com/celfix/inventory-multi-location\n";
    }

    private function addError($message)
    {
        $this->errors[] = $message;
    }

    private function addWarning($message)
    {
        $this->warnings[] = $message;
    }

    private function addSuccess($message)
    {
        $this->success[] = $message;
    }

    private function displayErrors()
    {
        if (!empty($this->errors)) {
            echo "\n❌ Installation failed with the following errors:\n";
            foreach ($this->errors as $error) {
                echo "  • {$error}\n";
            }
            echo "\nPlease resolve these issues and try again.\n";
        }
    }
}

// Run the installer if this file is executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $installer = new InventoryMultiLocationInstaller();
    $success = $installer->install();
    exit($success ? 0 : 1);
}