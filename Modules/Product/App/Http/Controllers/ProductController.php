<?php

namespace Modules\Product\App\Http\Controllers;

use Illuminate\Support\Str;
use App\Models\OfferProduct;
use Illuminate\Http\Request;
use Modules\Addon\App\Models\Addon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Http\RedirectResponse;
use Intervention\Image\Facades\Image;
use Modules\Category\Entities\Category;
use Modules\Product\App\Models\Product;
use Modules\Language\App\Models\Language;
use Modules\Restaurant\Entities\Restaurant;
use Modules\Product\App\Models\ProductTranslation;
use Modules\Product\App\Http\Requests\ProductRequest;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with('translate_product')->latest()->get();

        return view('product::admin.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::with('translate')->where('status', 'enable')->get();
        $addons = Addon::with('translate')->where('status', 'enable')->get();
        $restaurants = Restaurant::where('is_banned', 'disable')->where('admin_approval', 'enable')->get();
        return view('product::admin.create', compact('categories', 'addons', 'restaurants'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request): RedirectResponse
    {
        $product = new Product();

        if ($request->image) {
            $extention = $request->image->getClientOriginalExtension();
            $image_name = Str::slug($request->name) . date('-Y-m-d-h-i-s-') . rand(999, 9999) . '.' . $extention;
            $image_name = 'uploads/custom-images/' . $image_name;
            Image::make($request->image)
                ->save(public_path() . '/' . $image_name);
            $product->image = $image_name;
        }

        $product->slug = $request->slug;
        $product->restaurant_id = $request->restaurant_id;
        $product->category_id = $request->category_id;
        $product->price = $request->product_price;
        $product->offer_price = $request->offer_price == null ? 0 : $request->offer_price;
        $product->addon_items = json_encode($request['addon_items']);
        $product->status = $request->status == 'on' ? 'enable' : 'disable';
        $product->is_featured = $request->featured == 'on' ? 'enable' : 'disable';
        $product->save();

        $languages = Language::all();
        foreach ($languages as $language) {
            $translate = new ProductTranslation();
            $translate->product_id = $product->id;
            $translate->lang_code = $language->lang_code;
            $translate->name = $request->name;
            $translate->short_description = $request->short_description;
            $translate->size = json_encode(array_combine($request->size, $request->price));
            $translate->specification = json_encode($request['specification']);
            $translate->save();
        }

        $message = trans('translate.Created successfully');
        $notification = array('message' => $message, 'alert-type' => 'success');
        return redirect()->route('admin.product.index')->with($notification);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $id)
    {
        $product = Product::find($id);
        $categories = Category::with('translate')->where('status', 'enable')->get();
        $addons = Addon::with('translate')->where('status', 'enable')->get();
        $restaurants = Restaurant::where('is_banned', 'disable')->where('admin_approval', 'enable')->get();
        $product_translate = ProductTranslation::where(['product_id' => $id, 'lang_code' => $request->lang_code])->first();
        $intArray = json_decode($product->addon_items);
        if ($intArray != null) {
            $selected_ids = array_map('intval', $intArray);
        } else {
            $selected_ids = array();
        }
        return view('product::admin.edit', compact('categories', 'addons', 'restaurants', 'product', 'selected_ids', 'product_translate'));


    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, $id): RedirectResponse
    {

        $product = Product::find($id);
        $old_image = $product->image;


        if ($request->lang_code == admin_lang()) {
            if ($request->image) {
                $extention = $request->image->getClientOriginalExtension();
                $image_name = Str::slug($request->name) . date('-Y-m-d-h-i-s-') . rand(999, 9999) . '.' . $extention;
                $image_name = 'uploads/custom-images/' . $image_name;
                Image::make($request->image)
                    ->save(public_path() . '/' . $image_name);
                if ($old_image) {
                    if (File::exists(public_path() . '/' . $old_image)) unlink(public_path() . '/' . $old_image);
                }
                $product->image = $image_name;
            }

            $product->slug = $request->slug;
            $product->restaurant_id = $request->restaurant_id;
            $product->category_id = $request->category_id;
            $product->price = $request->product_price;
            $product->offer_price = $request->offer_price == null ? 0 : $request->offer_price;
            $product->addon_items = json_encode($request['addon_items']);
            $product->status = $request->status == 'on' ? 'enable' : 'disable';
            $product->is_featured = $request->featured == 'on' ? 'enable' : 'disable';
            $product->save();


            $translate = ProductTranslation::findOrFail($request->translate_id);
            $translate->name = $request->name;
            $translate->short_description = $request->short_description;
            $translate->size = json_encode(array_combine($request->size, $request->price));
            $translate->specification = json_encode($request['specification']);
            $translate->save();

        } else {
            $translate = ProductTranslation::findOrFail($request->translate_id);
            $translate->name = $request->name;
            $translate->short_description = $request->short_description;
            $translate->size = json_encode(array_combine($request->size, $request->price));
            $translate->specification = json_encode($request['specification']);
            $translate->save();
        }

        $message = trans('translate.Updated successfully');
        $notification = array('message' => $message, 'alert-type' => 'success');
        return redirect()->route('admin.product.index')->with($notification);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {

            $product = Product::findOrFail($id);
            $old_image = $product->image;

            $product->delete();

            ProductTranslation::where('product_id', $id)->delete();
            OfferProduct::where('product_id', $id)->delete();

            if ($old_image) {
                if (File::exists(public_path() . '/' . $old_image)) unlink(public_path() . '/' . $old_image);
            }

            $message = trans('translate.Deleted Product successfully');
            $notification = array('message' => $message, 'alert-type' => 'success');
            return redirect()->back()->with($notification);

        } catch (\Exception $e) {

            $message = $e->getMessage();
            $notification = array('message' => $message, 'alert-type' => 'success');
            return redirect()->back()->with($notification);
        }
    }

    public function setup_language($lang_code){
        $product_translates = ProductTranslation::where('lang_code' , admin_lang())->get();

        foreach($product_translates as $product_translate){
            $new_trans = new ProductTranslation();
            $new_trans->lang_code = $lang_code;
            $new_trans->product_id = $product_translate->product_id;
            $new_trans->name = $product_translate->name;
            $new_trans->short_description = $product_translate->short_description;
            $new_trans->size = $product_translate->size;
            $new_trans->specification = $product_translate->specification;
            $new_trans->save();

        }

    }
}
