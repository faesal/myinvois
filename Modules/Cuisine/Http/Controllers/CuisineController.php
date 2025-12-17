<?php

namespace Modules\Cuisine\Http\Controllers;

use Image, File, Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Http\RedirectResponse;

use Modules\Cuisine\Entities\Cuisine;
use Modules\Language\App\Models\Language;
use Modules\Restaurant\Entities\Restaurant;
use Illuminate\Contracts\Support\Renderable;
use Modules\Cuisine\Entities\CuisineTranslation;
use Modules\Cuisine\Http\Requests\CuisineRequest;

class CuisineController extends Controller
{
     /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $cuisines = Cuisine::latest()->get();

        return view('cuisine::.index', ['cuisines' => $cuisines]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('cuisine::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param CuisineRequest $request
     * @return RedirectResponse
     */
    public function store(CuisineRequest $request)
    {
        $cuisine = new Cuisine();

        if($request->image){
            $image_name = 'cuisine-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name ='uploads/custom-images/'.$image_name;
            Image::make($request->image)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $cuisine->icon = $image_name;
        }

        $cuisine->slug = $request->slug;
        $cuisine->status = $request->status ? Cuisine::STATUS_ACTIVE : Cuisine::STATUS_INACTIVE;
        $cuisine->save();

        $languages = Language::all();
        foreach($languages as $language){
            $cuisine_translation = new CuisineTranslation();
            $cuisine_translation->lang_code = $language->lang_code;
            $cuisine_translation->cuisine_id = $cuisine->id;
            $cuisine_translation->name = $request->name;
            $cuisine_translation->save();
        }

        $notify_message= trans('translate.Created Successfully');
        $notify_message=array('message'=>$notify_message,'alert-type'=>'success');
        return redirect()->route('admin.cuisine.edit', ['cuisine' => $cuisine->id, 'lang_code' => admin_lang()])->with($notify_message);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit(Request $request ,$id)
    {

        $cuisine = Cuisine::with('front_translate')->findOrFail($id);

        $cuisine_translate = CuisineTranslation::where(['cuisine_id' => $id, 'lang_code' => $request->lang_code])->first();

        return view('cuisine::edit', ['cuisine' => $cuisine, 'cuisine_translate' => $cuisine_translate]);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(CuisineRequest $request, $id)
    {

        $cuisine = Cuisine::findOrFail($id);

        if($request->lang_code == admin_lang()){

            if($request->image){
                $old_image = $cuisine->icon;
                $image_name = 'cuisine-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
                $image_name ='uploads/custom-images/'.$image_name;
                Image::make($request->image)
                    ->encode('webp', 80)
                    ->save(public_path().'/'.$image_name);
                $cuisine->icon = $image_name;
                $cuisine->save();
                if($old_image){
                    if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
                }
            }

            $cuisine->slug = $request->slug;
            $cuisine->status = $request->status ? Cuisine::STATUS_ACTIVE : Cuisine::STATUS_INACTIVE;
            $cuisine->save();

            $cuisine_translation = CuisineTranslation::findOrFail($request->translate_id);
            $cuisine_translation->name = $request->name;
            $cuisine_translation->save();

        }else{

            $cuisine_translation = CuisineTranslation::findOrFail($request->translate_id);
            $cuisine_translation->name = $request->name;
            $cuisine_translation->save();
        }

        $notify_message= trans('translate.Update Successfully');
        $notify_message=array('message'=>$notify_message,'alert-type'=>'success');
        return redirect()->back()->with($notify_message);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {

        $restaurantExist = Restaurant::whereJsonContains('cuisines', (string)$id)->exists();

        if($restaurantExist){
            $notify_message = trans('translate.Cuisines already exist in another restaurant');
            $notify_message = array('message' => $notify_message, 'alert-type' => 'error');
            return redirect()->route('admin.cuisine.index')->with($notify_message);
        }

        $cuisine = Cuisine::findOrFail($id);
        $old_icon = $cuisine->icon;

        if($old_icon){
            if(File::exists(public_path().'/'.$old_icon))unlink(public_path().'/'.$old_icon);
        }

        $cuisine->delete();

        CuisineTranslation::where('cuisine_id', $id)->delete();

        $notify_message= trans('translate.Delete Successfully');
        $notify_message=array('message'=>$notify_message,'alert-type'=>'success');
        return redirect()->route('admin.cuisine.index')->with($notify_message);
    }

    public function setup_language($lang_code){
        $cuisine_translates = CuisineTranslation::where('lang_code', admin_lang())->get();
        foreach($cuisine_translates as $cuisine_translate){
            $cat_translate = new CuisineTranslation();
            $cat_translate->lang_code = $lang_code;
            $cat_translate->cuisine_id = $cuisine_translate->cuisine_id;
            $cat_translate->name = $cuisine_translate->name;
            $cat_translate->save();
        }
    }
}
