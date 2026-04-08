<?php

namespace Modules\InventoryMultiLocation\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use DB;

class InventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('AdminSidebarMenu');
    }

    public function dashboard()
    {
        $business_id = request()->session()->get('user.business_id');
        $user = auth()->user();

        // Check if user has permission to view all locations for inventory
        if ($user->can('inventory_multi.view_all_locations')) {
            $permitted_locations = 'all';
        } else {
            $permitted_locations = $user->permitted_locations();
        }

        $locations_query = DB::table('business_locations')
            ->where('business_id', $business_id)
            ->where('is_active', 1);

        if ($permitted_locations !== 'all') {
            $locations_query->whereIn('id', $permitted_locations);
        }

        $locations = $locations_query->get();

        $location_stats = [];
        foreach ($locations as $location) {
            $stats = $this->getLocationStats($location->id);
            $location_stats[$location->id] = $stats;
        }

        $recent_transfers = DB::table('inventory_transfers as it')
            ->join('business_locations as bl1', 'it.from_location_id', '=', 'bl1.id')
            ->join('business_locations as bl2', 'it.to_location_id', '=', 'bl2.id')
            ->join('users as u', 'it.created_by', '=', 'u.id')
            ->where('it.business_id', $business_id)
            ->select(
                'it.*',
                'bl1.name as from_location',
                'bl2.name as to_location',
                'u.first_name',
                'u.last_name'
            )
            ->orderBy('it.created_at', 'desc')
            ->limit(10)
            ->get();

        return view('inventorymultilocation::dashboard', compact(
            'locations',
            'location_stats',
            'recent_transfers'
        ));
    }

    public function index(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $user = auth()->user();

        // Check if user has permission to view all locations for inventory
        if ($user->can('inventory_multi.view_all_locations')) {
            $permitted_locations = 'all';
        } else {
            $permitted_locations = $user->permitted_locations();
        }

        $location_id = $request->get('location_id', 'all');

        $locations_query = DB::table('business_locations')
            ->where('business_id', $business_id)
            ->where('is_active', 1);

        if ($permitted_locations !== 'all') {
            $locations_query->whereIn('id', $permitted_locations);
        }

        $locations = $locations_query->get();

        $categories = DB::table('categories')
            ->where('business_id', $business_id)
            ->where('parent_id', 0)
            ->get();

        $brands = DB::table('brands')
            ->where('business_id', $business_id)
            ->get();

        $query = DB::table('products as p')
            ->join('variations as v', 'p.id', '=', 'v.product_id')
            ->leftJoin('variation_location_details as vld', function($join) use ($location_id, $permitted_locations) {
                $join->on('v.id', '=', 'vld.variation_id');
                if ($location_id != 'all') {
                    $join->where('vld.location_id', $location_id);
                } elseif ($permitted_locations !== 'all') {
                    $join->whereIn('vld.location_id', $permitted_locations);
                }
            })
            ->leftJoin('business_locations as bl', 'vld.location_id', '=', 'bl.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('brands as b', 'p.brand_id', '=', 'b.id')
            ->leftJoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('p.business_id', $business_id)
            ->whereNotNull('vld.location_id')
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                'p.sku',
                'p.alert_quantity',
                'v.id as variation_id',
                'v.name as variation_name',
                'v.sub_sku',
                'v.default_purchase_price',
                'v.default_sell_price',
                'vld.qty_available',
                'vld.location_id',
                'bl.name as location_name',
                'c.name as category_name',
                'b.name as brand_name',
                'u.short_name as unit_name',
                'vld.updated_at as last_updated'
            );

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('p.name', 'like', "%{$search}%")
                  ->orWhere('p.sku', 'like', "%{$search}%")
                  ->orWhere('v.sub_sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('p.category_id', $request->get('category_id'));
        }

        if ($request->filled('brand_id')) {
            $query->where('p.brand_id', $request->get('brand_id'));
        }

        if ($request->filled('stock_status')) {
            $status = $request->get('stock_status');
            if ($status == 'low_stock') {
                $query->whereRaw('vld.qty_available <= p.alert_quantity');
            } elseif ($status == 'out_of_stock') {
                $query->where(function($q) {
                    $q->whereNull('vld.qty_available')
                      ->orWhere('vld.qty_available', '<=', 0);
                });
            } elseif ($status == 'in_stock') {
                $query->where('vld.qty_available', '>', 0);
            }
        }

        $inventory = $query->orderBy('p.name')
                           ->orderBy('v.name')
                           ->paginate(50);

        if ($request->ajax()) {
            return view('inventorymultilocation::partials.inventory_table', compact('inventory', 'locations'));
        }

        return view('inventorymultilocation::inventory', compact(
            'inventory',
            'locations',
            'categories',
            'brands',
            'location_id'
        ));
    }

    public function getLocationData(Request $request)
    {
        $location_id = $request->get('location_id');
        $stats = $this->getLocationStats($location_id);

        return response()->json($stats);
    }

    private function getLocationStats($location_id)
    {
        $business_id = request()->session()->get('user.business_id');

        $total_products = DB::table('variation_location_details as vld')
            ->join('variations as v', 'vld.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->where('p.business_id', $business_id)
            ->where('vld.location_id', $location_id)
            ->count();

        $total_value = DB::table('variation_location_details as vld')
            ->join('variations as v', 'vld.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->where('p.business_id', $business_id)
            ->where('vld.location_id', $location_id)
            ->sum(DB::raw('vld.qty_available * v.default_purchase_price'));

        $low_stock_count = DB::table('variation_location_details as vld')
            ->join('variations as v', 'vld.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->where('p.business_id', $business_id)
            ->where('vld.location_id', $location_id)
            ->whereRaw('vld.qty_available <= p.alert_quantity')
            ->count();

        $out_of_stock_count = DB::table('variation_location_details as vld')
            ->join('variations as v', 'vld.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->where('p.business_id', $business_id)
            ->where('vld.location_id', $location_id)
            ->where('vld.qty_available', '<=', 0)
            ->count();

        return [
            'total_products' => $total_products,
            'total_value' => $total_value,
            'low_stock_count' => $low_stock_count,
            'out_of_stock_count' => $out_of_stock_count
        ];
    }
}
