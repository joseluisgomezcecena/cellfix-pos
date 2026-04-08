<?php

namespace Modules\HideContactCustomFields\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hide-contact-fields:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install HideContactCustomFields module - backs up original views and publishes modified views';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Installing HideContactCustomFields module...');

        // Create backup directory
        $backupPath = storage_path('app/backups/contact_views');
        if (!File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
            $this->info('Created backup directory: ' . $backupPath);
        }

        // Backup original files
        $this->info('Backing up original view files...');

        $createViewPath = resource_path('views/contact/create.blade.php');
        $editViewPath = resource_path('views/contact/edit.blade.php');

        if (File::exists($createViewPath)) {
            File::copy($createViewPath, $backupPath . '/create.blade.php.backup');
            $this->info('Backed up: create.blade.php');
        } else {
            $this->warn('Original create.blade.php not found');
        }

        if (File::exists($editViewPath)) {
            File::copy($editViewPath, $backupPath . '/edit.blade.php.backup');
            $this->info('Backed up: edit.blade.php');
        } else {
            $this->warn('Original edit.blade.php not found');
        }

        // Copy modified views from module to application
        $this->info('Publishing modified views...');

        $moduleCreateView = module_path('HideContactCustomFields', 'Resources/views/contact/create.blade.php');
        $moduleEditView = module_path('HideContactCustomFields', 'Resources/views/contact/edit.blade.php');

        if (File::exists($moduleCreateView)) {
            File::copy($moduleCreateView, $createViewPath);
            $this->info('Published: create.blade.php (custom fields 6-10 removed)');
        } else {
            $this->error('Module create.blade.php not found!');
            return 1;
        }

        if (File::exists($moduleEditView)) {
            File::copy($moduleEditView, $editViewPath);
            $this->info('Published: edit.blade.php (custom fields 6-10 removed)');
        } else {
            $this->error('Module edit.blade.php not found!');
            return 1;
        }

        // Register module version in system table
        try {
            DB::table('system')->updateOrInsert(
                ['key' => 'hide_contact_custom_fields_version'],
                ['value' => '1.0.0']
            );
            $this->info('Registered module version in system table');
        } catch (\Exception $e) {
            $this->warn('Could not register module version: ' . $e->getMessage());
        }

        $this->info('');
        $this->info('✓ HideContactCustomFields module installed successfully!');
        $this->info('Custom fields 6-10 are now hidden from all contact forms.');
        $this->info('');
        $this->info('Next steps:');
        $this->info('  php artisan config:clear');
        $this->info('  php artisan cache:clear');
        $this->info('  php artisan view:clear');

        return 0;
    }
}
