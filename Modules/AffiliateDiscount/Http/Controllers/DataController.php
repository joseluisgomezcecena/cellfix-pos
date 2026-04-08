<?php

namespace Modules\AffiliateDiscount\Http\Controllers;

use Illuminate\Routing\Controller;
use Menu;

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
                'value' => 'manage_affiliate_discounts',
                'label' => __('affiliatediscount::lang.manage_affiliate_discounts'),
                'default' => false
            ],
            [
                'value' => 'use_affiliate_discounts_in_pos',
                'label' => __('affiliatediscount::lang.use_affiliate_discounts_in_pos'),
                'default' => false
            ],
            [
                'value' => 'view_affiliate_reports',
                'label' => __('affiliatediscount::lang.view_affiliate_reports'),
                'default' => false
            ],
            [
                'value' => 'add_discount_options_from_pos',
                'label' => __('affiliatediscount::lang.add_discount_options_from_pos'),
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
        // Don't register menu items if module is disabled
        if (!\Nwidart\Modules\Facades\Module::isEnabled('AffiliateDiscount')) {
            return;
        }

        return Menu::modify('admin-sidebar-menu', function ($menu) {
            $menu->dropdown(
                __('affiliatediscount::lang.menu'),
                function ($sub) {
                    $sub->url(
                        action('\Modules\AffiliateDiscount\Http\Controllers\AffiliateDiscountController@index'),
                        __('affiliatediscount::lang.affiliated_businesses'),
                        [
                            'active' => request()->segment(1) == 'affiliate-discounts'
                        ]
                    );

                    $sub->url(
                        action('\Modules\AffiliateDiscount\Http\Controllers\AffiliateDiscountController@reports'),
                        __('affiliatediscount::lang.reports'),
                        [
                            'active' => request()->segment(1) == 'affiliate-discounts' && request()->segment(2) == 'reports'
                        ]
                    );
                },
                [
                    'icon' => 'fa fa-handshake',
                    'id' => 'affiliate-discount-menu'
                ]
            )->order(34);
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
        return $notification_data;
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
