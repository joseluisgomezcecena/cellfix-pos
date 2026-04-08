<?php

namespace Modules\InventoryMultiLocation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventorymultilocation:install
                          {--force : Force installation even if already installed}
                          {--skip-migrations : Skip running database migrations}
                          {--skip-permissions : Skip setting up permissions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Inventory Multi-Location module';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🚀 Installing Inventory Multi-Location Module...');
        $this->line('');

        try {
            // Check if already installed
            if (!$this->option('force') && $this->isModuleInstalled()) {
                $this->warn('Module appears to be already installed. Use --force to reinstall.');
                return 1;
            }

            $this->checkRequirements();
            $this->runMigrations();
            $this->setupPermissions();
            $this->publishAssets();
            $this->clearCaches();

            $this->info('');
            $this->info('✅ Inventory Multi-Location module installed successfully!');
            $this->line('');
            $this->info('Next steps:');
            $this->line('1. Configure your business locations in the admin panel');
            $this->line('2. Set up user permissions for multi-location access');
            $this->line('3. Start managing your inventory across locations!');
            $this->line('');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Installation failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Check if module is already installed
     */
    protected function isModuleInstalled()
    {
        try {
            // Check if the inventory_transfers table exists
            return Schema::hasTable('inventory_transfers') &&
                   Schema::hasTable('inventory_transfer_items');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check system requirements
     */
    protected function checkRequirements()
    {
        $this->info('📋 Checking system requirements...');

        // Check PHP version
        $phpVersion = PHP_VERSION;
        if (version_compare($phpVersion, '8.0.0', '<')) {
            throw new \Exception("PHP 8.0.0 or higher is required. Current version: {$phpVersion}");
        }

        // Check required extensions
        $requiredExtensions = ['pdo', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath'];
        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                throw new \Exception("Required PHP extension '{$extension}' is not loaded.");
            }
        }

        // Check database connection
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }

        $this->info('✅ System requirements met');
    }

    /**
     * Run database migrations
     */
    protected function runMigrations()
    {
        if ($this->option('skip-migrations')) {
            $this->warn('⏭️  Skipping database migrations');
            return;
        }

        $this->info('💾 Running database migrations...');

        try {
            Artisan::call('migrate', [
                '--path' => 'Modules/InventoryMultiLocation/Database/Migrations',
                '--force' => true
            ]);

            $this->info('✅ Database migrations completed');
        } catch (\Exception $e) {
            throw new \Exception("Migration failed: " . $e->getMessage());
        }
    }

    /**
     * Setup module permissions
     */
    protected function setupPermissions()
    {
        if ($this->option('skip-permissions')) {
            $this->warn('⏭️  Skipping permission setup');
            return;
        }

        $this->info('🔐 Setting up permissions...');

        // The permissions are handled by the migration
        // Just verify they were created successfully
        $permissions = [
            'inventory_multi.view',
            'inventory_multi.transfer',
            'inventory_multi.manage',
            'inventory_multi.bulk_actions'
        ];

        $existingPermissions = DB::table('permissions')
            ->whereIn('name', $permissions)
            ->count();

        if ($existingPermissions < count($permissions)) {
            $this->warn('⚠️  Some permissions may not have been created properly.');
            $this->line('Please check the permissions migration.');
        } else {
            $this->info('✅ Permissions configured successfully');
        }
    }

    /**
     * Publish module assets
     */
    protected function publishAssets()
    {
        $this->info('📁 Publishing module assets...');

        try {
            Artisan::call('vendor:publish', [
                '--provider' => 'Modules\\InventoryMultiLocation\\Providers\\InventoryMultiLocationServiceProvider',
                '--force' => true
            ]);

            $this->info('✅ Assets published successfully');
        } catch (\Exception $e) {
            $this->warn('⚠️  Could not publish assets: ' . $e->getMessage());
        }
    }

    /**
     * Clear application caches
     */
    protected function clearCaches()
    {
        $this->info('🧹 Clearing application caches...');

        try {
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:clear');

            $this->info('✅ Caches cleared successfully');
        } catch (\Exception $e) {
            $this->warn('⚠️  Could not clear all caches: ' . $e->getMessage());
        }
    }
}