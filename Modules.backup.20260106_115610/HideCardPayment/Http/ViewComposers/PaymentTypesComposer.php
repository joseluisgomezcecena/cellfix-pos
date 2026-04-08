<?php

namespace Modules\HideCardPayment\Http\ViewComposers;

use Illuminate\View\View;

class PaymentTypesComposer
{
    /**
     * Bind data to the view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view)
    {
        // Check if module is enabled
        if (!config('hidecardpayment.enabled', true)) {
            return;
        }

        // Get the payment_types variable from the view
        $paymentTypes = $view->getData()['payment_types'] ?? [];

        // Get hidden payment types from config
        $hiddenPaymentTypes = config('hidecardpayment.hidden_payment_types', ['card']);

        // Remove hidden payment types
        foreach ($hiddenPaymentTypes as $paymentType) {
            if (isset($paymentTypes[$paymentType])) {
                unset($paymentTypes[$paymentType]);
            }
        }

        // Update the view with the filtered payment types
        $view->with('payment_types', $paymentTypes);
    }
}
