<?php

namespace Modules\Cellphone\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\System;

class CellphoneDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        // Set module version to mark as installed
        $module_version = config('cellphone.module_version', '1.0');
        System::addProperty('cellphone_version', $module_version);

        // $this->call("OthersTableSeeder");
    }
}
