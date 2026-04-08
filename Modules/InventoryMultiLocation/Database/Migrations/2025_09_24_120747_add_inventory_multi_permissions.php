<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permissions = [
            [
                'name' => 'inventory_multi.view',
                'guard_name' => 'web'
            ],
            [
                'name' => 'inventory_multi.transfer',
                'guard_name' => 'web'
            ],
            [
                'name' => 'inventory_multi.manage',
                'guard_name' => 'web'
            ],
            [
                'name' => 'inventory_multi.bulk_actions',
                'guard_name' => 'web'
            ]
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insert(array_merge($permission, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $permissions = [
            'inventory_multi.view',
            'inventory_multi.transfer',
            'inventory_multi.manage',
            'inventory_multi.bulk_actions'
        ];

        DB::table('permissions')->whereIn('name', $permissions)->delete();
    }
};
