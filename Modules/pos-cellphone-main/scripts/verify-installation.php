<?php
/**
 * POS Cellphone Module - Installation Verification Script
 *
 * This script verifies that the Cellphone module has been installed correctly.
 *
 * @author Celfix Team
 * @version 1.0.0
 */

class CellphoneVerifier
{
    private $basePath;
    private $passed = 0;
    private $failed = 0;
    private $warnings = 0;

    public function __construct()
    {
        $this->basePath = getcwd();

        // Ensure we're in a Laravel/Ultimate POS directory
        if (!file_exists($this->basePath . '/artisan')) {
            $this->error("Error: This doesn't appear to be a Laravel/Ultimate POS installation.");
            exit(1);
        }
    }

    public function verify()
    {
        $this->info("🔍 POS Cellphone Module - Installation Verification");
        $this->info("Verification started at: " . date('Y-m-d H:i:s'));
        $this->info(str_repeat("=", 60));

        // Run all verification tests
        $this->verifyModuleFiles();
        $this->verifySystemIntegration();
        $this->verifyRoutes();
        $this->verifyPermissions();

        // Summary
        $this->info(str_repeat("=", 60));
        $this->info("📊 Verification Summary:");
        $this->success("✅ Passed: " . $this->passed);

        if ($this->failed > 0) {
            $this->error("❌ Failed: " . $this->failed);
        }

        if ($this->warnings > 0) {
            $this->warning("⚠️  Warnings: " . $this->warnings);
        }

        if ($this->failed === 0) {
            $this->success("\n🎉 All verifications passed! The Cellphone module is properly installed.");
        } else {
            $this->error("\n❌ Some verifications failed. Please check the issues above.");
            exit(1);
        }
    }

    private function verifyModuleFiles()
    {
        $this->info("\n📁 Verifying module files...");

        $requiredFiles = [
            '/Modules/Cellphone/module.json',
            '/Modules/Cellphone/composer.json',
            '/Modules/Cellphone/Http/Controllers/CellphoneController.php',
            '/Modules/Cellphone/Http/Controllers/DataController.php',
            '/Modules/Cellphone/Entities/Cellphone.php',
            '/Modules/Cellphone/Resources/views/index.blade.php',
            '/Modules/Cellphone/Config/config.php',
            '/Modules/Cellphone/Database/Seeders/CellphoneDatabaseSeeder.php',
        ];

        foreach ($requiredFiles as $file) {
            if (file_exists($this->basePath . $file)) {
                $this->passed++;
                $this->success("  ✓ " . $file);
            } else {
                $this->failed++;
                $this->error("  ✗ Missing: " . $file);
            }
        }
    }

    private function verifySystemIntegration()
    {
        $this->info("\n🔗 Verifying system integration...");

        // Check if module is listed
        $output = shell_exec('php artisan module:list 2>&1');
        if (strpos($output, 'Cellphone') !== false) {
            $this->passed++;
            $this->success("  ✓ Module appears in module list");
        } else {
            $this->failed++;
            $this->error("  ✗ Module not found in module list");
        }

        // Check module version in system table
        $check = shell_exec('php artisan tinker --execute="echo App\\System::getProperty(\'cellphone_version\') ?? \'NOT_FOUND\';" 2>&1');
        if (strpos($check, 'NOT_FOUND') === false && !empty(trim($check))) {
            $this->passed++;
            $this->success("  ✓ Module version registered in system table: " . trim($check));
        } else {
            $this->failed++;
            $this->error("  ✗ Module version not found in system table");
        }
    }

    private function verifyRoutes()
    {
        $this->info("\n🛣️  Verifying routes...");

        $output = shell_exec('php artisan route:list --name=cellphone 2>&1');

        if (strpos($output, 'cellphone') !== false) {
            $this->passed++;
            $this->success("  ✓ Cellphone routes registered");
        } else {
            $this->warnings++;
            $this->warning("  ⚠ Cellphone routes may not be registered. Try clearing route cache.");
        }
    }

    private function verifyPermissions()
    {
        $this->info("\n🔐 Verifying permissions...");

        // Check if DataController has permission methods
        $dataController = $this->basePath . '/Modules/Cellphone/Http/Controllers/DataController.php';

        if (file_exists($dataController)) {
            $content = file_get_contents($dataController);

            if (strpos($content, 'user_permissions') !== false) {
                $this->passed++;
                $this->success("  ✓ Permission definitions found");
            } else {
                $this->warnings++;
                $this->warning("  ⚠ Permission definitions not found");
            }

            if (strpos($content, 'modifyAdminMenu') !== false) {
                $this->passed++;
                $this->success("  ✓ Menu integration found");
            } else {
                $this->failed++;
                $this->error("  ✗ Menu integration not found");
            }
        }
    }

    private function info($message)
    {
        echo "\033[0;36m" . $message . "\033[0m\n";
    }

    private function success($message)
    {
        echo "\033[0;32m" . $message . "\033[0m\n";
    }

    private function warning($message)
    {
        echo "\033[0;33m" . $message . "\033[0m\n";
        $this->warnings++;
    }

    private function error($message)
    {
        echo "\033[0;31m" . $message . "\033[0m\n";
    }
}

// Run the verifier
if (php_sapi_name() === 'cli') {
    $verifier = new CellphoneVerifier();
    $verifier->verify();
} else {
    die("This script must be run from the command line.");
}
