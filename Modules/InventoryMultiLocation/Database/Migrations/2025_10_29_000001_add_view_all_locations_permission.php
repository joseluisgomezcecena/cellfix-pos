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
        // Create the new permission
        $permission = [
            'name' => 'inventory_multi.view_all_locations',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now()
        ];

        DB::table('permissions')->insert($permission);

        // Get the newly created permission ID
        $permissionId = DB::table('permissions')
            ->where('name', 'inventory_multi.view_all_locations')
            ->first()->id;

        // Get all existing roles
        $roles = DB::table('roles')->get();

        // Assign the permission to all roles
        foreach ($roles as $role) {
            DB::table('role_has_permissions')->insert([
                'permission_id' => $permissionId,
                'role_id' => $role->id
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Get the permission ID before deleting
        $permission = DB::table('permissions')
            ->where('name', 'inventory_multi.view_all_locations')
            ->first();

        if ($permission) {
            // Remove role assignments
            DB::table('role_has_permissions')
                ->where('permission_id', $permission->id)
                ->delete();

            // Delete the permission
            DB::table('permissions')
                ->where('name', 'inventory_multi.view_all_locations')
                ->delete();
        }
    }
};
