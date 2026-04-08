<?php

namespace Modules\Layaway\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;

class LayawayPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $permissions = [
            [
                'name' => 'layaway.create',
                'guard_name' => 'web'
            ],
            [
                'name' => 'layaway.view',
                'guard_name' => 'web'
            ],
            [
                'name' => 'layaway.update',
                'guard_name' => 'web'
            ],
            [
                'name' => 'layaway.delete',
                'guard_name' => 'web'
            ],
            [
                'name' => 'layaway.process_payment',
                'guard_name' => 'web'
            ],
            [
                'name' => 'layaway.reports',
                'guard_name' => 'web'
            ],
            [
                'name' => 'layaway.settings',
                'guard_name' => 'web'
            ]
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }

        // Add permissions to Admin role if it exists
        $admin_role = \Spatie\Permission\Models\Role::where('name', 'Admin')->first();
        if ($admin_role) {
            foreach ($permissions as $permission) {
                $admin_role->givePermissionTo($permission['name']);
            }
        }
    }
}