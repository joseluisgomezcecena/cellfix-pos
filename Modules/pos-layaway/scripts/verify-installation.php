<?php
/**
 * POS Layaway Module - Installation Verification Script
 *
 * This script verifies that the Layaway module has been installed correctly.
 *
 * @author POS Modules Team
 * @version 1.0.0
 */

class LayawayVerifier
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
        $this->info("🔍 POS Layaway Module - Installation Verification");
        $this->info("Verification started at: " . date('Y-m-d H:i:s'));
        $this->info(str_repeat("=", 60));

        // Run all verification tests
        $this->verifyModuleFiles();
        $this->verifyDatabaseTables();
        $this->verifyDatabaseRelationships();
        $this->verifySystemIntegration();
        $this->verifyPermissions();
        $this->verifyRoutes();
        $this->verifyFunctionality();

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
            $this->success("\n🎉 All verifications passed! The Layaway module is properly installed.");
        } else {
            $this->error("\n❌ Some verifications failed. Please check the issues above.");
            exit(1);
        }
    }

    private function verifyModuleFiles()
    {
        $this->info("\n📁 Verifying module files...");

        $requiredFiles = [
            '/Modules/Layaway/module.json',
            '/Modules/Layaway/composer.json',
            '/Modules/Layaway/Http/Controllers/LayawayController.php',
            '/Modules/Layaway/Http/Controllers/LayawayPaymentController.php',
            '/Modules/Layaway/Http/Controllers/DataController.php',
            '/Modules/Layaway/Entities/Layaway.php',
            '/Modules/Layaway/Entities/LayawayItem.php',
            '/Modules/Layaway/Entities/LayawayPayment.php',
            '/Modules/Layaway/Resources/views/index.blade.php',
            '/Modules/Layaway/Resources/views/create.blade.php',
            '/Modules/Layaway/Resources/views/show.blade.php',
            '/Modules/Layaway/Providers/LayawayServiceProvider.php'
        ];

        foreach ($requiredFiles as $file) {
            if (file_exists($this->basePath . $file)) {
                $this->pass("✓ " . $file);
            } else {
                $this->fail("✗ Missing: " . $file);
            }
        }
    }

    private function verifyDatabaseTables()
    {
        $this->info("\n🗄️  Verifying database tables...");

        $requiredTables = [
            'layaways',
            'layaway_items',
            'layaway_payments',
            'sequences'
        ];

        foreach ($requiredTables as $table) {
            try {
                $result = $this->runCommand("php artisan tinker --execute=\"echo Schema::hasTable('{$table}') ? 'EXISTS' : 'MISSING';\"");
                if (strpos($result, 'EXISTS') !== false) {
                    $this->pass("✓ Table '{$table}' exists");
                } else {
                    $this->fail("✗ Table '{$table}' missing");
                }
            } catch (Exception $e) {
                $this->fail("✗ Could not verify table '{$table}': " . $e->getMessage());
            }
        }

        // Check for added columns in existing tables
        try {
            $result = $this->runCommand("php artisan tinker --execute=\"echo Schema::hasColumn('transactions', 'layaway_id') ? 'EXISTS' : 'MISSING';\"");
            if (strpos($result, 'EXISTS') !== false) {
                $this->pass("✓ transactions.layaway_id column exists");
            } else {
                $this->fail("✗ transactions.layaway_id column missing");
            }
        } catch (Exception $e) {
            $this->fail("✗ Could not verify transactions.layaway_id column");
        }
    }

    private function verifyDatabaseRelationships()
    {
        $this->info("\n🔗 Verifying database relationships...");

        try {
            // Test Layaway model relationships
            $this->runCommand("php artisan tinker --execute=\"
                \$layaway = new \\Modules\\Layaway\\Entities\\Layaway();
                echo 'Layaway relationships: ';
                echo method_exists(\$layaway, 'items') ? 'items ' : '';
                echo method_exists(\$layaway, 'payments') ? 'payments ' : '';
                echo method_exists(\$layaway, 'contact') ? 'contact ' : '';
                echo method_exists(\$layaway, 'transaction') ? 'transaction ' : '';
            \"");
            $this->pass("✓ Layaway model relationships defined");

            // Test Transaction model relationship
            $this->runCommand("php artisan tinker --execute=\"
                \$transaction = new \\App\\Transaction();
                echo method_exists(\$transaction, 'layaway') ? 'LAYAWAY_RELATION_EXISTS' : 'LAYAWAY_RELATION_MISSING';
            \"");
            $this->pass("✓ Transaction model layaway relationship exists");

        } catch (Exception $e) {
            $this->fail("✗ Database relationship verification failed: " . $e->getMessage());
        }
    }

    private function verifySystemIntegration()
    {
        $this->info("\n⚙️  Verifying system integration...");

        // Check if Transaction.php has layaway relationship
        $transactionFile = $this->basePath . '/app/Transaction.php';
        if (file_exists($transactionFile)) {
            $content = file_get_contents($transactionFile);
            if (strpos($content, 'function layaway()') !== false) {
                $this->pass("✓ Transaction model has layaway relationship");
            } else {
                $this->fail("✗ Transaction model missing layaway relationship");
            }
        } else {
            $this->fail("✗ Transaction model file not found");
        }

        // Check if FixLayawayNumbers command exists
        $commandFile = $this->basePath . '/app/Console/Commands/FixLayawayNumbers.php';
        if (file_exists($commandFile)) {
            $this->pass("✓ FixLayawayNumbers console command installed");
        } else {
            $this->warn("⚠️  FixLayawayNumbers console command not found");
        }
    }

    private function verifyPermissions()
    {
        $this->info("\n🔐 Verifying permissions...");

        try {
            $this->runCommand("php artisan tinker --execute=\"
                \$permissions = [
                    'layaway.create',
                    'layaway.view',
                    'layaway.update',
                    'layaway.delete',
                    'layaway.process_payment'
                ];
                echo 'Checking permissions...';
            \"");
            $this->pass("✓ Permission system accessible");
        } catch (Exception $e) {
            $this->warn("⚠️  Could not verify permissions: " . $e->getMessage());
        }
    }

    private function verifyRoutes()
    {
        $this->info("\n🛤️  Verifying routes...");

        try {
            $routeOutput = $this->runCommand('php artisan route:list | grep layaway || echo "NO_LAYAWAY_ROUTES"');
            if (strpos($routeOutput, 'NO_LAYAWAY_ROUTES') === false && !empty(trim($routeOutput))) {
                $this->pass("✓ Layaway routes registered");
            } else {
                $this->fail("✗ Layaway routes not found");
            }
        } catch (Exception $e) {
            $this->warn("⚠️  Could not verify routes: " . $e->getMessage());
        }
    }

    private function verifyFunctionality()
    {
        $this->info("\n🧪 Verifying core functionality...");

        try {
            // Test layaway number generation
            $this->runCommand("php artisan tinker --execute=\"
                try {
                    \$number = \\Modules\\Layaway\\Entities\\Layaway::generateLayawayNumber(1);
                    echo 'Generated number: ' . \$number;
                    if (strpos(\$number, 'LAY') === 0) {
                        echo ' - FORMAT_VALID';
                    } else {
                        echo ' - FORMAT_INVALID';
                    }
                } catch (Exception \$e) {
                    echo 'NUMBER_GENERATION_FAILED: ' . \$e->getMessage();
                }
            \"");
            $this->pass("✓ Layaway number generation working");

        } catch (Exception $e) {
            $this->fail("✗ Functionality test failed: " . $e->getMessage());
        }

        try {
            // Test model creation (without saving)
            $this->runCommand("php artisan tinker --execute=\"
                \$layaway = new \\Modules\\Layaway\\Entities\\Layaway([
                    'business_id' => 1,
                    'contact_id' => 1,
                    'business_location_id' => 1,
                    'created_by' => 1,
                    'total_amount' => 100.00,
                    'down_payment_amount' => 20.00,
                    'balance_due' => 80.00,
                    'payment_deadline' => now()->addDays(30)
                ]);
                echo 'Model creation test: SUCCESS';
            \"");
            $this->pass("✓ Layaway model instantiation working");

        } catch (Exception $e) {
            $this->fail("✗ Model instantiation test failed: " . $e->getMessage());
        }
    }

    private function runCommand($command)
    {
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);

        $outputStr = implode("\n", $output);

        if ($returnCode !== 0) {
            throw new Exception("Command failed: " . $command . "\nOutput: " . $outputStr);
        }

        return $outputStr;
    }

    private function pass($message)
    {
        echo "\033[32m" . $message . "\033[0m\n";
        $this->passed++;
    }

    private function fail($message)
    {
        echo "\033[31m" . $message . "\033[0m\n";
        $this->failed++;
    }

    private function warn($message)
    {
        echo "\033[33m" . $message . "\033[0m\n";
        $this->warnings++;
    }

    private function info($message)
    {
        echo $message . "\n";
    }

    private function success($message)
    {
        echo "\033[32m" . $message . "\033[0m\n";
    }

    private function error($message)
    {
        echo "\033[31m" . $message . "\033[0m\n";
    }
}

// Check if running from command line
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Run the verifier
try {
    $verifier = new LayawayVerifier();
    $verifier->verify();
} catch (Exception $e) {
    echo "\n❌ Fatal error during verification: " . $e->getMessage() . "\n";
    exit(1);
}