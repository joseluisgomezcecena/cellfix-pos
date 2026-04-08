<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['middleware' => ['web', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu'], 'prefix' => 'layaway'], function () {

    // Layaway Management Routes
    Route::get('/', 'LayawayController@index')->name('layaway.index');
    Route::get('/create', 'LayawayController@create')->name('layaway.create');
    Route::post('/', 'LayawayController@store')->name('layaway.store');
    Route::get('/{id}', 'LayawayController@show')->name('layaway.show');
    Route::get('/{id}/edit', 'LayawayController@edit')->name('layaway.edit');
    Route::put('/{id}', 'LayawayController@update')->name('layaway.update');
    Route::delete('/{id}', 'LayawayController@destroy')->name('layaway.destroy');

    // Layaway Actions
    Route::post('/{id}/cancel', 'LayawayController@cancel')->name('layaway.cancel');
    Route::post('/{id}/activate', 'LayawayController@activate')->name('layaway.activate');
    Route::get('/{id}/print', 'LayawayController@printReceipt')->name('layaway.print');
    Route::get('/product-row/{row_index}', 'LayawayController@getProductRow')->name('layaway.product_row');

    // Payment Routes
    Route::prefix('payments')->group(function () {
        Route::get('/', 'LayawayPaymentController@index')->name('layaway.payments.index');
        Route::get('/{layaway_id}/create', 'LayawayPaymentController@create')->name('layaway.payments.create');
        Route::post('/{layaway_id}', 'LayawayPaymentController@store')->name('layaway.payments.store');
        Route::get('/receipt/{id}', 'LayawayPaymentController@printReceipt')->name('layaway.payments.receipt');
        Route::get('/history/{layaway_id}', 'LayawayPaymentController@history')->name('layaway.payments.history');
    });

    // Reports Routes
    Route::prefix('reports')->group(function () {
        Route::get('/', 'LayawayReportController@index')->name('layaway.reports.index');
        Route::get('/summary', 'LayawayReportController@summary')->name('layaway.reports.summary');
        Route::get('/collection-forecast', 'LayawayReportController@collectionForecast')->name('layaway.reports.forecast');
        Route::get('/customer-history', 'LayawayReportController@customerHistory')->name('layaway.reports.customer');
        Route::get('/overdue', 'LayawayReportController@overdue')->name('layaway.reports.overdue');
        Route::get('/payment-methods', 'LayawayReportController@paymentMethods')->name('layaway.reports.methods');
        Route::get('/export/{type}', 'LayawayReportController@export')->name('layaway.reports.export');
    });

    // Settings Routes
    Route::prefix('settings')->group(function () {
        Route::get('/', 'LayawaySettingController@index')->name('layaway.settings.index');
        Route::put('/', 'LayawaySettingController@update')->name('layaway.settings.update');
        Route::post('/reset', 'LayawaySettingController@reset')->name('layaway.settings.reset');
    });

    // Ajax Routes
    Route::prefix('ajax')->group(function () {
        Route::get('/product-details/{id}', 'LayawayController@getProductDetails')->name('layaway.ajax.product_details');
        Route::get('/customer-details/{id}', 'LayawayController@getCustomerDetails')->name('layaway.ajax.customer_details');
        Route::get('/calculate-payment', 'LayawayController@calculatePayment')->name('layaway.ajax.calculate_payment');
    });
});