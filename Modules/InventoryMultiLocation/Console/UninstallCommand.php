<?php

namespace Modules\InventoryMultiLocation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UninstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventorymultilocation:uninstall
                          {--force : Force uninstallation without confirmation}
                          {--keep-data : Keep database data (only remove permissions)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uninstall the Inventory Multi-Location module';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->error('🗑️  Uninstalling Inventory Multi-Location Module...');
        $this->line('');

        // Confirmation check
        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to uninstall the Inventory Multi-Location module?')) {
                $this->info('Uninstallation cancelled.');
                return 1;
            }

            if (!$this->option('keep-data')) {
                $this->error('⚠️  WARNING: This will permanently delete all inventory transfer data!');
                if (!$this->confirm('Continue with data deletion?')) {
                    $this->info('Uninstallation cancelled.');
                    return 1;
                }
            }
        }

        try {
            $this->disableModule();

            if (!$this->option('keep-data')) {
                $this->removeData();
                $this->rollbackMigrations();
            }

            $this->removePermissions();
            $this->clearCaches();

            $this->info('');
            $this->info('✅ Inventory Multi-Location module uninstalled successfully!');
            $this->line('');
            if ($this->option('keep-data')) {
                $this->info('Note: Database tables and data were preserved.');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Uninstallation failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Disable the module
     */
    protected function disableModule()
    {
        $this->info('🔌 Disabling module...');

        try {
            Artisan::call('module:disable', ['module' => 'InventoryMultiLocation']);
            $this->info('✅ Module disabled');
        } catch (\Exception $e) {
            $this->warn('⚠️  Could not disable module: ' . $e->getMessage());
        }
    }

    /**
     * Remove module data
     */
    protected function removeData()
    {
        $this->info('🗃️  Removing module data...');

        try {
            // Remove transfer items first due to foreign key constraints
            if (Schema::hasTable('inventory_transfer_items')) {
                DB::table('inventory_transfer_items')->delete();
                $this->line('  • Inventory transfer items removed');
            }

            // Remove transfers
            if (Schema::hasTable('inventory_transfers')) {
                DB::table('inventory_transfers')->delete();
                $this->line('  • Inventory transfers removed');
            }

            $this->info('✅ Module data removed');
        } catch (\Exception $e) {
            throw new \Exception("Could not remove module data: " . $e->getMessage());
        }
    }

    /**
     * Rollback database migrations
     */
    protected function rollbackMigrations()
    {
        $this->info('💾 Rolling back database migrations...');

        try {
            // Get migration files in reverse order
            $migrationPath = base_path('Modules/InventoryMultiLocation/Database/Migrations');
            $migrations = [];

            if (is_dir($migrationPath)) {
                $files = scandir($migrationPath);
                foreach ($files as $file) {
                    if (strpos($file, '.php') !== false) {
                        $migrations[] = str_replace('.php', '', $file);
                    }
                }
            }

            // Sort in reverse order for rollback
            rsort($migrations);

            foreach ($migrations as $migration) {
                try {
                    Artisan::call('migrate:rollback', [
                        '--path' => 'Modules/InventoryMultiLocation/Database/Migrations/' . $migration . '.php'
                    ]);
                } catch (\Exception $e) {
                    $this->warn("Could not rollback migration {$migration}: " . $e->getMessage());
                }
            }

            $this->info('✅ Database migrations rolled back');
        } catch (\Exception $e) {
            $this->warn('⚠️  Could not rollback all migrations: ' . $e->getMessage());
        }
    }

    /**
     * Remove module permissions
     */
    protected function removePermissions()
    {
        $this->info('🔐 Removing permissions...');

        try {
            $permissions = [
                'inventory_multi.view',
                'inventory_multi.transfer',
                'inventory_multi.manage',
                'inventory_multi.bulk_actions'
            ];

            // Remove from role_has_permissions
            DB::table('role_has_permissions')
                ->whereIn('permission_id', function($query) use ($permissions) {
                    $query->select('id')
                          ->from('permissions')
                          ->whereIn('name', $permissions);
                })
                ->delete();

            // Remove from model_has_permissions
            DB::table('model_has_permissions')
                ->whereIn('permission_id', function($query) use ($permissions) {
                    $query->select('id')
                          ->from('permissions')
                          ->whereIn('name', $permissions);
                })
                ->delete();

            // Remove permissions
            $deletedCount = DB::table('permissions')
                ->whereIn('name', $permissions)
                ->delete();

            $this->info("✅ Removed {$deletedCount} permissions");
        } catch (\Exception $e) {
            $this->warn('⚠️  Could not remove permissions: ' . $e->getMessage());
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