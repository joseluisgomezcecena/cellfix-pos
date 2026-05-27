<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Brands;
use App\User;
use App\VendorCommissionTarget;
use Illuminate\Http\Request;

class CommissionTargetController extends Controller
{
    /**
     * List every user with role VENDEDORES NIVEL 1 or VENDEDOR PLUS,
     * showing their level and assigned sucursales. Click → edit metas per vendor.
     */
    public function index()
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $vendor_role_names = [
            'VENDEDORES NIVEL 1#' . $business_id,
            'VENDEDOR PLUS#' . $business_id,
        ];

        $vendor_users = User::where('business_id', $business_id)
            ->whereHas('roles', function ($q) use ($vendor_role_names) {
                $q->whereIn('name', $vendor_role_names);
            })
            ->with('roles', 'permissions')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        // Resolve location names for each vendor based on location.X permissions
        $all_locations = BusinessLocation::where('business_id', $business_id)
            ->where('is_active', 1)
            ->select('id', 'name')
            ->get()
            ->keyBy('id');

        $vendor_rows = [];
        foreach ($vendor_users as $user) {
            $perms = $user->permissions->pluck('name')->toArray();
            $access_all = in_array('access_all_locations', $perms) || in_array('access_all_locations#' . $business_id, $perms);

            $locations = [];
            if ($access_all) {
                $locations = $all_locations->pluck('name')->toArray();
            } else {
                foreach ($all_locations as $loc) {
                    if (in_array('location.' . $loc->id, $perms)) {
                        $locations[] = $loc->name;
                    }
                }
            }

            $role_name = optional($user->roles->first())->name;
            $level = $role_name ? str_replace('#' . $business_id, '', $role_name) : '';

            // How many metas does this user already have configured?
            $targets_count = VendorCommissionTarget::where('user_id', $user->id)
                ->where(function ($q) {
                    $q->where('meta_units', '>', 0)->orWhere('commission_per_unit', '>', 0);
                })
                ->count();

            $vendor_rows[] = [
                'user' => $user,
                'level' => $level,
                'locations' => $locations,
                'configured_targets' => $targets_count,
            ];
        }

        return view('commission_target.index', compact('vendor_rows'));
    }

    /**
     * Show the form to configure metas + commission per brand for ONE specific vendor.
     */
    public function edit($user_id)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $user = User::where('business_id', $business_id)
            ->with('roles', 'permissions')
            ->findOrFail($user_id);

        $brands = Brands::where('business_id', $business_id)
            ->orderBy('name')
            ->get();

        $targets = VendorCommissionTarget::where('user_id', $user->id)
            ->get()
            ->keyBy('brand_id');

        // Resolve location names like in index
        $all_locations = BusinessLocation::where('business_id', $business_id)
            ->where('is_active', 1)
            ->get()
            ->keyBy('id');
        $perms = $user->permissions->pluck('name')->toArray();
        $access_all = in_array('access_all_locations', $perms) || in_array('access_all_locations#' . $business_id, $perms);
        $user_locations = [];
        if ($access_all) {
            $user_locations = $all_locations->pluck('name')->toArray();
        } else {
            foreach ($all_locations as $loc) {
                if (in_array('location.' . $loc->id, $perms)) {
                    $user_locations[] = $loc->name;
                }
            }
        }

        $role_name = optional($user->roles->first())->name;
        $level = $role_name ? str_replace('#' . $business_id, '', $role_name) : '';

        return view('commission_target.edit', compact(
            'user', 'level', 'user_locations', 'brands', 'targets'
        ));
    }

    /**
     * Save metas for one vendor.
     */
    public function update(Request $request, $user_id)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            // Verify the user belongs to this business
            $user = User::where('business_id', $business_id)->findOrFail($user_id);

            $rows = $request->input('targets', []); // [brand_id => ['meta_units' => x, 'commission_per_unit' => y]]

            foreach ($rows as $brand_id => $values) {
                $meta = (int) ($values['meta_units'] ?? 0);
                $commission = (float) ($values['commission_per_unit'] ?? 0);

                VendorCommissionTarget::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'brand_id' => (int) $brand_id,
                    ],
                    [
                        'business_id' => $business_id,
                        'meta_units' => $meta,
                        'commission_per_unit' => $commission,
                    ]
                );
            }

            $output = ['success' => 1, 'msg' => __('lang_v1.targets_saved')];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->route('commission-targets.edit', $user_id)->with('status', $output);
    }
}
