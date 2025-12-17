<?php

namespace Modules\City\Http\Controllers;

use Image, File, Str;
use Illuminate\Http\Request;
use Modules\City\Entities\City;
use Illuminate\Routing\Controller;
use Modules\Listing\Entities\Listing;
use Modules\Language\App\Models\Language;
use Modules\City\Entities\CityTranslation;

use Modules\City\Http\Requests\CityRequest;

use Modules\Restaurant\Entities\Restaurant;
use Illuminate\Contracts\Support\Renderable;

class CityController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $cities = City::with('translate')->get();

        return view('city::index', compact('cities'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('city::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(CityRequest $request)
    {
        $city = new City();
        $city->save();

        if($request->image){
            $image_name = 'city-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name ='uploads/custom-images/'.$image_name;
            Image::make($request->image)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $city->image = $image_name;
            $city->save();
        }

        $languages = Language::all();
        foreach($languages as $language){
            $city_translation = new CityTranslation();
            $city_translation->lang_code = $language->lang_code;
            $city_translation->city_id = $city->id;
            $city_translation->name = $request->name;
            $city_translation->save();
        }

        $notify_message= trans('translate.Created Successfully');
        $notify_message=array('message'=>$notify_message,'alert-type'=>'success');
        return redirect()->route('admin.city.edit', ['city' => $city->id, 'lang_code' => admin_lang()])->with($notify_message);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit(Request $request ,$id)
    {
        $city = City::findOrFail($id);
        $city_translate = CityTranslation::where(['city_id' => $id, 'lang_code' => $request->lang_code])->first();

        return view('city::edit', compact('city','city_translate'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(CityRequest $request, $id)
    {
        $city = City::findOrFail($id);

        if($request->lang_code == admin_lang()){

            if($request->image){
                $old_image = $city->icon;
                $image_name = 'city-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
                $image_name ='uploads/custom-images/'.$image_name;
                Image::make($request->image)
                    ->encode('webp', 80)
                    ->save(public_path().'/'.$image_name);
                $city->image = $image_name;
                $city->save();
                if($old_image){
                    if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
                }
            }
        }


        $city_translation = CityTranslation::findOrFail($request->translate_id);
        $city_translation->name = $request->name;
        $city_translation->save();

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
        $restaurant_qty = Restaurant::where('city_id', $id)->count();

        if($restaurant_qty > 0){
            $notify_message = trans('translate.Multiple listing created under it, so you can not delete it');
            $notify_message = array('message'=>$notify_message,'alert-type'=>'error');
            return redirect()->back()->with($notify_message);
        }

        $city = City::find($id);
        $old_icon = $city->image;

        if($old_icon){
            if(File::exists(public_path().'/'.$old_icon))unlink(public_path().'/'.$old_icon);
        }
        $city->delete();

        CityTranslation::where('city_id', $id)->delete();

        $notify_message= trans('translate.Delete Successfully');
        $notify_message=array('message'=>$notify_message,'alert-type'=>'success');
        return redirect()->route('admin.city.index')->with($notify_message);
    }

    public function setup_language($lang_code){
        $city_translates = CityTranslation::where('lang_code', admin_lang())->get();
        foreach($city_translates as $city_translate){
            $city_translation = new CityTranslation();
            $city_translation->lang_code = $lang_code;
            $city_translation->city_id = $city_translate->city_id;
            $city_translation->name = $city_translate->name;
            $city_translation->save();
        }
    }

}
