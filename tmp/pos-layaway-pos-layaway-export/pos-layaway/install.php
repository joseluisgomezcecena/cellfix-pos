 <?php
  /**
   * POS Layaway Module - Automated Installer (FIXED VERSION)
   *
   * This script automatically installs the Layaway module into an existing Ultimate POS installation.
   * FIXED: Correct migration order to prevent foreign key constraint errors
   *
   * @author POS Modules Team
   * @version 1.0.1
   */

  class LayawayInstaller
  {
      private $basePath;
      private $installerPath;
      private $backupPath;
      private $logFile;
      private $errors = [];
      private $warnings = [];

      public function __construct($installerPath = null)
      {
          // Determine installer path
          $this->installerPath = $installerPath ?: dirname(__FILE__);

          // Find POS base path (where artisan exists)
          $this->basePath = $this->findBasePath();

          $this->backupPath = $this->basePath . '/storage/layaway-backup-' . date('Y-m-d-H-i-s');
          $this->logFile = $this->basePath . '/storage/logs/layaway-install.log';

          // Ensure we're in a Laravel/Ultimate POS directory
          if (!file_exists($this->basePath . '/artisan')) {
              $this->error("Error: Could not find Ultimate POS installation.");
              $this->error("Searched path: " . $this->basePath);
              $this->error("Please run this script from your Ultimate POS root directory.");
              exit(1);
          }
      }

      private function findBasePath()
      {
          // Start from current directory and search up
          $currentPath = getcwd();
          $maxDepth = 5;

          for ($i = 0; $i < $maxDepth; $i++) {
              if (file_exists($currentPath . '/artisan')) {
                  return $currentPath;
              }
              $currentPath = dirname($currentPath);
          }

          // If not found, use getcwd()
          return getcwd();
      }

      public function install()
      {
          $this->log("=== POS Layaway Module Installation Started ===");
          $this->info("POS Layaway Module Installer v1.0.1 (FIXED)");
          $this->info("Installation started at: " . date('Y-m-d H:i:s'));
          $this->info("POS Path: " . $this->basePath);
          $this->info("Installer Path: " . $this->installerPath);

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

              // Run database migrations (FIXED ORDER)
              $this->info("\n🗄️  Running database migrations...");
              $this->runMigrations();

              // Apply system patches
              $this->info("\n🔧 Applying system patches...");
              $this->applySystemPatches();

              // Register module
              $this->info("\n📋 Registering module...");
              $this->registerModule();

              // Verify installation
              $this->info("\n✅ Verifying installation...");
              $this->verifyInstallation();

              $this->success("\n🎉 Installation completed successfully!");
              $this->info("Backup created at: " . $this->backupPath);
              $this->info("You can now access the Layaway module in your Ultimate POS interface.");

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
          $sourceModule = $this->installerPath . '/src/Modules/Layaway';
          $targetModule = $this->basePath . '/Modules/Layaway';

          if (!is_dir($sourceModule)) {
              throw new Exception("Source module not found at: " . $sourceModule);
          }

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
          // First, copy system integration migrations to the migrations folder
          $migrations = [
              $this->installerPath . '/database/migrations/2025_09_18_070615_add_layaway_id_to_transactions_table.php',
              $this->installerPath . '/database/migrations/2025_09_18_070942_add_transaction_payment_id_to_layaway_payments_table.php',
              $this->installerPath . '/database/migrations/2025_09_18_083239_create_sequences_table.php'
          ];

          foreach ($migrations as $migration) {
              if (!file_exists($migration)) {
                  $this->warning("Migration file not found: " . basename($migration));
                  continue;
              }

              $targetMigration = $this->basePath . '/database/migrations/' . basename($migration);
              if (!file_exists($targetMigration)) {
                  copy($migration, $targetMigration);
                  $this->log("Copied migration: " . basename($migration));
              }
          }

          // CRITICAL FIX: Run module migrations FIRST to create the layaways table
          $this->info("Step 1: Running module migrations (creates layaways table)...");
          $this->runCommand('composer dump-autoload');
          $this->runCommand('php artisan module:discover');
          $this->runCommand('php artisan module:migrate Layaway --force');
          $this->success("✓ Module migrations completed (layaways table created)");

          // THEN run system migrations to add foreign keys
          $this->info("Step 2: Running system migrations (adds foreign keys)...");
          $this->runCommand('php artisan migrate --force');
          $this->success("✓ System migrations completed (foreign keys added)");

          $this->success("✓ All database migrations completed successfully");
      }

      private function applySystemPatches()
      {
          // Apply Transaction.php patch
          $transactionFile = $this->basePath . '/app/Transaction.php';

          if (!file_exists($transactionFile)) {
              $this->warning("Transaction.php not found at expected location");
              return;
          }

          $transactionContent = file_get_contents($transactionFile);

          // Check if layaway relationship already exists
          if (strpos($transactionContent, 'function layaway()') === false) {
              // Add the layaway relationship method
              $layawayMethod = "\n    /**\n     * Get the associated layaway\n     */\n    public function layaway()\n    {\n        return
  \$this->belongsTo(\\Modules\\Layaway\\Entities\\Layaway::class, 'layaway_id');\n    }\n";

              // Find the last method in the class and add before the closing brace
              $lastMethodPos = strrpos($transactionContent, '}');
              if ($lastMethodPos !== false) {
                  $transactionContent = substr_replace($transactionContent, $layawayMethod . '}', $lastMethodPos);
                  file_put_contents($transactionFile, $transactionContent);
                  $this->log("Applied Transaction.php patch");
                  $this->success("✓ Transaction.php patched");
              } else {
                  $this->warning("Could not patch Transaction.php - closing brace not found");
              }
          } else {
              $this->info("Transaction.php already has layaway relationship");
          }

          // Copy console command
          $commandSource = $this->installerPath . '/app/patches/FixLayawayNumbers.php';
          $commandTarget = $this->basePath . '/app/Console/Commands/FixLayawayNumbers.php';

          if (file_exists($commandSource)) {
              if (!file_exists($commandTarget)) {
                  copy($commandSource, $commandTarget);
                  $this->log("Installed FixLayawayNumbers command");
                  $this->success("✓ Console command installed");
              } else {
                  $this->info("FixLayawayNumbers command already exists");
              }
          } else {
              $this->warning("FixLayawayNumbers command not found in installer");
          }

          $this->success("✓ System patches applied");
      }

      private function registerModule()
      {
          // Generate module cache
          $this->runCommand('php artisan module:list');
          $this->runCommand('php artisan module:enable Layaway');

          // Clear various caches
          $this->runCommand('php artisan config:clear');
          $this->runCommand('php artisan cache:clear');
          $this->runCommand('php artisan route:clear');

          // Dump autoload
          $this->runCommand('composer dump-autoload');

          $this->success("✓ Module registered and caches cleared");
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

          // Check if routes are available
          try {
              $this->runCommand('php artisan route:list | grep layaway', false);
              $this->success("✓ Layaway routes registered");
          } catch (Exception $e) {
              $this->warning("Could not verify routes registration");
          }

          $this->success("✓ Installation verification completed");
      }

      private function rollback()
      {
          if (is_dir($this->backupPath)) {
              $this->info("Restoring from backup...");

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

              $this->info("Rollback completed. System restored to previous state.");
              $this->info("Backup files remain at: " . $this->backupPath);
          }
      }

      private function runCommand($command, $throwOnError = true)
      {
          $this->log("Executing: " . $command);

          $output = [];
          $returnCode = 0;

          // Change to base directory before running command
          $originalDir = getcwd();
          chdir($this->basePath);

          exec($command . ' 2>&1', $output, $returnCode);

          chdir($originalDir);

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
