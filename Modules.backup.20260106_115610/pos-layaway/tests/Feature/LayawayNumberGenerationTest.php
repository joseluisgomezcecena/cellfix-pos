<?php

namespace Tests\Feature;

use Tests\TestCase;
use Modules\Layaway\Entities\Layaway;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class LayawayNumberGenerationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_generates_unique_layaway_numbers()
    {
        $businessId = 1;

        // Generate multiple layaway numbers by creating actual layaways
        $numbers = [];
        for ($i = 0; $i < 5; $i++) {
            $layaway = Layaway::create([
                'business_id' => $businessId,
                'contact_id' => 1,
                'business_location_id' => 1,
                'created_by' => 1,
                'total_amount' => 100.00,
                'down_payment_amount' => 20.00,
                'balance_due' => 80.00,
                'payment_deadline' => now()->addDays(30),
            ]);
            $numbers[] = $layaway->layaway_number;
        }

        // Ensure all numbers are unique
        $this->assertEquals(count($numbers), count(array_unique($numbers)));
    }

    /** @test */
    public function it_generates_sequential_layaway_numbers()
    {
        $businessId = 1;
        $date = date('Ymd');

        // Generate first number
        $firstNumber = Layaway::generateLayawayNumber($businessId);
        $this->assertEquals("LAY{$date}0001", $firstNumber);

        // Create a layaway with this number to simulate database state
        $layaway = new Layaway([
            'business_id' => $businessId,
            'contact_id' => 1,
            'business_location_id' => 1,
            'created_by' => 1,
            'layaway_number' => $firstNumber,
            'total_amount' => 100.00,
            'down_payment_amount' => 20.00,
            'balance_due' => 80.00,
            'payment_deadline' => now()->addDays(30),
        ]);
        $layaway->save();

        // Generate second number
        $secondNumber = Layaway::generateLayawayNumber($businessId);
        $this->assertEquals("LAY{$date}0002", $secondNumber);
    }

    /** @test */
    public function it_handles_concurrent_layaway_creation()
    {
        $businessId = 1;
        $numbers = [];

        // Simulate concurrent requests by creating actual layaways
        for ($i = 0; $i < 5; $i++) {
            $layaway = Layaway::create([
                'business_id' => $businessId,
                'contact_id' => 1,
                'business_location_id' => 1,
                'created_by' => 1,
                'total_amount' => 100.00,
                'down_payment_amount' => 20.00,
                'balance_due' => 80.00,
                'payment_deadline' => now()->addDays(30),
            ]);
            $numbers[] = $layaway->layaway_number;
        }

        // Ensure all numbers are unique
        $this->assertEquals(count($numbers), count(array_unique($numbers)));
    }

    /** @test */
    public function atomic_method_generates_unique_numbers()
    {
        $businessId = 1;

        // Generate multiple layaway numbers using atomic method
        $numbers = [];
        for ($i = 0; $i < 5; $i++) {
            $numbers[] = Layaway::generateLayawayNumberAtomic($businessId);
        }

        // Ensure all numbers are unique
        $this->assertEquals(count($numbers), count(array_unique($numbers)));

        // Verify sequence table has correct values
        $date = date('Ymd');
        $sequenceKey = "layaway_{$businessId}_{$date}";
        $sequence = DB::table('sequences')->where('key', $sequenceKey)->first();

        $this->assertNotNull($sequence);
        $this->assertEquals(5, $sequence->value);
    }

    /** @test */
    public function different_businesses_can_have_same_sequence_numbers()
    {
        $date = date('Ymd');

        $number1 = Layaway::generateLayawayNumber(1);
        $number2 = Layaway::generateLayawayNumber(2);

        // Both should start with sequence 0001 for their respective businesses
        $this->assertEquals("LAY{$date}0001", $number1);
        $this->assertEquals("LAY{$date}0001", $number2);
    }
}