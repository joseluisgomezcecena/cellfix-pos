<?php

namespace Modules\HideCardPayment\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Modules\HideCardPayment\Http\ViewComposers\PaymentTypesComposer;

class HideCardPaymentServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected $moduleName = 'HideCardPayment';

    /**
     * @var string $moduleNameLower
     */
    protected $moduleNameLower = 'hidecardpayment';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerViewComposers();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'),
            $this->moduleNameLower
        );
    }

    /**
     * Register view composers to filter payment types.
     *
     * @return void
     */
    protected function registerViewComposers()
    {
        // Apply to all POS-related views that use payment_types
        View::composer([
            'sale_pos.create',
            'sale_pos.edit',
            'sale_pos.partials.pos_form_actions',
            'sale_pos.partials.payment_modal',
            'sale_pos.partials.payment_row_form',
            'sale_pos.partials.payment_row',
            'sell.create',
            'sell.edit',
            'transaction_payment.*',
        ], PaymentTypesComposer::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
