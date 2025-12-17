<?php

namespace Modules\Addon\App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Modules\Addon\App\Models\Addon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Modules\Product\App\Models\Product;
use Modules\Language\App\Models\Language;
use Modules\Addon\App\Models\AddonTranslation;
use Modules\Addon\App\Http\Requests\RestaurantAddonRequest;

class RestaurantAddonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $addons = Addon::where('restaurant_id', Auth::guard('restaurant')->user()->id)->latest()->get();

        return view('addon::restaurant.index', ['addons' => $addons]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('addon::restaurant.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RestaurantAddonRequest $request): RedirectResponse
    {
        $addon = new Addon();
        $addon->price = $request->price;
        $addon->restaurant_id = Auth::guard('restaurant')->user()->id;
        $addon->status = $request->status ? 'enable' : 'disable';
        $addon->save();

        $languages = Language::all();
        foreach($languages as $language){
            $addon_translation = new AddonTranslation();
            $addon_translation->addon_id = $addon->id;
            $addon_translation->lang_code = $language->lang_code;
            $addon_translation->name = $request->name;
            $addon_translation->save();
        }

        $notify_message= trans('translate.Created Successfully');
        $notify_message=array('message'=>$notify_message,'alert-type'=>'success');
        return redirect()->route('restaurant.addon.edit', ['addon' => $addon->id, 'lang_code' => admin_lang()])->with($notify_message);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('addon::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $id)
    {
        $addon = Addon::findOrFail($id);
        $addon_translate = AddonTranslation::where(['addon_id' => $id, 'lang_code' => $request->lang_code])->first();

        return view('addon::restaurant.edit', ['addon' => $addon, 'addon_translate' => $addon_translate]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RestaurantAddonRequest $request, $id): RedirectResponse
    {
        $addon = Addon::findOrFail($id);

        if($request->lang_code == admin_lang()){

            $addon->price = $request->price;
            $addon->restaurant_id = Auth::guard('restaurant')->user()->id;
            $addon->status = $request->status ? 'enable' : 'disable';
            $addon->save();

            $addon_translation = AddonTranslation::findOrFail($request->translate_id);
            $addon_translation->name = $request->name;
            $addon_translation->save();

        }else{
            $addon_translation = AddonTranslation::findOrFail($request->translate_id);
            $addon_translation->name = $request->name;
            $addon_translation->save();
        }

        $notify_message= trans('translate.Update Successfully');
        $notify_message=array('message'=>$notify_message,'alert-type'=>'success');
        return redirect()->back()->with($notify_message);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $addon = Addon::findOrFail($id);

        $productExists = Product::whereJsonContains('addon_items', (string)$id)->exists();

        if($productExists){
            $notify_message = trans('translate.Addon already exist in another product');
            $notify_message = array('message' => $notify_message, 'alert-type' => 'error');
            return redirect()->route('restaurant.addon.index')->with($notify_message);
        }

        AddonTranslation::where('addon_id', $id)->delete();

        $addon->delete();

        $notify_message = trans('translate.Deleted successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->route('restaurant.addon.index')->with($notify_message);
    }
}
