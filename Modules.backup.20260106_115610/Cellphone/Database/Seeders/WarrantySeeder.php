<?php

namespace Modules\Cellphone\Database\Seeders;

use App\Warranty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarrantySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the first business ID (or you can specify a specific business_id)
        $business_id = DB::table('business')->first()->id ?? 1;

        $warranties = config('cellphone.warranty_options');

        foreach ($warranties as $warranty) {
            // Check if warranty already exists for this business
            $exists = Warranty::where('business_id', $business_id)
                ->where('duration', $warranty['duration'])
                ->where('duration_type', $warranty['duration_type'])
                ->exists();

            if (!$exists) {
                Warranty::create([
                    'business_id' => $business_id,
                    'name' => $warranty['name'],
                    'duration' => $warranty['duration'],
                    'duration_type' => $warranty['duration_type'],
                    'description' => 'Garantía estándar para equipos celulares',
                ]);
            }
        }

        $this->command->info('Cellphone warranties seeded successfully!');
    }
}
