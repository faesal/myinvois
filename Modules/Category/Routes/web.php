<?php

use Modules\Category\Http\Controllers\CategoryController;
use Modules\Category\Http\Controllers\RestaurantCategoryController;



Route::group(['as'=> 'admin.', 'prefix' => 'admin/restaurant', 'middleware' => ['admin']],function (){

    Route::resource('category', CategoryController::class);

});

Route::group(['as'=> 'restaurant.', 'prefix' => 'restaurant', 'middleware' => ['restaurant']],function (){

    Route::get('category', [RestaurantCategoryController::class, 'list'])->name('category.index');

});

