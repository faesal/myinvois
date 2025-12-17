<?php

use Modules\Newsletter\Http\Controllers\Admin\NewsletterController;
use Modules\Newsletter\Http\Controllers\NewsletterController as FrontendNewsletterController;



Route::group(['as'=> 'admin.', 'prefix' => 'admin', 'middleware' => ['admin']],function (){

    Route::controller(NewsletterController::class)->group(function () {
        Route::get('subscriber-list', 'subscriber_list')->name('subscriber-list');
        Route::get('subscriber-email-box', 'subscriber_email')->name('subscriber-email');
        Route::post('send-subscriber-email', 'send_subscriber_email')->name('send-subscriber-email');
        Route::delete('delete-subscriber/{id}', 'delete_subscriber')->name('delete-subscriber');

    });
});

Route::group(['middleware' => ['HtmlSpecialchars', 'MaintenanceMode']], function () {
    Route::post('newsletter-request', [FrontendNewsletterController::class, 'newsletter_request'])->name('newsletter-request');
    Route::get('newsletter-verification', [FrontendNewsletterController::class, 'newsletter_verification'])->name('newsletter-verification');
});
