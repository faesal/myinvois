<?php

namespace Modules\Page\App\Http\Controllers;

use Image, File, Str;
use App\Models\AboutUs;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\AboutUsTranslation;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\Page\App\Http\Requests\AboutUsRequest;

class AboutusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function about_us(Request $request)
    {

        $about_us = AboutUs::first();
        $translate = AboutUsTranslation::where(['about_us_id' => $about_us->id, 'lang_code' => $request->lang_code])->first();

        return view('page::about_us', ['about_us' => $about_us, 'translate' => $translate ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(AboutUsRequest $request)
    {
        $about_us = AboutUs::first();

        $translate = AboutUsTranslation::where(['about_us_id' => $about_us->id, 'lang_code' => $request->lang_code])->first();
        $translate->title = $request->title;
        $translate->description = $request->description;
        $translate->customer_title = $request->customer_title;
        $translate->customer_des = $request->customer_des;
        $translate->branch_title = $request->branch_title;
        $translate->branch_des = $request->branch_des;
        $translate->save();

        if($request->about_image){
            $old_image = $about_us->about_image;
            $image_name = 'about-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name ='uploads/custom-images/'.$image_name;
            Image::make($request->about_image)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $about_us->about_image = $image_name;
            $about_us->save();

            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }

        if($request->lang_code == admin_lang()){
            $about_us->experience_year = $request->experience_year;
            $about_us->save();

        }

        if($request->customer_image){
            $old_image = $about_us->customer_image;
            $image_name = 'about-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name ='uploads/custom-images/'.$image_name;
            Image::make($request->customer_image)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $about_us->customer_image = $image_name;
            $about_us->save();

            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }

        if($request->branch_image){
            $old_image = $about_us->branch_image;
            $image_name = 'about-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name ='uploads/custom-images/'.$image_name;
            Image::make($request->branch_image)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $about_us->branch_image = $image_name;
            $about_us->save();

            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }

        $notify_message = trans('translate.Update successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);


    }


    public function setup_language($lang_code){
        $about_translate = AboutUsTranslation::where('lang_code' , admin_lang())->first();

        $new_trans = new AboutUsTranslation();
        $new_trans->lang_code = $lang_code;
        $new_trans->about_us_id = $about_translate->about_us_id;
        $new_trans->title = $about_translate->title;
        $new_trans->description = $about_translate->description;
        $new_trans->customer_title = $about_translate->customer_title;
        $new_trans->customer_des = $about_translate->customer_des;
        $new_trans->branch_title = $about_translate->branch_title;
        $new_trans->branch_des = $about_translate->branch_des;
        $new_trans->save();

    }

}
