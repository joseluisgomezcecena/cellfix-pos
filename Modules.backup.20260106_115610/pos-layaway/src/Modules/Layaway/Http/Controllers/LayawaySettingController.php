<?php

namespace Modules\Layaway\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\System;

class LayawaySettingController extends Controller
{
    /**
     * Display layaway settings
     * @return Renderable
     */
    public function index()
    {
        if (!auth()->user()->can('layaway.settings')) {
            abort(403, 'Unauthorized action.');
        }

        $settings = [
            'default_down_payment_percentage' => System::getProperty('layaway_default_down_payment') ?? 20,
            'minimum_down_payment_percentage' => System::getProperty('layaway_minimum_down_payment') ?? 10,
            'maximum_down_payment_percentage' => System::getProperty('layaway_maximum_down_payment') ?? 100,
            'default_payment_deadline_days' => System::getProperty('layaway_default_deadline_days') ?? 30,
            'maximum_payment_deadline_days' => System::getProperty('layaway_maximum_deadline_days') ?? 365,
            'layaway_number_prefix' => System::getProperty('layaway_number_prefix') ?? 'LAY',
            'enable_overdue_notifications' => System::getProperty('layaway_enable_overdue_notifications') ?? 1,
            'overdue_notification_days' => System::getProperty('layaway_overdue_notification_days') ?? 3,
            'enable_due_date_reminders' => System::getProperty('layaway_enable_due_date_reminders') ?? 1,
            'due_date_reminder_days' => System::getProperty('layaway_due_date_reminder_days') ?? 7,
            'auto_expire_overdue_days' => System::getProperty('layaway_auto_expire_days') ?? 90,
            'allow_partial_payments' => System::getProperty('layaway_allow_partial_payments') ?? 1,
            'require_customer_signature' => System::getProperty('layaway_require_signature') ?? 0,
            'enable_layaway_receipts' => System::getProperty('layaway_enable_receipts') ?? 1,
            'enable_layaway_terms' => System::getProperty('layaway_enable_terms') ?? 1,
            'layaway_terms_text' => System::getProperty('layaway_terms_text') ?? ''
        ];

        return view('layaway::settings.index', compact('settings'));
    }

    /**
     * Update layaway settings
     * @param Request $request
     * @return Response
     */
    public function update(Request $request)
    {
        if (!auth()->user()->can('layaway.settings')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'default_down_payment_percentage' => 'required|numeric|min:0|max:100',
            'minimum_down_payment_percentage' => 'required|numeric|min:0|max:100',
            'maximum_down_payment_percentage' => 'required|numeric|min:0|max:100',
            'default_payment_deadline_days' => 'required|integer|min:1|max:365',
            'maximum_payment_deadline_days' => 'required|integer|min:1|max:365',
            'layaway_number_prefix' => 'required|string|max:10',
            'overdue_notification_days' => 'required|integer|min:1|max:30',
            'due_date_reminder_days' => 'required|integer|min:1|max:30',
            'auto_expire_overdue_days' => 'required|integer|min:1|max:365',
            'layaway_terms_text' => 'nullable|string'
        ]);

        try {
            $settings = [
                'layaway_default_down_payment' => $request->default_down_payment_percentage,
                'layaway_minimum_down_payment' => $request->minimum_down_payment_percentage,
                'layaway_maximum_down_payment' => $request->maximum_down_payment_percentage,
                'layaway_default_deadline_days' => $request->default_payment_deadline_days,
                'layaway_maximum_deadline_days' => $request->maximum_payment_deadline_days,
                'layaway_number_prefix' => $request->layaway_number_prefix,
                'layaway_enable_overdue_notifications' => $request->has('enable_overdue_notifications') ? 1 : 0,
                'layaway_overdue_notification_days' => $request->overdue_notification_days,
                'layaway_enable_due_date_reminders' => $request->has('enable_due_date_reminders') ? 1 : 0,
                'layaway_due_date_reminder_days' => $request->due_date_reminder_days,
                'layaway_auto_expire_days' => $request->auto_expire_overdue_days,
                'layaway_allow_partial_payments' => $request->has('allow_partial_payments') ? 1 : 0,
                'layaway_require_signature' => $request->has('require_customer_signature') ? 1 : 0,
                'layaway_enable_receipts' => $request->has('enable_layaway_receipts') ? 1 : 0,
                'layaway_enable_terms' => $request->has('enable_layaway_terms') ? 1 : 0,
                'layaway_terms_text' => $request->layaway_terms_text
            ];

            foreach ($settings as $key => $value) {
                System::setProperty($key, $value);
            }

            $output = [
                'success' => true,
                'msg' => __('layaway::lang.settings_updated_successfully')
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->action('\\Modules\\Layaway\\Http\\Controllers\\LayawaySettingController@index')
            ->with('status', $output);
    }

    /**
     * Reset settings to default
     * @return Response
     */
    public function reset()
    {
        if (!auth()->user()->can('layaway.settings')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $default_settings = [
                'layaway_default_down_payment' => 20,
                'layaway_minimum_down_payment' => 10,
                'layaway_maximum_down_payment' => 100,
                'layaway_default_deadline_days' => 30,
                'layaway_maximum_deadline_days' => 365,
                'layaway_number_prefix' => 'LAY',
                'layaway_enable_overdue_notifications' => 1,
                'layaway_overdue_notification_days' => 3,
                'layaway_enable_due_date_reminders' => 1,
                'layaway_due_date_reminder_days' => 7,
                'layaway_auto_expire_days' => 90,
                'layaway_allow_partial_payments' => 1,
                'layaway_require_signature' => 0,
                'layaway_enable_receipts' => 1,
                'layaway_enable_terms' => 1,
                'layaway_terms_text' => ''
            ];

            foreach ($default_settings as $key => $value) {
                System::setProperty($key, $value);
            }

            $output = [
                'success' => true,
                'msg' => __('layaway::lang.settings_reset_successfully')
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->action('\\Modules\\Layaway\\Http\\Controllers\\LayawaySettingController@index')
            ->with('status', $output);
    }
}