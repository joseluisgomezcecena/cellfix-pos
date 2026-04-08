<?php

namespace Modules\Cellphone\Console;

use App\Product;
use App\ProductVariation;
use App\Variation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Cellphone\Entities\Cellphone;

class RepairCellphonesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cellphone:repair {--business_id= : Specific business ID to repair} {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Repair broken cellphones that are missing flags, stock settings, or location links';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $businessId = $this->option('business_id');

        $this->info('=== Cellphone Repair Tool ===');
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }
        $this->newLine();

        // Build query for cellphones
        $query = Cellphone::cellphones();

        if ($businessId) {
            $query->where('business_id', $businessId);
            $this->info("Scanning cellphones for business ID: {$businessId}");
        } else {
            $this->info("Scanning all cellphones across all businesses");
        }

        $cellphones = $query->get();
        $this->info("Found {$cellphones->count()} cellphones to check");
        $this->newLine();

        $stats = [
            'checked' => 0,
            'flag_restored' => 0,
            'stock_enabled' => 0,
            'locations_linked' => 0,
            'already_ok' => 0,
        ];

        foreach ($cellphones as $cellphone) {
            $stats['checked']++;
            $needsRepair = false;
            $repairs = [];

            // Check 1: Cellphone flag
            if (!$cellphone->isCellphone()) {
                $needsRepair = true;
                $repairs[] = 'Missing CELLPHONE_MODULE flag';
                $stats['flag_restored']++;
            }

            // Check 2: Enable stock flag
            if ($cellphone->enable_stock != 1) {
                // Check if this cellphone has stock records
                $hasStock = DB::table('variation_location_details')
                    ->where('product_id', $cellphone->id)
                    ->exists();

                if ($hasStock) {
                    $needsRepair = true;
                    $repairs[] = 'Missing enable_stock flag (has stock)';
                    $stats['stock_enabled']++;
                }
            }

            // Check 3: Product locations
            $stockLocations = DB::table('variation_location_details')
                ->where('product_id', $cellphone->id)
                ->pluck('location_id')
                ->unique();

            $linkedLocations = DB::table('product_locations')
                ->where('product_id', $cellphone->id)
                ->pluck('location_id');

            $missingLocations = $stockLocations->diff($linkedLocations);

            if ($missingLocations->count() > 0) {
                $needsRepair = true;
                $repairs[] = "Missing location links: " . $missingLocations->implode(', ');
                $stats['locations_linked']++;
            }

            if ($needsRepair) {
                $this->warn("Product #{$cellphone->id} ({$cellphone->name}) needs repair:");
                foreach ($repairs as $repair) {
                    $this->line("  - {$repair}");
                }

                if (!$dryRun) {
                    DB::beginTransaction();
                    try {
                        // Fix 1: Restore cellphone flag
                        if (!$cellphone->isCellphone()) {
                            $cellphone->markAsCellphone();
                        }

                        // Fix 2: Enable stock if has stock records
                        $hasStock = DB::table('variation_location_details')
                            ->where('product_id', $cellphone->id)
                            ->exists();

                        if ($hasStock && $cellphone->enable_stock != 1) {
                            $cellphone->enable_stock = 1;
                        }

                        $cellphone->save();

                        // Fix 3: Link to locations where stock exists
                        if ($missingLocations->count() > 0) {
                            $currentLocations = $linkedLocations->toArray();
                            $allLocations = array_unique(array_merge($currentLocations, $missingLocations->toArray()));
                            $cellphone->product_locations()->sync($allLocations);
                        }

                        DB::commit();
                        $this->info("  ✓ Repaired successfully");
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $this->error("  ✗ Failed to repair: " . $e->getMessage());
                    }
                } else {
                    $this->info("  (Would repair in non-dry-run mode)");
                }
                $this->newLine();
            } else {
                $stats['already_ok']++;
            }
        }

        // Summary
        $this->newLine();
        $this->info('=== Repair Summary ===');
        $this->line("Cellphones checked: {$stats['checked']}");
        $this->line("Already OK: {$stats['already_ok']}");

        if ($stats['flag_restored'] > 0) {
            $action = $dryRun ? 'Would restore' : 'Restored';
            $this->line("{$action} cellphone flags: {$stats['flag_restored']}");
        }

        if ($stats['stock_enabled'] > 0) {
            $action = $dryRun ? 'Would enable' : 'Enabled';
            $this->line("{$action} stock tracking: {$stats['stock_enabled']}");
        }

        if ($stats['locations_linked'] > 0) {
            $action = $dryRun ? 'Would link' : 'Linked';
            $this->line("{$action} product locations: {$stats['locations_linked']}");
        }

        if ($dryRun && ($stats['flag_restored'] + $stats['stock_enabled'] + $stats['locations_linked'] > 0)) {
            $this->newLine();
            $this->warn('Run without --dry-run to apply these fixes');
        }

        return 0;
    }
}
