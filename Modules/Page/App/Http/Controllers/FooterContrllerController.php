<?php

namespace Modules\Page\App\Http\Controllers;

use Image, File;
use Illuminate\Http\Request;
use Modules\Page\App\Models\Footer;
use App\Http\Controllers\Controller;
use Modules\Page\App\Models\Homepage;
use Modules\Page\App\Models\FooterTranslation;

class FooterContrllerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $footer = Footer::first();
        $translate = FooterTranslation::where(['footer_id' => $footer->id, 'lang_code' => $request->lang_code])->first();

        return view('page::section.footer', [
            'footer' => $footer,
            'translate' => $translate,
        ]);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {

        if($request->lang_code == admin_lang()){

            $request->validate([
                'facebook' => 'required|max:250',
                'twitter' => 'required|max:250',
                'linkedin' => 'required|max:250',
                'instagram' => 'required|max:250',
                'copyright' => 'required|max:250',

            ],[
                'facebook' => trans('translate.Facebook is required'),
                'twitter' => trans('translate.Twitter is required'),
                'linkedin' => trans('translate.Linkedin is required'),
                'instagram' => trans('translate.Instagram is required'),
                'copyright' => trans('translate.Copyright is required'),
            ]);

            $footer = Footer::first();


            if($request->payment_image){
                $old_image = $footer->payment_image;
                $image_name = 'intro-two-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
                $image_name ='uploads/custom-images/'.$image_name;
                Image::make($request->payment_image)
                    ->encode('webp', 80)
                    ->save(public_path().'/'.$image_name);
                $footer->payment_image = $image_name;
                $footer->save();

                if($old_image){
                    if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
                }
            }

            $footer->facebook = $request->facebook;
            $footer->twitter = $request->twitter;
            $footer->linkedin = $request->linkedin;
            $footer->instagram = $request->instagram;
            $footer->copyright = $request->copyright;
            $footer->save();

        }

        $request->validate([
            'about_us' => 'required',
        ],[
            'about_us' => trans('translate.About us is required'),
        ]);

        $footer = Footer::first();

        $translate = FooterTranslation::where(['footer_id' => $footer->id, 'lang_code' => $request->lang_code])->first();
        $translate->about_us = $request->about_us;
        $translate->save();


        $notify_message = trans('translate.Update successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);
    }

    public function setup_language($lang_code){
        $footer_translates = FooterTranslation::where('lang_code' , admin_lang())->first();

        $new_trans = new FooterTranslation();
        $new_trans->lang_code = $lang_code;
        $new_trans->footer_id = $footer_translates->footer_id;
        $new_trans->about_us = $footer_translates->about_us;
        $new_trans->save();

    }


}
