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

Route::group(['middleware' => ['web', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu'], 'prefix' => 'inventory-multi'], function () {
    Route::get('/dashboard', 'InventoryController@dashboard')->name('inventory-multi.dashboard');
    Route::get('/inventory', 'InventoryController@index')->name('inventory-multi.inventory');
    Route::get('/location-data', 'InventoryController@getLocationData')->name('inventory-multi.location-data');

    Route::get('/transfers', 'TransferController@index')->name('inventory-multi.transfers.index');
    Route::get('/transfers/create', 'TransferController@create')->name('inventory-multi.transfers.create');
    Route::post('/transfers', 'TransferController@store')->name('inventory-multi.transfers.store');
    Route::get('/transfers/{id}', 'TransferController@show')->name('inventory-multi.transfers.show');
    Route::put('/transfers/{id}', 'TransferController@update')->name('inventory-multi.transfers.update');
    Route::delete('/transfers/{id}', 'TransferController@destroy')->name('inventory-multi.transfers.destroy');
    Route::post('/transfers/{id}/complete', 'TransferController@complete')->name('inventory-multi.transfers.complete');

    Route::get('/reports', 'ReportController@index')->name('inventory-multi.reports.index');
    Route::get('/reports/stock-summary', 'ReportController@stockSummary')->name('inventory-multi.reports.stock-summary');
    Route::get('/reports/transfer-history', 'ReportController@transferHistory')->name('inventory-multi.reports.transfer-history');
    Route::get('/reports/low-stock', 'ReportController@lowStock')->name('inventory-multi.reports.low-stock');
});
