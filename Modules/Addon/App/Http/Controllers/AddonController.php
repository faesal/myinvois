<?php

namespace Modules\Addon\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Addon\App\Http\Requests\AddonRequest;
use Modules\Addon\App\Models\Addon;
use Modules\Addon\App\Models\AddonTranslation;
use Modules\Language\App\Models\Language;
use Modules\Product\App\Models\Product;
use Modules\Restaurant\Entities\Restaurant;

class AddonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $addons = Addon::latest()->get();
        return view('addon::admin.index', ['addons' => $addons]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $restaurants = Restaurant::where('is_banned', 'disable')->where('admin_approval', 'enable')->get();
        return view('addon::admin.create', ['restaurants' => $restaurants]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AddonRequest $request): RedirectResponse
    {
        $addon = new Addon();
        $addon->price = $request->price;
        $addon->restaurant_id = $request->restaurant_id;
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
        return redirect()->route('admin.addon.edit', ['addon' => $addon->id, 'lang_code' => admin_lang()])->with($notify_message);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $id)
    {
        $addon = Addon::findOrFail($id);
        $restaurants = Restaurant::where('is_banned', 'disable')->where('admin_approval', 'enable')->get();
        $addon_translate = AddonTranslation::where(['addon_id' => $id, 'lang_code' => $request->lang_code])->first();

        return view('addon::admin.edit', ['addon' => $addon, 'addon_translate' => $addon_translate, 'restaurants' => $restaurants]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AddonRequest $request, $id): RedirectResponse
    {
        $addon = Addon::findOrFail($id);

        if($request->lang_code == admin_lang()){

            $addon->price = $request->price;
            $addon->restaurant_id = $request->restaurant_id;
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
            return redirect()->route('admin.addon.index')->with($notify_message);
        }

        AddonTranslation::where('addon_id', $id)->delete();

        $addon->delete();

        $notify_message = trans('translate.Deleted successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->route('admin.addon.index')->with($notify_message);
    }



    public function setup_language($lang_code){
        $addon_translates = AddonTranslation::where('lang_code' , admin_lang())->get();

        foreach($addon_translates as $addon_translate){
            $new_trans = new AddonTranslation();
            $new_trans->lang_code = $lang_code;
            $new_trans->name = $addon_translate->name;
            $new_trans->addon_id = $addon_translate->addon_id;
            $new_trans->save();

        }
    }


    public function ajax_addon_list($id): \Illuminate\Http\JsonResponse
    {
        $addons = Addon::with('translate')->where('status', 'enable')->where('restaurant_id', $id)->get();

        return response()->json([
            'template' => view('addon::admin.partials.addon_list', compact('addons'))->render()
        ], 200);
    }

    public function ajax_addon_list_edit($id): \Illuminate\Http\JsonResponse
    {
        $product = Product::find($id);
        $intArray = json_decode($product->addon_items);
        if ($intArray != null) {
            $selected_ids = array_map('intval', $intArray);
        } else {
            $selected_ids = array();
        }
        $addons = Addon::with('translate')->where('status', 'enable')->where('restaurant_id', $product->restaurant_id)->get();

        return response()->json([
            'template' => view('addon::admin.partials.addon_list_edit', compact('addons', 'selected_ids'))->render()
        ], 200);
    }
}
