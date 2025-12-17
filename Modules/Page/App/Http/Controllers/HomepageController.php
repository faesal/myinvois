<?php

namespace Modules\Page\App\Http\Controllers;

use Image, File, Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\Page\App\Models\Homepage;
use Modules\Page\App\Http\Requests\IntroRequest;
use Modules\Page\App\Models\HomepageTranslation;
use Modules\Page\App\Http\Requests\MobileAppRequest;
use Modules\Page\App\Http\Requests\WorkingStepRequest;
use Modules\Page\App\Http\Requests\JoinRestaurantRequest;

class HomepageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function intro_section(Request $request)
    {
        $homepage = Homepage::first();
        $translate = HomepageTranslation::where(['homepage_id' => $homepage->id, 'lang_code' => $request->lang_code])->first();

        return view('page::section.intro', ['homepage' => $homepage, 'translate' => $translate]);
    }

    public function update_intro_section(IntroRequest $request)
    {


        $translate = HomepageTranslation::where(['id' => $request->translate_id, 'lang_code' => $request->lang_code])->first();
        $translate->intro_title = $request->intro_title;
        $translate->intro_tags = $request->intro_tags;
        $translate->save();

        $homepage = Homepage::first();

        if($request->intro_banner_one){
            $old_image = $homepage->intro_banner_one;
            $image_name = 'intro-one-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name ='uploads/custom-images/'.$image_name;
            Image::make($request->intro_banner_one)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $homepage->intro_banner_one = $image_name;
            $homepage->save();

            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }

        if($request->intro_banner_two){
            $old_image = $homepage->intro_banner_two;
            $image_name = 'intro-two-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name ='uploads/custom-images/'.$image_name;
            Image::make($request->intro_banner_two)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $homepage->intro_banner_two = $image_name;
            $homepage->save();

            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }



        $notify_message = trans('translate.Update successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);

    }


    public function working_step(Request $request)
    {
        $homepage = Homepage::first();
        $translate = HomepageTranslation::where(['homepage_id' => $homepage->id, 'lang_code' => $request->lang_code])->first();

        return view('page::section.working_step', ['homepage' => $homepage, 'translate' => $translate]);
    }

    public function update_working_step(WorkingStepRequest $request)
    {

        $translate = HomepageTranslation::where(['id' => $request->translate_id, 'lang_code' => $request->lang_code])->first();
        $translate->working_step_title1 = $request->working_step_title1;
        $translate->working_step_title2 = $request->working_step_title2;
        $translate->working_step_title3 = $request->working_step_title3;
        $translate->working_step_title4 = $request->working_step_title4;
        $translate->working_step_des1 = $request->working_step_des1;
        $translate->working_step_des2 = $request->working_step_des2;
        $translate->working_step_des3 = $request->working_step_des3;
        $translate->working_step_des4 = $request->working_step_des4;
        $translate->save();

        $homepage = Homepage::first();

        if($request->working_step_icon1){
            $old_image = $homepage->working_step_icon1;
            $image_name = 'working-step-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name ='uploads/custom-images/'.$image_name;
            Image::make($request->working_step_icon1)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $homepage->working_step_icon1 = $image_name;
            $homepage->save();

            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }

         if($request->working_step_icon2){
            $old_image = $homepage->working_step_icon2;
            $image_name = 'working-step-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name ='uploads/custom-images/'.$image_name;
            Image::make($request->working_step_icon2)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $homepage->working_step_icon2 = $image_name;
            $homepage->save();

            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }

         if($request->working_step_icon3){
            $old_image = $homepage->working_step_icon3;
            $image_name = 'working-step-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name ='uploads/custom-images/'.$image_name;
            Image::make($request->working_step_icon3)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $homepage->working_step_icon3 = $image_name;
            $homepage->save();

            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }

        if($request->working_step_icon4){
            $old_image = $homepage->working_step_icon4;
            $image_name = 'working-step-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name ='uploads/custom-images/'.$image_name;
            Image::make($request->working_step_icon4)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $homepage->working_step_icon4 = $image_name;
            $homepage->save();

            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }

        $notify_message = trans('translate.Update successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);

    }


    public function join_restaurant(Request $request){

        $homepage = Homepage::first();
        $translate = HomepageTranslation::where(['homepage_id' => $homepage->id, 'lang_code' => $request->lang_code])->first();

        return view('page::section.join_restaurant' , ['homepage' => $homepage, 'translate' => $translate]);
    }

    public function update_join_restaurant(JoinRestaurantRequest $request)
    {

        $translate = HomepageTranslation::where(['id' => $request->translate_id, 'lang_code' => $request->lang_code])->first();
        $translate->join_restaurant_title = $request->join_restaurant_title;
        $translate->join_restaurant_des = $request->join_restaurant_des;
        $translate->save();

        $homepage = Homepage::first();

        if($request->join_restaurant_image){
            $old_image = $homepage->join_restaurant_image;
            $image_name = 'working-step-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name ='uploads/custom-images/'.$image_name;
            Image::make($request->join_restaurant_image)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $homepage->join_restaurant_image = $image_name;
            $homepage->save();

            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }

        $notify_message = trans('translate.Update successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);

    }


    public function mobile_app(Request $request){

        $homepage = Homepage::first();
        $translate = HomepageTranslation::where(['homepage_id' => $homepage->id, 'lang_code' => $request->lang_code])->first();

        return view('page::section.mobile_app' , ['homepage' => $homepage, 'translate' => $translate]);
    }

    public function update_mobile_app(MobileAppRequest $request)
    {

        $translate = HomepageTranslation::where(['id' => $request->translate_id, 'lang_code' => $request->lang_code])->first();
        $translate->mobile_app_title = $request->mobile_app_title;
        $translate->mobile_app_des = $request->mobile_app_des;
        $translate->save();

        if($request->lang_code == admin_lang()){
            $homepage = Homepage::first();
            $homepage->mobile_playstore = $request->mobile_playstore;
            $homepage->mobile_appstore = $request->mobile_appstore;
            $homepage->save();
        }

        if($request->mobile_app_image){
            $old_image = $homepage->mobile_app_image;
            $image_name = 'working-step-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name ='uploads/custom-images/'.$image_name;
            Image::make($request->mobile_app_image)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $homepage->mobile_app_image = $image_name;
            $homepage->save();

            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }

        $notify_message = trans('translate.Update successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);

    }


    public function setup_language($lang_code){

        $home_translates = HomepageTranslation::where('lang_code' , admin_lang())->first();

        $new_trans = new HomepageTranslation();
        $new_trans->lang_code = $lang_code;
        $new_trans->homepage_id = $home_translates->homepage_id;
        $new_trans->intro_title = $home_translates->intro_title;
        $new_trans->intro_tags = $home_translates->intro_tags;
        $new_trans->working_step_title1 = $home_translates->working_step_title1;
        $new_trans->working_step_title2 = $home_translates->working_step_title2;
        $new_trans->working_step_title3 = $home_translates->working_step_title3;
        $new_trans->working_step_title4 = $home_translates->working_step_title4;
        $new_trans->working_step_des1 = $home_translates->working_step_des1;
        $new_trans->working_step_des2 = $home_translates->working_step_des2;
        $new_trans->working_step_des3 = $home_translates->working_step_des3;
        $new_trans->working_step_des4 = $home_translates->working_step_des4;
        $new_trans->join_restaurant_title = $home_translates->join_restaurant_title;
        $new_trans->join_restaurant_des = $home_translates->join_restaurant_des;
        $new_trans->mobile_app_title = $home_translates->mobile_app_title;
        $new_trans->mobile_app_des = $home_translates->mobile_app_des;
        $new_trans->save();

    }

}
