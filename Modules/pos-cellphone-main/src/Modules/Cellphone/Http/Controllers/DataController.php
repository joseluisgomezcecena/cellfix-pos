<?php

namespace Modules\Cellphone\Http\Controllers;

use Illuminate\Routing\Controller;
use Menu;
use Modules\Cellphone\Entities\Cellphone;

class DataController extends Controller
{
    /**
     * Define module permissions
     * @return array
     */
    public function user_permissions()
    {
        return [
            [
                'value' => 'cellphone.create',
                'label' => __('cellphone::lang.create_cellphone'),
                'default' => false
            ],
            [
                'value' => 'cellphone.view',
                'label' => __('cellphone::lang.view_cellphone'),
                'default' => false
            ],
            [
                'value' => 'cellphone.update',
                'label' => __('cellphone::lang.update_cellphone'),
                'default' => false
            ],
            [
                'value' => 'cellphone.delete',
                'label' => __('cellphone::lang.delete_cellphone'),
                'default' => false
            ],
            [
                'value' => 'cellphone.export',
                'label' => __('cellphone::lang.export_cellphone'),
                'default' => false
            ],
        ];
    }

    /**
     * Add module menu items
     * @return Menu
     */
    public function modifyAdminMenu()
    {
        return Menu::modify('admin-sidebar-menu', function ($menu) {
            $menu->dropdown(
                __('cellphone::lang.menu'),
                function ($sub) {
                    $sub->url(
                        action('\Modules\Cellphone\Http\Controllers\CellphoneController@create'),
                        __('cellphone::lang.new_cellphone'),
                        [
                            'active' => request()->segment(1) == 'cellphone' && request()->segment(2) == 'create'
                        ]
                    );

                    $sub->url(
                        action('\Modules\Cellphone\Http\Controllers\CellphoneController@index'),
                        __('cellphone::lang.all_cellphones'),
                        [
                            'active' => request()->segment(1) == 'cellphone' && request()->segment(2) == null
                        ]
                    );
                },
                [
                    'icon' => 'fa fa-mobile',
                    'id' => 'cellphone-menu'
                ]
            )->order(33);
        });
    }

    /**
     * Parse module notifications
     * @param $notification
     * @return array
     */
    public function parse_notification($notification)
    {
        $notification_data = [];

        // Add notification parsing if needed in the future
        // For example: warranty expiration notifications

        return $notification_data;
    }

    /**
     * Add custom dashboard widgets
     * @return array
     */
    public function dashboard_widgets($business_id)
    {
        $widgets = [];

        if (auth()->user()->can('cellphone.view')) {
            $widgets['cellphone_summary'] = [
                'label' => __('cellphone::lang.dashboard_widget_title'),
                'view' => 'cellphone::dashboard.widget',
                'data' => $this->getDashboardData($business_id)
            ];
        }

        return $widgets;
    }

    /**
     * Get dashboard data for widgets
     * @param int $business_id
     * @return array
     */
    public function getDashboardData($business_id)
    {
        return Cellphone::getStatistics($business_id);
    }

    /**
     * Adds super admin settings for this module
     * @return array
     */
    public function superadmin_settings()
    {
        return [];
    }
}
