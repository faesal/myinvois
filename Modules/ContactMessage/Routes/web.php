<?php

use Modules\ContactMessage\Http\Controllers\ContactMessageController;
use Modules\ContactMessage\Http\Controllers\Frontend\ContactMessageController as FrontendContactMessageController;

Route::group(['as'=> 'admin.', 'prefix' => 'admin', 'middleware' => ['admin']],function (){

    Route::controller(ContactMessageController::class)->group(function () {
        Route::get('contact-message', 'contact_message')->name('contact-message');
        Route::get('show-message/{id}', 'show_message')->name('show-message');
        Route::delete('delete-contact-message/{id}', 'delete_message')->name('delete-contact-message');
        Route::put('contact-message-setting', 'contact_message_setting')->name('contact-message-setting');
    });

});


Route::group(['middleware' => ['HtmlSpecialchars']], function () {
    Route::post('store-contact-message', [FrontendContactMessageController::class, 'store_contact_message'])->name('store-contact-message');
});
