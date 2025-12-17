<?php

use Illuminate\Support\Facades\Route;
use Modules\SmsSetting\App\Http\Controllers\SmsSettingController;

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


Route::group(['as'=> 'admin.', 'prefix' => 'admin', 'middleware' => ['auth:admin']], function () {
    Route::get('sms-setting', [SmsSettingController::class, 'index'])->name('sms-setting');
    Route::put('update-sms-setting', [SmsSettingController::class, 'update'])->name('update-sms-setting');

    Route::get('twilio-sms-setting', [SmsSettingController::class, 'twilio_configuration'])->name('twilio-sms-setting');
    Route::put('update-twilio-sms-setting', [SmsSettingController::class, 'update_twilio_configuration'])->name('update-twilio-sms-setting');

    Route::get('biztech-sms-setting', [SmsSettingController::class, 'biztech_configuration'])->name('biztech-sms-setting');
    Route::put('update-biztech-sms-setting', [SmsSettingController::class, 'update_biztech_configuration'])->name('update-biztech-sms-setting');



    Route::get('sms-template', [SmsSettingController::class, 'sms_template'])->name('sms-template');
    Route::get('edit-sms-template/{id}', [SmsSettingController::class, 'edit_sms_template'])->name('edit-sms-template');
    Route::put('update-sms-template/{id}', [SmsSettingController::class, 'update_sms_template'])->name('update-sms-template');


});
