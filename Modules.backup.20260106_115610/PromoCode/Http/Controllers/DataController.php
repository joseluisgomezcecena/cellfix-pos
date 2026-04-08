<?php

namespace Modules\PromoCode\Http\Controllers;

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
                'value' => 'promocode.view',
                'label' => __('promocode::lang.view_promo_companies'),
                'default' => false
            ],
            [
                'value' => 'promocode.create',
                'label' => __('promocode::lang.create_promo_company'),
                'default' => false
            ],
            [
                'value' => 'promocode.update',
                'label' => __('promocode::lang.update_promo_company'),
                'default' => false
            ],
            [
                'value' => 'promocode.delete',
                'label' => __('promocode::lang.delete_promo_company'),
                'default' => false
            ],
        ];
    }

    /**
     * Add module menu items to admin sidebar
     * @return Menu
     */
    public function modifyAdminMenu()
    {
        if (!auth()->check()) {
            return null;
        }

        return Menu::modify('admin-sidebar-menu', function ($menu) {
            if (!$menu) {
                return;
            }

            $menu->dropdown(
                __('promocode::lang.promo_companies'),
                function ($sub) {
                    if (auth()->user()->can('promocode.view') || auth()->user()->can('superadmin')) {
                        $sub->url(
                            action('\Modules\PromoCode\Http\Controllers\PromoCompanyController@index'),
                            __('promocode::lang.all_promo_companies'),
                            ['active' => request()->segment(1) == 'promo-codes' && request()->segment(2) == 'promo-companies' && request()->segment(3) == null]
                        );
                    }

                    if (auth()->user()->can('promocode.create') || auth()->user()->can('superadmin')) {
                        $sub->url(
                            action('\Modules\PromoCode\Http\Controllers\PromoCompanyController@create'),
                            __('promocode::lang.add_promo_company'),
                            ['active' => request()->segment(1) == 'promo-codes' && request()->segment(2) == 'promo-companies' && request()->segment(3) == 'create']
                        );
                    }
                },
                ['icon' => 'fa fa-tags']
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
}
