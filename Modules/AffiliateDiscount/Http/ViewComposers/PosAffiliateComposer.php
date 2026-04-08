<?php

namespace Modules\AffiliateDiscount\Http\ViewComposers;

use Illuminate\View\View;
use Modules\AffiliateDiscount\Entities\AffiliatedBusiness;

class PosAffiliateComposer
{
    /**
     * Bind data to the view.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return;
        }

        // Check if user has permission
        if (!auth()->user()->can('use_affiliate_discounts_in_pos')) {
            $view->with('show_affiliate_discount', false);
            return;
        }

        $business_id = session('user.business_id');

        // Get active affiliated businesses
        $affiliated_businesses = AffiliatedBusiness::with('contact')
            ->where('business_id', $business_id)
            ->active()
            ->get();

        // Get active session data
        $sessionKey = config('affiliatediscount.session_key');
        $activeSession = session($sessionKey);

        // Categories configuration
        $categories = config('affiliatediscount.categories', []);

        // Pass data to view
        $view->with([
            'show_affiliate_discount' => true,
            'affiliated_businesses' => $affiliated_businesses,
            'affiliate_active_session' => $activeSession,
            'affiliate_categories' => $categories,
            'affiliate_modal_enabled' => count($affiliated_businesses) > 0,
        ]);
    }
}
