<?php

use Illuminate\Support\Facades\Route;
use Modules\Addon\App\Http\Controllers\AddonController;
use Modules\Addon\App\Http\Controllers\RestaurantAddonController;

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

Route::group(['as'=> 'admin.', 'prefix' => 'admin/restaurant', 'middleware' => ['admin']],function (){

    Route::resource('addon', AddonController::class);

    Route::get('ajax-addon-list/{id}', [AddonController::class, 'ajax_addon_list']);
    Route::get('ajax-addon-list-edit/{id}', [AddonController::class, 'ajax_addon_list_edit']);
});

Route::group(['as'=> 'restaurant.', 'prefix' => 'restaurant', 'middleware' => ['restaurant']],function (){

    Route::resource('addon', RestaurantAddonController::class);

});
