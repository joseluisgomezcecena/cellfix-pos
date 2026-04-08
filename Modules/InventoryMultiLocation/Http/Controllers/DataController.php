<?php

namespace Modules\InventoryMultiLocation\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Menu;

class DataController extends Controller
{
    public function user_permissions()
    {
        return [
            [
                'value' => 'inventory_multi.view',
                'label' => __('inventorymultilocation::lang.view_inventory'),
                'default' => false
            ],
            [
                'value' => 'inventory_multi.view_all_locations',
                'label' => __('inventorymultilocation::lang.view_all_locations'),
                'default' => true
            ],
            [
                'value' => 'inventory_multi.transfer',
                'label' => __('inventorymultilocation::lang.transfer_stock'),
                'default' => false
            ],
            [
                'value' => 'inventory_multi.manage',
                'label' => __('inventorymultilocation::lang.manage_inventory'),
                'default' => false
            ],
            [
                'value' => 'inventory_multi.bulk_actions',
                'label' => __('inventorymultilocation::lang.bulk_actions'),
                'default' => false
            ]
        ];
    }

    public function modifyAdminMenu()
    {
        return Menu::modify('admin-sidebar-menu', function ($menu) {
            $menu->dropdown(__('inventorymultilocation::lang.inventory_multi_location'), function ($sub) {
                $sub->url(
                    action('\Modules\InventoryMultiLocation\Http\Controllers\InventoryController@dashboard'),
                    __('inventorymultilocation::lang.dashboard'),
                    [
                        'icon' => 'fa fa-dashboard',
                        'active' => request()->segment(1) == 'inventory-multi' && request()->segment(2) == 'dashboard'
                    ]
                );
                $sub->url(
                    action('\Modules\InventoryMultiLocation\Http\Controllers\InventoryController@index'),
                    __('inventorymultilocation::lang.view_inventory'),
                    [
                        'active' => request()->segment(1) == 'inventory-multi' && request()->segment(2) == 'inventory'
                    ]
                );
            }, ['icon' => 'fa fa-warehouse'])->order(33);
        });
    }

    public function parse_notification($notification)
    {
        if ($notification->type == 'Modules\InventoryMultiLocation\Notifications\LowStockAlert') {
            return [
                'msg' => __('inventorymultilocation::lang.low_stock_notification'),
                'icon_class' => 'fa fa-exclamation-triangle',
                'link' => action('\Modules\InventoryMultiLocation\Http\Controllers\InventoryController@index'),
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at->diffForHumans()
            ];
        }

        if ($notification->type == 'Modules\InventoryMultiLocation\Notifications\StockTransferNotification') {
            return [
                'msg' => __('inventorymultilocation::lang.stock_transfer_notification'),
                'icon_class' => 'fa fa-exchange',
                'link' => action('\Modules\InventoryMultiLocation\Http\Controllers\TransferController@index'),
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at->diffForHumans()
            ];
        }
    }

    public function dashboard_widgets()
    {
        return [
            [
                'title' => __('inventorymultilocation::lang.total_locations'),
                'value' => 'total_locations',
                'icon' => 'fa fa-building',
                'color' => 'bg-blue'
            ],
            [
                'title' => __('inventorymultilocation::lang.low_stock_items'),
                'value' => 'low_stock_items',
                'icon' => 'fa fa-exclamation-triangle',
                'color' => 'bg-red'
            ],
            [
                'title' => __('inventorymultilocation::lang.pending_transfers'),
                'value' => 'pending_transfers',
                'icon' => 'fa fa-exchange',
                'color' => 'bg-yellow'
            ],
            [
                'title' => __('inventorymultilocation::lang.total_stock_value'),
                'value' => 'total_stock_value',
                'icon' => 'fa fa-money',
                'color' => 'bg-green'
            ]
        ];
    }

    public function getDashboardData()
    {
        $business_id = request()->session()->get('user.business_id');
        $user = auth()->user();

        // Check if user has permission to view all locations for inventory
        if ($user->can('inventory_multi.view_all_locations')) {
            $permitted_locations = 'all';
        } else {
            $permitted_locations = $user->permitted_locations();
        }

        $data = [];

        $location_query = \DB::table('business_locations')
            ->where('business_id', $business_id)
            ->where('is_active', 1);

        if ($permitted_locations !== 'all') {
            $location_query->whereIn('id', $permitted_locations);
        }

        $data['total_locations'] = $location_query->count();

        $low_stock_query = \DB::table('variation_location_details as vld')
            ->join('variations as v', 'vld.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->where('p.business_id', $business_id)
            ->whereRaw('vld.qty_available <= p.alert_quantity');

        if ($permitted_locations !== 'all') {
            $low_stock_query->whereIn('vld.location_id', $permitted_locations);
        }

        $data['low_stock_items'] = $low_stock_query->count();

        $data['pending_transfers'] = \DB::table('inventory_transfers')
            ->where('business_id', $business_id)
            ->where('status', 'pending')
            ->count();

        $stock_value_query = \DB::table('variation_location_details as vld')
            ->join('variations as v', 'vld.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->where('p.business_id', $business_id);

        if ($permitted_locations !== 'all') {
            $stock_value_query->whereIn('vld.location_id', $permitted_locations);
        }

        $data['total_stock_value'] = $stock_value_query->sum(\DB::raw('vld.qty_available * v.default_purchase_price'));

        return $data;
    }
}
