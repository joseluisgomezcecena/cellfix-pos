<?php

namespace Modules\HideContactCustomFields\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class UninstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hide-contact-fields:uninstall';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uninstall HideContactCustomFields module - restores original views';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Uninstalling HideContactCustomFields module...');

        if (!$this->confirm('This will restore the original contact views with all 10 custom fields. Continue?')) {
            $this->info('Uninstallation cancelled.');
            return 0;
        }

        // Check for backup files
        $backupPath = storage_path('app/backups/contact_views');

        if (!File::exists($backupPath)) {
            $this->error('Backup directory not found! Cannot restore original views.');
            $this->info('Location expected: ' . $backupPath);
            return 1;
        }

        $createBackup = $backupPath . '/create.blade.php.backup';
        $editBackup = $backupPath . '/edit.blade.php.backup';

        if (!File::exists($createBackup) || !File::exists($editBackup)) {
            $this->error('Backup files not found! Cannot restore original views.');
            $this->info('Expected files:');
            $this->info('  - ' . $createBackup);
            $this->info('  - ' . $editBackup);
            return 1;
        }

        // Restore original files
        $this->info('Restoring original view files...');

        $createViewPath = resource_path('views/contact/create.blade.php');
        $editViewPath = resource_path('views/contact/edit.blade.php');

        try {
            File::copy($createBackup, $createViewPath);
            $this->info('Restored: create.blade.php');

            File::copy($editBackup, $editViewPath);
            $this->info('Restored: edit.blade.php');
        } catch (\Exception $e) {
            $this->error('Error restoring files: ' . $e->getMessage());
            return 1;
        }

        // Remove module version from system table
        try {
            DB::table('system')->where('key', 'hide_contact_custom_fields_version')->delete();
            $this->info('Removed module version from system table');
        } catch (\Exception $e) {
            $this->warn('Could not remove module version: ' . $e->getMessage());
        }

        // Optionally remove backup files
        if ($this->confirm('Do you want to delete the backup files?', false)) {
            File::deleteDirectory($backupPath);
            $this->info('Backup files deleted.');
        } else {
            $this->info('Backup files retained at: ' . $backupPath);
        }

        $this->info('');
        $this->info('✓ HideContactCustomFields module uninstalled successfully!');
        $this->info('Original contact views with all 10 custom fields have been restored.');
        $this->info('');
        $this->info('Next steps:');
        $this->info('  php artisan config:clear');
        $this->info('  php artisan cache:clear');
        $this->info('  php artisan view:clear');

        return 0;
    }
}
