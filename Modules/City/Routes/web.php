<?php

use Modules\City\Http\Controllers\CityController;

Route::group(['as'=> 'admin.', 'prefix' => 'admin/restaurant', 'middleware' => ['auth:admin']],function (){

    Route::resource('city', CityController::class);

});
