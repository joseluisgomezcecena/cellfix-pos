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

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu'])->prefix('cellphone')->group(function() {
    Route::get('/', 'CellphoneController@index')->name('cellphone.index');
    Route::get('/create', 'CellphoneController@create')->name('cellphone.create');
    Route::post('/', 'CellphoneController@store')->name('cellphone.store');
    Route::get('/{id}', 'CellphoneController@show')->name('cellphone.show');
    Route::get('/{id}/edit', 'CellphoneController@edit')->name('cellphone.edit');
    Route::put('/{id}', 'CellphoneController@update')->name('cellphone.update');
    Route::delete('/{id}', 'CellphoneController@destroy')->name('cellphone.destroy');

    // Reports
    Route::get('/reports', 'CellphoneReportController@index')->name('cellphone.reports');
    Route::get('/reports/export', 'CellphoneReportController@export')->name('cellphone.export');
});
