<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\App\Http\Controllers\ProductController;
use Modules\Product\App\Http\Controllers\RestaurantProductController;

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

    Route::resource('product', ProductController::class);

});

Route::group(['as'=> 'restaurant.', 'prefix' => 'restaurant', 'middleware' => ['restaurant', 'HtmlSpecialchars']],function (){

    Route::resource('product', RestaurantProductController::class);

});

