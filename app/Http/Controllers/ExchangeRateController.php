<?php

namespace App\Http\Controllers;

use App\Business;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    public function edit()
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business = Business::findOrFail($business_id);

        return view('exchange_rate.edit', compact('business'));
    }

    public function update(Request $request)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'cash_exchange_rate' => 'required|numeric|min:0',
        ]);

        try {
            $business_id = $request->session()->get('user.business_id');
            $business = Business::findOrFail($business_id);

            $business->cash_exchange_rate = (float) $request->input('cash_exchange_rate');
            $business->save();

            // Refresh in-session value so the cash modal reflects the new rate immediately
            $request->session()->put('business.cash_exchange_rate', $business->cash_exchange_rate);

            $output = ['success' => 1, 'msg' => __('lang_v1.exchange_rate_updated')];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->route('exchange-rate.edit')->with('status', $output);
    }
}
