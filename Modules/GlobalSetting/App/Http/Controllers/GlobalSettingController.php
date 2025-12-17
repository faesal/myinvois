<?php

namespace Modules\GlobalSetting\App\Http\Controllers;

use Cache, Image, File, Str, Artisan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Models\AboutUsTranslation;
use App\Models\Admin;
use App\Models\Banner;
use App\Models\DeliveryMan;
use App\Models\DeliveryManWithdraw;
use App\Models\DeliveryManWithdrawMethod;
use App\Models\OfferProduct;
use App\Models\RestaurantWishlist;
use App\Models\Review;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Wishlist;
use Illuminate\Http\RedirectResponse;
use Modules\Addon\App\Models\Addon;
use Modules\Addon\App\Models\AddonTranslation;
use Modules\Blog\App\Models\Blog;
use Modules\Blog\App\Models\BlogCategory;
use Modules\Blog\App\Models\BlogCategoryTranslation;
use Modules\Blog\App\Models\BlogComment;
use Modules\Blog\App\Models\BlogTranslation;
use Modules\Category\Entities\Category;
use Modules\Category\Entities\CategoryTranslation;
use Modules\City\Entities\City;
use Modules\City\Entities\CityTranslation;
use Modules\ContactMessage\Entities\ContactMessage;
use Modules\Coupon\App\Models\Coupon;
use Modules\Cuisine\Entities\Cuisine;
use Modules\Cuisine\Entities\CuisineTranslation;
use Modules\GlobalSetting\App\Models\GlobalSetting;

use Modules\GlobalSetting\App\Http\Requests\TawkChatRequest;
use Modules\GlobalSetting\App\Http\Requests\SocialLoginRequest;
use Modules\GlobalSetting\App\Http\Requests\CookieConsentRequest;
use Modules\GlobalSetting\App\Http\Requests\FacebookPixelRequest;
use Modules\GlobalSetting\App\Http\Requests\GeneralSettingRequest;
use Modules\GlobalSetting\App\Http\Requests\GoogleAnalyticRequest;
use Modules\GlobalSetting\App\Http\Requests\GoogleRecaptchaRequest;
use Modules\Language\App\Models\Language;
use Modules\Newsletter\Entities\Subscriber;
use Modules\Order\App\Models\Order;
use Modules\Page\App\Models\HomepageTranslation;
use Modules\Page\App\Models\PrivacyPolicy;
use Modules\Page\App\Models\TermAndCondition;
use Modules\PaymentWithdraw\App\Models\SellerWithdraw;
use Modules\PaymentWithdraw\App\Models\WithdrawMethod;
use Modules\Product\App\Models\Product;
use Modules\Product\App\Models\ProductTranslation;
use Modules\Restaurant\Entities\Restaurant;

class GlobalSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function general_setting()
    {
        return view('globalsetting::index');
    }

    public function update_general_setting(GeneralSettingRequest $request)
    {

        GlobalSetting::where('key', 'app_name')->update(['value' => $request->app_name]);
        GlobalSetting::where('key', 'contact_message_mail')->update(['value' => $request->contact_message_mail]);
        GlobalSetting::where('key', 'timezone')->update(['value' => $request->timezone]);
        GlobalSetting::where('key', 'delivery_charge')->update(['value' => $request->delivery_charge]);
        GlobalSetting::where('key', 'preloader_status')->update(['value' => $request->preloader_status]);
        GlobalSetting::where('key', 'commission_per_delivery')->update(['value' => $request->commission_per_delivery]);
        GlobalSetting::where('key', 'commission_per_sale')->update(['value' => $request->commission_per_sale]);
        GlobalSetting::where('key', 'commission_type')->update(['value' => $request->commission_type]);

        $this->set_cache_setting();

        $notify_message = trans('translate.Updated successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);

    }


    public function update_logo_favicon(Request $request)
    {

        $logo_setting = GlobalSetting::where('key', 'logo')->first();


        if($request->logo){
            $old_logo = $logo_setting->value;
            $image = $request->logo;
            $ext = $image->getClientOriginalExtension();
            $logo_name = 'logo-'.date('Y-m-d-h-i-s-').rand(999,9999).'.'.$ext;
            $logo_name = 'uploads/website-images/'.$logo_name;
            $request->logo->move(public_path('uploads/website-images'), $logo_name);
            $logo_setting->value = $logo_name;
            $logo_setting->save();

            if($old_logo){
                if(File::exists(public_path().'/'.$old_logo))unlink(public_path().'/'.$old_logo);
            }
        }



        $footer_logo_setting = GlobalSetting::where('key', 'footer_logo')->first();

        if($request->footer_logo){
            $old_logo = $footer_logo_setting->value;
            $image = $request->footer_logo;
            $ext = $image->getClientOriginalExtension();
            $logo_name = 'footer-logo-'.date('Y-m-d-h-i-s-').rand(999,9999).'.'.$ext;
            $logo_name = 'uploads/website-images/'.$logo_name;
            $request->footer_logo->move(public_path('uploads/website-images'), $logo_name);
            $footer_logo_setting->value = $logo_name;
            $footer_logo_setting->save();
            if($old_logo){
                if(File::exists(public_path().'/'.$old_logo))unlink(public_path().'/'.$old_logo);
            }
        }



        $logo_setting = GlobalSetting::where('key', 'favicon')->first();

        if($request->favicon){
            $old_favicon = $logo_setting->value;
            $favicon = $request->favicon;
            $ext = $favicon->getClientOriginalExtension();
            $favicon_name = 'favicon-'.date('Y-m-d-h-i-s-').rand(999,9999).'.'.$ext;
            $favicon_name = 'uploads/website-images/'.$favicon_name;
            Image::make($favicon)
                    ->save(public_path().'/'.$favicon_name);
            $logo_setting->value = $favicon_name;
            $logo_setting->save();
            if($old_favicon){
                if(File::exists(public_path().'/'.$old_favicon))unlink(public_path().'/'.$old_favicon);
            }
        }





        $this->set_cache_setting();

        $notify_message = trans('translate.Updated successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);

    }

    public function update_google_captcha(GoogleRecaptchaRequest $request){

        GlobalSetting::where('key', 'recaptcha_site_key')->update(['value' => $request->site_key]);
        GlobalSetting::where('key', 'recaptcha_secret_key')->update(['value' => $request->secret_key]);
        GlobalSetting::where('key', 'recaptcha_status')->update(['value' => $request->status ? 1 : 0]);

        $this->set_cache_setting();

        $notify_message = trans('translate.Updated successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);


    }

    public function update_tawk_chat(TawkChatRequest $request){

        GlobalSetting::where('key', 'tawk_chat_link')->update(['value' => $request->chat_link]);
        GlobalSetting::where('key', 'tawk_status')->update(['value' => $request->status ? 1 : 0]);

        $this->set_cache_setting();

        $notify_message = trans('translate.Updated successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);

    }

    public function update_google_analytic(GoogleAnalyticRequest $request){

        GlobalSetting::where('key', 'google_analytic_id')->update(['value' => $request->analytic_id]);
        GlobalSetting::where('key', 'google_analytic_status')->update(['value' => $request->status ? 1 : 0]);

        $this->set_cache_setting();

        $notify_message = trans('translate.Updated successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);

    }

    public function update_facebook_pixel(FacebookPixelRequest $request){

        GlobalSetting::where('key', 'pixel_app_id')->update(['value' => $request->app_id]);
        GlobalSetting::where('key', 'pixel_status')->update(['value' => $request->status ? 1 : 0]);

        $this->set_cache_setting();

        $notify_message = trans('translate.Updated successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);

    }


    public function database_clear(){

        try {
            Banner::truncate();
            Addon::truncate();
            AddonTranslation::truncate();
            Blog::truncate();
            BlogTranslation::truncate();
            BlogCategory::truncate();
            BlogCategoryTranslation::truncate();
            BlogComment::truncate();
            Category::truncate();
            CategoryTranslation::truncate();
            ContactMessage::truncate();
            Coupon::truncate();
            Cuisine::truncate();
            CuisineTranslation::truncate();
            City::truncate();
            CityTranslation::truncate();
            DeliveryManWithdraw::truncate();
            DeliveryManWithdrawMethod::truncate();
            DeliveryMan::truncate();
            DeliveryManWithdrawMethod::truncate();
            Order::truncate();
            Review::truncate();
            Product::truncate();
            ProductTranslation::truncate();
            OfferProduct::truncate();
            Subscriber::truncate();
            Restaurant::truncate();
            RestaurantWishlist::truncate();
            SellerWithdraw::truncate();
            User::truncate();
            UserAddress::truncate();
            Wishlist::truncate();
            WithdrawMethod::truncate();

            $languages = Language::where('id', '!=', 1)->get();

            foreach($languages as $language){
                HomepageTranslation::where('lang_code', $language->lang_code)->delete();
                AboutUsTranslation::where('lang_code', $language->lang_code)->delete();
                PrivacyPolicy::where('lang_code', $language->lang_code)->delete();
                TermAndCondition::where('lang_code', $language->lang_code)->delete();

                $path = base_path().'/lang'.'/'.$language->lang_code;

                if (File::exists($path)) {
                    File::deleteDirectory($path);
                }

                $language->delete();
            }

            Language::where('id', 1)->update(['is_default' => 'yes']);


            $admins = Admin::where('id', '!=', 1)->get();
            foreach($admins as $admin){
                $admin_image = $admin->image;
                $admin->delete();
                if($admin_image){
                    if(File::exists(public_path().'/'.$admin_image))unlink(public_path().'/'.$admin_image);
                }
            }

            $folderPath = public_path('uploads/custom-images');
            $response = File::deleteDirectory($folderPath);

            $path = public_path('uploads/custom-images');
            if(!File::isDirectory($path)){
                File::makeDirectory($path, 0777, true, true);
            }

            $notify_message = trans('translate.Database Cleared Successfully');
            $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
            return redirect()->back()->with($notify_message);

        } catch (\Throwable $th) {
            $notify_message = $th->getMessage();
            $notify_message = array('message' => $notify_message, 'alert-type' => 'error');
            return redirect()->back()->with($notify_message);
        }
    }


    public function cookie_consent(){

        return view('globalsetting::cookie_consent');

    }


    public function cookie_consent_update(CookieConsentRequest $request){

        GlobalSetting::where('key', 'cookie_consent_message')->update(['value' => $request->message]);
        GlobalSetting::where('key', 'cookie_consent_status')->update(['value' => $request->status ? 1 : 0]);

        $this->set_cache_setting();

        $notify_message = trans('translate.Updated successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);

    }


    public function error_image(){

        return view('globalsetting::error_image');

    }


    public function error_image_update(Request $request){

        $setting = GlobalSetting::where('key', 'error_image')->first();

        if($request->error_image){
            $old_logo = $setting->value;
            $image = $request->error_image;
            $ext = $image->getClientOriginalExtension();
            $logo_name = 'error-image-'.date('Y-m-d-h-i-s-').rand(999,9999).'.'.$ext;
            $logo_name = 'uploads/website-images/'.$logo_name;
            $logo = Image::make($image)
                    ->save(public_path().'/'.$logo_name);
            $setting->value = $logo_name;
            $setting->save();

            if($old_logo){
                if(File::exists(public_path().'/'.$old_logo))unlink(public_path().'/'.$old_logo);
            }
        }

        $this->set_cache_setting();

        $notify_message = trans('translate.Updated successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);

    }


    public function login_image(){

        return view('globalsetting::login_image');

    }

    public function login_image_update(Request $request) {

        $request->validate([
            'login_title_one' => 'required|string|max:255',
            'login_title_two' => 'required|string|max:255',
            'login_title_three' => 'required|string|max:255',
            'login_description_one' => 'required|string|max:500',
            'login_description_two' => 'required|string|max:500',
            'login_description_three' => 'required|string|max:500',
        ]);

        $keys = [
            'login_image_one',
            'login_image_two',
            'login_image_three',
            'login_title_one',
            'login_title_two',
            'login_title_three',
            'login_description_one',
            'login_description_two',
            'login_description_three'
        ];

        $settings = GlobalSetting::whereIn('key', $keys)->get()->keyBy('key');

        foreach ($keys as $key) {
            if ($request->has($key) && isset($settings[$key])) {

                if ($key === 'login_image_one' || $key === 'login_image_two' || $key === 'login_image_three') {

                    $image = $request->file($key);
                    if ($image) {
                        $ext = $image->getClientOriginalExtension();
                        $logo_name = $key . '-' . date('Y-m-d-h-i-s-') . rand(999, 9999) . '.' . $ext;
                        $logo_path = 'uploads/website-images/' . $logo_name;

                        Image::make($image)->save(public_path($logo_path));

                        $old_logo = $settings[$key]->value;
                        $settings[$key]->value = $logo_path;
                        $settings[$key]->save();

                        if ($old_logo && File::exists(public_path($old_logo))) {
                            unlink(public_path($old_logo));
                        }
                    }
                } else {
                    $settings[$key]->value = $request->input($key);
                    $settings[$key]->save();
                }
            }
        }

        $this->set_cache_setting();

        $notify_message = trans('translate.Updated successfully');
        return redirect()->back()->with(['message' => $notify_message, 'alert-type' => 'success']);
    }

    public function signup_image(){

        return view('globalsetting::signup_image');

    }

    public function signup_image_update(Request $request) {

        $request->validate([
            'signup_title_one' => 'required|string|max:255',
            'signup_title_two' => 'required|string|max:255',
            'signup_title_three' => 'required|string|max:255',
            'signup_description_one' => 'required|string|max:500',
            'signup_description_two' => 'required|string|max:500',
            'signup_description_three' => 'required|string|max:500',
        ]);

        $keys = [
            'signup_image_one',
            'signup_image_two',
            'signup_image_three',
            'signup_title_one',
            'signup_title_two',
            'signup_title_three',
            'signup_description_one',
            'signup_description_two',
            'signup_description_three'
        ];

        $settings = GlobalSetting::whereIn('key', $keys)->get()->keyBy('key');

        foreach ($keys as $key) {
            if ($request->has($key) && isset($settings[$key])) {

                if ($key === 'signup_image_one' || $key === 'signup_image_two' || $key === 'signup_image_three') {

                    $image = $request->file($key);
                    if ($image) {
                        $ext = $image->getClientOriginalExtension();
                        $logo_name = $key . '-' . date('Y-m-d-h-i-s-') . rand(999, 9999) . '.' . $ext;
                        $logo_path = 'uploads/website-images/' . $logo_name;

                        Image::make($image)->save(public_path($logo_path));

                        $old_logo = $settings[$key]->value;
                        $settings[$key]->value = $logo_path;
                        $settings[$key]->save();

                        if ($old_logo && File::exists(public_path($old_logo))) {
                            unlink(public_path($old_logo));
                        }
                    }
                } else {
                    $settings[$key]->value = $request->input($key);
                    $settings[$key]->save();
                }
            }
        }

        $this->set_cache_setting();

        $notify_message = trans('translate.Updated successfully');
        return redirect()->back()->with(['message' => $notify_message, 'alert-type' => 'success']);
    }

    public function admin_login_image(){

        return view('globalsetting::admin_login_image');

    }


    public function admin_login_image_update(Request $request){

        $setting = GlobalSetting::where('key', 'admin_login')->first();

        if($request->admin_login){
            $old_logo = $setting->value;
            $image = $request->admin_login;
            $ext = $image->getClientOriginalExtension();
            $logo_name = 'admin-bg-image-'.date('Y-m-d-h-i-s-').rand(999,9999).'.'.$ext;
            $logo_name = 'uploads/website-images/'.$logo_name;
            $logo = Image::make($image)
                    ->save(public_path().'/'.$logo_name);
            $setting->value = $logo_name;
            $setting->save();

            if($old_logo){
                if(File::exists(public_path().'/'.$old_logo))unlink(public_path().'/'.$old_logo);
            }

        }

        $this->set_cache_setting();

        $notify_message = trans('translate.Updated successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);

    }


    public function breadcrumb(){

        return view('globalsetting::breadcrumb');

    }


    public function breadcrumb_update(Request $request){

        $setting = GlobalSetting::where('key', 'breadcrumb_image')->first();

        if($request->breadcrumb_image){
            $old_logo = $setting->value;
            $image = $request->breadcrumb_image;
            $ext = $image->getClientOriginalExtension();
            $logo_name = 'breadcrumb-image-'.date('Y-m-d-h-i-s-').rand(999,9999).'.'.$ext;
            $logo_name = 'uploads/website-images/'.$logo_name;
            $logo = Image::make($image)
                    ->save(public_path().'/'.$logo_name);
            $setting->value = $logo_name;
            $setting->save();

            if($old_logo){
                if(File::exists(public_path().'/'.$old_logo))unlink(public_path().'/'.$old_logo);
            }

        }

        $this->set_cache_setting();

        $notify_message = trans('translate.Updated successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);

    }

    public function social_login(){

        return view('globalsetting::social_login');
    }

    public function social_login_update(SocialLoginRequest $request){

        GlobalSetting::where('key', 'facebook_client_id')->update(['value' => $request->facebook_client_id]);
        GlobalSetting::where('key', 'facebook_secret_id')->update(['value' => $request->facebook_secret_id]);
        GlobalSetting::where('key', 'facebook_redirect_url')->update(['value' => $request->facebook_redirect_url]);
        GlobalSetting::where('key', 'is_facebook')->update(['value' => $request->is_facebook ? 1 : 0]);

        GlobalSetting::where('key', 'gmail_client_id')->update(['value' => $request->gmail_client_id]);
        GlobalSetting::where('key', 'gmail_secret_id')->update(['value' => $request->gmail_secret_id]);
        GlobalSetting::where('key', 'gmail_redirect_url')->update(['value' => $request->gmail_redirect_url]);
        GlobalSetting::where('key', 'is_gmail')->update(['value' => $request->is_gmail ? 1 : 0]);

        $this->set_cache_setting();

        $notify_message = trans('translate.Updated successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);



    }


    public function default_avatar(){

        return view('globalsetting::default_avatar');

    }


    public function default_avatar_update(Request $request){

        $setting = GlobalSetting::where('key', 'default_avatar')->first();

        if($request->default_avatar){
            $old_logo = $setting->value;
            $image = $request->default_avatar;
            $ext = $image->getClientOriginalExtension();
            $logo_name = 'avatar-image-'.date('Y-m-d-h-i-s-').rand(999,9999).'.'.$ext;
            $logo_name = 'uploads/website-images/'.$logo_name;
            $logo = Image::make($image)
                    ->save(public_path().'/'.$logo_name);
            $setting->value = $logo_name;
            $setting->save();

            if($old_logo){
                if(File::exists(public_path().'/'.$old_logo))unlink(public_path().'/'.$old_logo);
            }

        }

        $this->set_cache_setting();

        $notify_message = trans('translate.Updated successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);

    }

    public function default_cover_image(){

        return view('globalsetting::default_cover_image');

    }


    public function default_cover_image_update(Request $request){

        $setting = GlobalSetting::where('key', 'default_cover_image')->first();

        if($request->default_cover_image){
            $old_logo = $setting->value;
            $image = $request->default_cover_image;
            $ext = $image->getClientOriginalExtension();
            $logo_name = 'default-cover-image-'.date('Y-m-d-h-i-s-').rand(999,9999).'.'.$ext;
            $logo_name = 'uploads/website-images/'.$logo_name;
            $logo = Image::make($image)
                    ->save(public_path().'/'.$logo_name);
            $setting->value = $logo_name;
            $setting->save();

            if($old_logo){
                if(File::exists(public_path().'/'.$old_logo))unlink(public_path().'/'.$old_logo);
            }

        }

        $this->set_cache_setting();

        $notify_message = trans('translate.Updated successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);

    }


    public function maintenance_mode(){

        return view('globalsetting::maintenance_mode');

    }


    public function maintenance_mode_update(Request $request){

        $setting = GlobalSetting::where('key', 'maintenance_image')->first();

        if($request->maintenance_image){
            $old_logo = $setting->value;
            $image = $request->maintenance_image;
            $ext = $image->getClientOriginalExtension();
            $logo_name = 'maintenance-image-'.date('Y-m-d-h-i-s-').rand(999,9999).'.'.$ext;
            $logo_name = 'uploads/website-images/'.$logo_name;
            $logo = Image::make($image)
                    ->save(public_path().'/'.$logo_name);
            $setting->value = $logo_name;
            $setting->save();

            if($old_logo){
                if(File::exists(public_path().'/'.$old_logo))unlink(public_path().'/'.$old_logo);
            }

        }

        GlobalSetting::where('key', 'maintenance_text')->update(['value' => $request->maintenance_text]);
        GlobalSetting::where('key', 'maintenance_status')->update(['value' => $request->maintenance_status ? 1 : 0]);

        $this->set_cache_setting();

        $notify_message = trans('translate.Updated successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);

    }


    public function cache_clear(){

        Artisan::call('optimize:clear');

        $notify_message = trans('translate.Updated successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);
    }

    public function set_cache_setting(){
        $setting_data = GlobalSetting::get();

        $setting = array();

        foreach($setting_data as $data_item){
            $setting[$data_item->key] = $data_item->value;
        }

        $setting = (object) $setting;


        Cache::put('setting', $setting);

    }

}
