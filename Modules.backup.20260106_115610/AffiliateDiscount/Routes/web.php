<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for the module. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group.
|
*/

Route::middleware(['web', 'auth', 'SetSessionData'])->group(function() {

    // Installation routes
    Route::prefix('modules/affiliatediscount')->group(function() {
        Route::get('/install', 'InstallController@index');
        Route::post('/install', 'InstallController@install');
        Route::get('/install/uninstall', 'InstallController@uninstall');
        Route::get('/install/update', 'InstallController@update');
    });
});

// Admin routes - with full middleware stack including AdminSidebarMenu
Route::group(['middleware' => ['web', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu'], 'prefix' => 'affiliate-discounts'], function() {
    Route::middleware('can:manage_affiliate_discounts')->group(function() {
        Route::get('/', 'AffiliateDiscountController@index')->name('affiliate-discounts.index');
        Route::get('/create', 'AffiliateDiscountController@create')->name('affiliate-discounts.create');
        Route::post('/', 'AffiliateDiscountController@store')->name('affiliate-discounts.store');
        Route::get('/{id}/edit', 'AffiliateDiscountController@edit')->name('affiliate-discounts.edit');
        Route::put('/{id}', 'AffiliateDiscountController@update')->name('affiliate-discounts.update');
        Route::delete('/{id}', 'AffiliateDiscountController@destroy')->name('affiliate-discounts.destroy');

        // Discount options management
        Route::get('/{id}/options', 'AffiliateDiscountController@manageOptions')->name('affiliate-discounts.options');
        Route::post('/{id}/options', 'AffiliateDiscountController@storeOption')->name('affiliate-discounts.store-option');
        Route::put('/options/{optionId}', 'AffiliateDiscountController@updateOption')->name('affiliate-discounts.update-option');
        Route::delete('/options/{optionId}', 'AffiliateDiscountController@destroyOption')->name('affiliate-discounts.destroy-option');

        // Toggle active status
        Route::post('/{id}/toggle', 'AffiliateDiscountController@toggleStatus')->name('affiliate-discounts.toggle');

        // Reports
        Route::get('/reports', 'AffiliateDiscountController@reports')->name('affiliate-discounts.reports');
        Route::get('/reports/data', 'AffiliateDiscountController@reportsData')->name('affiliate-discounts.reports-data');
    });
});

// POS API routes - for affiliate discount functionality in POS
Route::middleware(['web', 'auth', 'SetSessionData'])->prefix('api/affiliate-discount')->group(function() {

    // Get active affiliated businesses
    Route::get('/businesses', 'PosAffiliateController@getBusinesses');

    // Get discount options for a specific business
    Route::get('/options/{businessId}', 'PosAffiliateController@getOptions');

    // Save discount selections to session
    Route::post('/save-selection', 'PosAffiliateController@saveSelection');

    // Calculate discounts for cart items
    Route::post('/calculate', 'PosAffiliateController@calculateDiscounts');

    // Quick add new discount option from POS
    Route::post('/add-option', 'PosAffiliateController@addOption')
        ->middleware('can:add_discount_options_from_pos');

    // Get current session discounts
    Route::get('/active-session', 'PosAffiliateController@getActiveSession');

    // Clear session discounts
    Route::delete('/clear-session', 'PosAffiliateController@clearSession');

    // Save transaction discounts (called when finalizing sale)
    Route::post('/save-transaction', 'PosAffiliateController@saveTransaction');
});
