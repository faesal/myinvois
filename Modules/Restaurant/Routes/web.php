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


use Modules\Restaurant\Http\Controllers\RestaurantController;

Route::group(['as'=> 'admin.', 'prefix' => 'admin/restaurant', 'middleware' => ['auth:admin']],function (){
    
    Route::resource('restaurants', RestaurantController::class);
    Route::put('trusted-status/{id}', [RestaurantController::class, 'trusted_status'])->name('restaruant-trusted');


});

