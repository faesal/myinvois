<?php

use Illuminate\Support\Facades\Route;
use Modules\Page\App\Http\Controllers\PageController;
use Modules\Page\App\Http\Controllers\AboutusController;
use Modules\Page\App\Http\Controllers\PrivacyController;
use Modules\Page\App\Http\Controllers\HomepageController;
use Modules\Page\App\Http\Controllers\ContactUsController;
use Modules\Page\App\Http\Controllers\FooterContrllerController;
use Modules\Page\App\Http\Controllers\TermsConditiondController;
use Modules\Page\App\Http\Controllers\FooterImageGalleryController;

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
    Route::get('terms-conditions', [TermsConditiondController::class, 'index'])->name('terms-conditions');
    Route::put('update-terms-conditions', [TermsConditiondController::class, 'update'])->name('update-terms-conditions');

    Route::get('privacy-policy', [PrivacyController::class, 'index'])->name('privacy-policy');
    Route::put('update-privacy-policy', [PrivacyController::class, 'update'])->name('update-privacy-policy');

    Route::get('intro-section', [HomepageController::class, 'intro_section'])->name('intro-section');
    Route::put('update-intro-section', [HomepageController::class, 'update_intro_section'])->name('update-intro-section');

    Route::get('working-step', [HomepageController::class, 'working_step'])->name('working-step');
    Route::put('update-working-step', [HomepageController::class, 'update_working_step'])->name('update-working-step');

    Route::get('join-restaurant', [HomepageController::class, 'join_restaurant'])->name('join-restaurant');
    Route::put('update-join-restaurant', [HomepageController::class, 'update_join_restaurant'])->name('update-join-restaurant');

    Route::get('mobile-app', [HomepageController::class, 'mobile_app'])->name('mobile-app');
    Route::put('update-mobile-app', [HomepageController::class, 'update_mobile_app'])->name('update-mobile-app');

    Route::get('about-us', [AboutusController::class, 'about_us'])->name('about-us');
    Route::put('update-about-us', [AboutusController::class, 'update'])->name('update-about-us');

    Route::get('contact-us', [ContactUsController::class, 'index'])->name('contact-us');
    Route::put('update-contact-us', [ContactUsController::class, 'update'])->name('update-contact-us');

    Route::get('footer-image-gallery', [FooterImageGalleryController::class, 'footer_image_gallery'])->name('footer-image-gallery');
    Route::put('update-footer-image-gallery', [FooterImageGalleryController::class, 'update_footer_image_gallery'])->name('update-footer-image-gallery');

    Route::get('footer', [FooterContrllerController::class, 'index'])->name('footer');
    Route::put('update-footer', [FooterContrllerController::class, 'update'])->name('update-footer');


});
