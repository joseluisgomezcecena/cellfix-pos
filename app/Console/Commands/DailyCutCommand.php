<?php

namespace App\Console\Commands;

use App\Utils\DailyCutUtil;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DailyCutCommand extends Command
{
    protected $signature = 'pos:daily-cut
                            {--date= : Date to generate the cut for (default today, format Y-m-d)}
                            {--business= : Only generate cuts for this business id}';

    protected $description = 'Generate the daily POS cut snapshot for every location';

    public function handle(DailyCutUtil $util)
    {
        $date = $this->option('date') ?: Carbon::now()->toDateString();
        $business_id = $this->option('business');

        $this->info("Generating daily cut for {$date}" . ($business_id ? " (business {$business_id})" : ' (all businesses)'));

        if ($business_id) {
            $results = $util->generateForBusiness((int) $business_id, $date);
            $this->info('Generated ' . count($results) . ' cut(s) for business ' . $business_id);
        } else {
            $count = $util->generateForAllBusinesses($date);
            $this->info("Processed {$count} business(es)");
        }

        return Command::SUCCESS;
    }
}
