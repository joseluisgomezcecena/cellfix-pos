<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Layaway\Entities\Layaway;
use Illuminate\Support\Facades\DB;

class FixLayawayNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'layaway:fix-numbers
                          {--dry-run : Show what would be changed without making changes}
                          {--force : Force the operation to run in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix duplicate layaway numbers and ensure uniqueness';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('This command cannot be run in production without the --force flag');
            return 1;
        }

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in dry-run mode. No changes will be made.');
        }

        // Find duplicate layaway numbers
        $duplicates = Layaway::selectRaw('layaway_number, COUNT(*) as count, GROUP_CONCAT(id) as ids')
                            ->groupBy('layaway_number')
                            ->having('count', '>', 1)
                            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('No duplicate layaway numbers found!');
            return 0;
        }

        $this->warn('Found ' . $duplicates->count() . ' duplicate layaway numbers:');

        foreach ($duplicates as $duplicate) {
            $this->line("Layaway Number: {$duplicate->layaway_number} (Count: {$duplicate->count})");
            $this->line("IDs: {$duplicate->ids}");

            if (!$dryRun) {
                $this->fixDuplicateLayawayNumber($duplicate->layaway_number, explode(',', $duplicate->ids));
            }
        }

        if (!$dryRun) {
            $this->info('Fixed all duplicate layaway numbers successfully!');
        }

        return 0;
    }

    /**
     * Fix duplicate layaway number by regenerating numbers for duplicates
     *
     * @param string $layawayNumber
     * @param array $ids
     */
    private function fixDuplicateLayawayNumber($layawayNumber, $ids)
    {
        // Keep the first record with the original number, update the rest
        $firstId = array_shift($ids);

        $this->info("Keeping original number for ID: {$firstId}");

        foreach ($ids as $id) {
            $layaway = Layaway::find($id);
            if ($layaway) {
                // Extract the date part from the original number
                $dateFromNumber = substr($layawayNumber, 3, 8); // Extract YYYYMMDD

                // Generate a new unique number for this business and date
                $newNumber = $this->generateUniqueNumber($layaway->business_id, $dateFromNumber);

                $this->info("Updating layaway ID {$id}: {$layawayNumber} -> {$newNumber}");

                $layaway->update(['layaway_number' => $newNumber]);
            }
        }
    }

    /**
     * Generate a unique layaway number for a specific date
     *
     * @param int $businessId
     * @param string $date
     * @return string
     */
    private function generateUniqueNumber($businessId, $date)
    {
        $prefix = 'LAY';
        $maxSequence = 9999;

        for ($sequence = 1; $sequence <= $maxSequence; $sequence++) {
            $layawayNumber = $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);

            // Check if this number already exists
            $exists = Layaway::where('layaway_number', $layawayNumber)->exists();

            if (!$exists) {
                return $layawayNumber;
            }
        }

        throw new \Exception("Unable to generate unique layaway number for business {$businessId} on date {$date}");
    }
}