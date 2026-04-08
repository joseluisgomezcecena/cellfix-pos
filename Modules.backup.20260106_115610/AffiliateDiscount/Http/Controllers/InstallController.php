<?php

namespace Modules\AffiliateDiscount\Http\Controllers;

use App\Http\Controllers\Controller;
use App\System;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class InstallController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the module installation page
     */
    public function index()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        return view('affiliatediscount::install.index');
    }

    /**
     * Install the module
     */
    public function install()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            // Run migrations
            Artisan::call('module:migrate', ['module' => 'AffiliateDiscount']);

            // Set module version
            System::addProperty('affiliatediscount_version', config('affiliatediscount.module_version'));

            // Create default permissions
            $this->createPermissions();

            DB::commit();

            $output = [
                'success' => 1,
                'msg' => 'AffiliateDiscount module installed successfully'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File: ' . $e->getFile(). ' Line: ' . $e->getLine(). ' Message: ' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => $e->getMessage()
            ];
        }

        return redirect()
            ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
            ->with('status', $output);
    }

    /**
     * Uninstall the module
     */
    public function uninstall()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            System::removeProperty('affiliatediscount_version');

            $output = [
                'success' => 1,
                'msg' => 'AffiliateDiscount module uninstalled successfully'
            ];
        } catch (\Exception $e) {
            $output = [
                'success' => 0,
                'msg' => $e->getMessage()
            ];
        }

        return redirect()
            ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
            ->with('status', $output);
    }

    /**
     * Update the module
     */
    public function update()
    {
        // Future updates logic here
        return redirect()
            ->action([\App\Http\Controllers\Install\ModulesController::class, 'index']);
    }

    /**
     * Create default permissions
     */
    private function createPermissions()
    {
        $permissions = [
            ['name' => 'manage_affiliate_discounts', 'guard_name' => 'web'],
            ['name' => 'use_affiliate_discounts_in_pos', 'guard_name' => 'web'],
            ['name' => 'view_affiliate_reports', 'guard_name' => 'web'],
            ['name' => 'add_discount_options_from_pos', 'guard_name' => 'web'],
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate($permission);
        }
    }
}
