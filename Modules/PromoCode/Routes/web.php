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

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu'])->prefix('promo-codes')->group(function() {
    // Admin routes for managing promo companies
    Route::resource('promo-companies', 'PromoCompanyController');

    // Business autocomplete for admin forms
    Route::get('/businesses', 'PromoCodeController@getBusinesses')->name('promo-codes.businesses');

    // POS API routes for promo code verification and calculation
    Route::post('/verify', 'PromoCodeController@verify')->name('promo-codes.verify');
    Route::post('/calculate', 'PromoCodeController@calculate')->name('promo-codes.calculate');
    Route::post('/clear', 'PromoCodeController@clear')->name('promo-codes.clear');
});
