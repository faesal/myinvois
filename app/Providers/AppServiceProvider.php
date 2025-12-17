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

                $general_setting = Cache::get('setting');

                $language_list = Language::where('status', 1)->get();
                $currency_list = Currency::where('status', 'active')->get();

                $footer = Footer::first();

                if (Auth::guard('web')->check()) {
                    $userId = Auth::guard('web')->id();

                    $wishlistItems = Wishlist::where('user_id', $userId)->get();

                    $wishlist = $wishlistItems->map(function ($item) {
                        $product = Product::find($item->product_id);
                        return [
                            'wishlist_item' => $item,
                            'product' => $product,
                            'translated_name' => $product->name,
                            'item_id' => $product->id,
                        ];
                    });

                    $view->with('wishlist', $wishlist);
                } else {
                    $view->with('wishlist', collect());
                }





                $view->with('general_setting', $general_setting);
                $view->with('language_list', $language_list);
                $view->with('currency_list', $currency_list);
                $view->with('footer', $footer);

            });

        }catch(Exception $ex){
            Log::info('AppServiceProvider : '. $ex->getMessage());

            Artisan::call('optimize:clear');
        }



    }
}
