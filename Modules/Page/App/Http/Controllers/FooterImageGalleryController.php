<?php

namespace Modules\Page\App\Http\Controllers;

use Image, File;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Page\App\Models\Homepage;

class FooterImageGalleryController extends Controller
{
    public function footer_image_gallery(){

        $homepage = Homepage::first();

        return view('page::section.footer_image_gallery', compact('homepage'));
    }

    public function update_footer_image_gallery(Request $request){


        $request->validate([
            'footer_img_one_link' => 'required|max:255',
            'footer_img_two_link' => 'required|max:255',
            'footer_img_three_link' => 'required|max:255',
            'footer_img_four_link' => 'required|max:255',
        ]);


        $homepage = Homepage::first();
        $homepage->footer_img_one_link = $request->footer_img_one_link;
        $homepage->footer_img_two_link = $request->footer_img_two_link;
        $homepage->footer_img_three_link = $request->footer_img_three_link;
        $homepage->footer_img_four_link = $request->footer_img_four_link;
        $homepage->footer_img_five_link = $request->footer_img_five_link;
        $homepage->footer_img_six_link = $request->footer_img_six_link;
        $homepage->save();

        if($request->footer_img_one){
            $old_image = $homepage->footer_img_one;
            $image_name = 'intro-one-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name ='uploads/custom-images/'.$image_name;
            Image::make($request->footer_img_one)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $homepage->footer_img_one = $image_name;
            $homepage->save();

            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }

        if($request->footer_img_two){
            $old_image = $homepage->footer_img_two;
            $image_name = 'intro-two-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name ='uploads/custom-images/'.$image_name;
            Image::make($request->footer_img_two)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $homepage->footer_img_two = $image_name;
            $homepage->save();

            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }

        if($request->footer_img_three){
            $old_image = $homepage->footer_img_three;
            $image_name = 'intro-two-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name ='uploads/custom-images/'.$image_name;
            Image::make($request->footer_img_three)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $homepage->footer_img_three = $image_name;
            $homepage->save();

            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }

        if($request->footer_img_four){
            $old_image = $homepage->footer_img_four;
            $image_name = 'intro-two-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name ='uploads/custom-images/'.$image_name;
            Image::make($request->footer_img_four)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $homepage->footer_img_four = $image_name;
            $homepage->save();

            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }

        if($request->footer_img_five){
            $old_image = $homepage->footer_img_five;
            $image_name = 'intro-two-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name ='uploads/custom-images/'.$image_name;
            Image::make($request->footer_img_five)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $homepage->footer_img_five = $image_name;
            $homepage->save();

            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }

        if($request->footer_img_six){
            $old_image = $homepage->footer_img_six;
            $image_name = 'intro-two-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name ='uploads/custom-images/'.$image_name;
            Image::make($request->footer_img_six)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $homepage->footer_img_six = $image_name;
            $homepage->save();

            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }





        $notify_message = trans('translate.Update successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);
    }
}
