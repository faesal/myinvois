<?php

namespace App\Providers;

use View;
use Cache;
use Exception;
use Throwable;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Log;
use Modules\Page\App\Models\Footer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use Modules\Product\App\Models\Product;
use Modules\Currency\App\Models\Currency;
use Modules\Language\App\Models\Language;
use Modules\GlobalSetting\App\Models\GlobalSetting;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        try{
            Cache::rememberForever('setting', function(){
                $setting_data = GlobalSetting::get();

                $setting = array();

                foreach($setting_data as $data_item){
                    $setting[$data_item->key] = $data_item->value;
                }

                $setting = (object) $setting;

                return $setting;
            });


            View::composer('*', function($view){

               

               

            });

        }catch(Exception $ex){
            Log::info('AppServiceProvider : '. $ex->getMessage());

            Artisan::call('optimize:clear');
        }



    }
}
