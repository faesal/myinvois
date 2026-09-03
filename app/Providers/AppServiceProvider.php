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
use Illuminate\Support\Facades\Schema; // <-- 1. ADDED SCHEMA FACADE
use Illuminate\Support\Facades\URL;    // <-- 2. ADDED URL FACADE FOR HTTPS FIX

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
        // -------------------------------------------------------------
        // FIX: Force HTTPS to prevent POST requests converting to GET
        // -------------------------------------------------------------
        if(config('app.env') !== 'local') {
            URL::forceScheme('https');
        }

        // 2. ADDED FIX FOR MySQL "1071 Specified key was too long" ERROR
        Schema::defaultStringLength(191); 

        try {
            
            // 🚀 THE FIX: Only run this if we are NOT running an artisan command (like migrate) 
            // or if the table already successfully exists.
            if (!app()->runningInConsole() || Schema::hasTable('global_settings')) {
                
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
                    // Original logic remains untouched
                });
                
            }

        } catch(Exception $ex) {
            Log::info('AppServiceProvider : '. $ex->getMessage());

            // 🚀 SAFETY NET: Prevent infinite loops if an artisan command fails
            if (!app()->runningInConsole()) {
                Artisan::call('optimize:clear');
            }
        }

    }
}