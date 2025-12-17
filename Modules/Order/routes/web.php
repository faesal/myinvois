<?php

use Illuminate\Support\Facades\Route;
use Modules\Order\App\Http\Controllers\OrderController;
use Modules\Order\App\Http\Controllers\RestaurantOrderController;

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

Route::group(['as'=> 'admin.', 'prefix' => 'admin', 'middleware' => ['admin']],function (){

    Route::any('order', [OrderController::class, 'index'])->name('order.index');
    Route::get('order-details/{id}', [OrderController::class, 'order_details'])->name('order.details');
    Route::delete('order-delete/{id}',[OrderController::class,'delete_order'])->name('order.delete');
    Route::post('order-status-change/{id}',[OrderController::class,'order_status_change'])->name('order.status.change');
    Route::post('payment-status-change/{id}',[OrderController::class,'payment_status_change'])->name('payment.status.change');
    Route::get('/invoice/{id}',[OrderController::class,'invoice'])->name('order.invoice');
    

    //Delivery man route
    
    Route::post('deliveryman/{id}',[OrderController::class,'deliveryman'])->name('deliveryman');

});



Route::group(['as'=> 'restaurant.', 'prefix' => 'restaurant', 'middleware' => ['restaurant']],function (){

    Route::resource('order', RestaurantOrderController::class);
    Route::get('order-details/{id}', [RestaurantOrderController::class, 'order_details'])->name('order.details');
    Route::get('/invoice/{id}',[RestaurantOrderController::class,'invoice'])->name('order.invoice');
    Route::post('order-status-change/{id}',[RestaurantOrderController::class,'order_status_change'])->name('order.status.change');
});
