<?php

namespace Modules\Layaway\Http\Controllers;

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
                'value' => 'layaway.create',
                'label' => __('layaway::lang.create_layaway'),
                'default' => false
            ],
            [
                'value' => 'layaway.view',
                'label' => __('layaway::lang.view_layaway'),
                'default' => false
            ],
            [
                'value' => 'layaway.update',
                'label' => __('layaway::lang.update_layaway'),
                'default' => false
            ],
            [
                'value' => 'layaway.delete',
                'label' => __('layaway::lang.delete_layaway'),
                'default' => false
            ],
            [
                'value' => 'layaway.process_payment',
                'label' => __('layaway::lang.process_payment'),
                'default' => false
            ],
            [
                'value' => 'layaway.reports',
                'label' => __('layaway::lang.view_reports'),
                'default' => false
            ],
            [
                'value' => 'layaway.settings',
                'label' => __('layaway::lang.manage_settings'),
                'default' => false
            ]
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
                __('layaway::lang.layaway'),
                function ($sub) {
                    $sub->url(
                        action('\Modules\Layaway\Http\Controllers\LayawayController@create'),
                        __('layaway::lang.new_layaway'),
                        ['active' => request()->segment(1) == 'layaway' && request()->segment(2) == 'create']
                    );

                    $sub->url(
                        action('\Modules\Layaway\Http\Controllers\LayawayController@index'),
                        __('layaway::lang.all_layaways'),
                        ['active' => request()->segment(1) == 'layaway' && request()->segment(2) == null]
                    );

                    if (auth()->user()->can('business_settings.access') || auth()->user()->can('view_purchase_n_sell_report')) {
                        $sub->url(
                            url('/equipos-apartados'),
                            __('lang_v1.reserved_equipos_report'),
                            ['active' => request()->segment(1) == 'equipos-apartados']
                        );
                    }
                },
                ['icon' => 'fa fa-shopping-bag']
            )->order(32);
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

        if ($notification->type == 'Modules\Layaway\Notifications\LayawayPaymentDueNotification') {
            $data = $notification->data;
            $notification_data = [
                'msg' => __('layaway::lang.payment_due_notification', ['number' => $data['layaway_number'], 'amount' => $data['amount_due']]),
                'icon_class' => 'fa fa-calendar-times-o',
                'link' => action('\\Modules\\Layaway\\Http\\Controllers\\LayawayController@show', $data['layaway_id']),
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at->diffForHumans()
            ];
        }

        if ($notification->type == 'Modules\Layaway\Notifications\LayawayOverdueNotification') {
            $data = $notification->data;
            $notification_data = [
                'msg' => __('layaway::lang.overdue_notification', ['number' => $data['layaway_number'], 'days' => $data['days_overdue']]),
                'icon_class' => 'fa fa-exclamation-triangle text-warning',
                'link' => action('\\Modules\\Layaway\\Http\\Controllers\\LayawayController@show', $data['layaway_id']),
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at->diffForHumans()
            ];
        }

        if ($notification->type == 'Modules\Layaway\Notifications\LayawayCompletedNotification') {
            $data = $notification->data;
            $notification_data = [
                'msg' => __('layaway::lang.completed_notification', ['number' => $data['layaway_number'], 'customer' => $data['customer_name']]),
                'icon_class' => 'fa fa-check-circle text-success',
                'link' => action('\\Modules\\Layaway\\Http\\Controllers\\LayawayController@show', $data['layaway_id']),
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at->diffForHumans()
            ];
        }

        return $notification_data;
    }

    /**
     * Add custom dashboard widgets
     * @return array
     */
    public function dashboard_widgets($business_id)
    {
        $widgets = [];

        if (auth()->user()->can('layaway.view')) {
            $widgets['layaway_summary'] = [
                'label' => __('layaway::lang.layaway_summary'),
                'view' => 'layaway::dashboard.summary',
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
    private function getDashboardData($business_id)
    {
        $data = [];

        try {
            $data['total_active'] = \Modules\Layaway\Entities\Layaway::where('business_id', $business_id)
                ->where('status', 'active')
                ->count();

            $data['total_overdue'] = \Modules\Layaway\Entities\Layaway::where('business_id', $business_id)
                ->overdue()
                ->count();

            $data['pending_amount'] = \Modules\Layaway\Entities\Layaway::where('business_id', $business_id)
                ->where('status', 'active')
                ->sum('balance_due');

            $data['today_collections'] = \Modules\Layaway\Entities\LayawayPayment::whereHas('layaway', function($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                })
                ->whereDate('payment_date', today())
                ->sum('amount');
        } catch (\Exception $e) {
            \Log::error('Layaway Dashboard Error: ' . $e->getMessage());
        }

        return $data;
    }

    /**
     * Add module data to POS
     * @param array $pos_data
     * @return array
     */
    public function after_pos_create($pos_data)
    {
        $layaway_id = request()->get('layaway_id');

        if (!empty($layaway_id)) {
            $layaway = \Modules\Layaway\Entities\Layaway::find($layaway_id);
            if ($layaway && $layaway->business_id == $pos_data['business_id']) {
                $pos_data['layaway_id'] = $layaway_id;
            }
        }

        return $pos_data;
    }

    /**
     * Add module specific validations
     * @return array
     */
    public function pos_form_validation()
    {
        return [
            'layaway_id' => 'nullable|exists:layaways,id'
        ];
    }
}