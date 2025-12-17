<?php

use Modules\Cuisine\Http\Controllers\CuisineController;



Route::group(['as'=> 'admin.', 'prefix' => 'admin/restaurant', 'middleware' => ['auth:admin']],function (){

    Route::resource('cuisine', CuisineController::class);

});

