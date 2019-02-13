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

Route::permanentRedirect('/', '/vk/ads');


Route::get('vk/ads/login', 'Vk\Ads@login')->name('login');
Route::get('vk/ads/auth', 'Vk\Ads@auth')->name('auth');

Route::group(['middleware' => ['vktoken'], 'prefix' => 'vk/ads', 'namespace' => 'Vk'], function () {
    Route::get('/', 'Ads@index')->name('cabinets');

    Route::get('logout', 'Ads@logout')->name('logout');

    Route::get('cabinet/{cabinetId}/{cabinetName}', 'Ads@cabinet')->name('cabinet');
    Route::get('cabinet/{cabinetId}/{cabinetName}/campaign/{campaignId}/{campaignName}', 'Ads@campaign')->name('campaign');

    Route::post('destroy', 'Ads@destroy');
    Route::post('update', 'Ads@update');
});