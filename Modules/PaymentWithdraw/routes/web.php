<?php

use Illuminate\Support\Facades\Route;
use Modules\PaymentWithdraw\App\Http\Controllers\PaymentWithdrawController;
use Modules\PaymentWithdraw\App\Http\Controllers\WithdrawMethodController;
use Modules\PaymentWithdraw\App\Http\Controllers\deliveryman\PaymentWithdrawController as DeliverymanPaymentWithdrawController;
use Modules\PaymentWithdraw\App\Http\Controllers\deliveryman\WithdrawMethodController as DeliverymanWithdrawMethodController;
use Modules\PaymentWithdraw\App\Http\Controllers\Seller\WithdrawController;

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

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['admin']], function () {

    Route::resource('withdraw-list', PaymentWithdrawController::class);
    Route::post('withdraw-approval/{id}', [PaymentWithdrawController::class, 'withdraw_approval'])->name('withdraw-approval');
    Route::post('withdraw-rejected/{id}', [PaymentWithdrawController::class, 'withdraw_rejected'])->name('withdraw-rejected');

    Route::resource('withdraw-methods', WithdrawMethodController::class);

    Route::resource('deliveryman-withdraw-list', DeliverymanPaymentWithdrawController::class);
    Route::resource('deliveryman-withdraw-methods', WithdrawMethodController::class);
    Route::post('deliveryman-withdraw-approval/{id}', [DeliverymanPaymentWithdrawController::class, 'withdraw_approval'])->name('deliveryman-withdraw-approval');
    Route::post('deliveryman-withdraw-rejected/{id}', [DeliverymanPaymentWithdrawController::class, 'withdraw_rejected'])->name('deliveryman-withdraw-rejected');
});


Route::group(['prefix' => 'restaurant', 'as' => 'restaurant.', 'middleware' => ['restaurant', 'HtmlSpecialchars']], function () {

    Route::resource('my-withdraw', WithdrawController::class);

});


