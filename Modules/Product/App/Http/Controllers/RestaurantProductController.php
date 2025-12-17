<?php

namespace Modules\Product\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Modules\Addon\App\Models\Addon;
use Modules\Category\Entities\Category;
use Modules\Language\App\Models\Language;
use Modules\Product\App\Http\Requests\RestaurantProductRequest;
use Modules\Product\App\Models\Product;
use Modules\Product\App\Models\ProductTranslation;

class RestaurantProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::where('restaurant_id',Auth::guard('restaurant')->user()->id)->with('translate_product')->get();
        return view('product::restaurant.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::with('translate')->where('status', 'enable')->get();
        $addons = Addon::where('restaurant_id',Auth::guard('restaurant')->user()->id)->with('translate')->where('status', 'enable')->get();
        return view('product::restaurant.create', compact('categories', 'addons'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RestaurantProductRequest $request): RedirectResponse
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
        $product->restaurant_id = Auth::guard('restaurant')->user()->id;
        $product->category_id = $request->category_id;
        $product->price = $request->product_price;
        $product->offer_price = $request->offer_price == null ? 0 : $request->offer_price;
        $product->addon_items = json_encode($request['addon_items']);
        $product->status = $request->status == 'on' ? 'enable' : 'disable';
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
        $message = "Created successfully";
        $notification = array('message' => $message, 'alert-type' => 'success');
        return redirect()->route('restaurant.product.index')->with($notification);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $id)
    {
        $product = Product::find($id);
        $categories = Category::with('translate')->where('status', 'enable')->get();
        $addons = Addon::where('restaurant_id',Auth::guard('restaurant')->user()->id)->with('translate')->where('status', 'enable')->get();
        $product_translate = ProductTranslation::where(['product_id' => $id, 'lang_code' => $request->lang_code])->first();
        $intArray = json_decode($product->addon_items);
        if ($intArray != null) {
            $selected_ids = array_map('intval', $intArray);
        } else {
            $selected_ids = array();
        }
        return view('product::restaurant.edit', compact('categories', 'addons', 'product', 'selected_ids', 'product_translate'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RestaurantProductRequest $request, $id): RedirectResponse
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
            $product->restaurant_id = Auth::guard('restaurant')->user()->id;
            $product->category_id = $request->category_id;
            $product->price = $request->product_price;
            $product->offer_price = $request->offer_price == null ? 0 : $request->offer_price;
            $product->addon_items = json_encode($request['addon_items']);
            $product->status = $request->status == 'on' ? 'enable' : 'disable';
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

        $message = "Updated successfully";
        $notification = array('message' => $message, 'alert-type' => 'success');
        return redirect()->route('restaurant.product.index')->with($notification);
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

            if ($old_image) {
                if (File::exists(public_path() . '/' . $old_image)) unlink(public_path() . '/' . $old_image);
            }

            $message = "Deleted Product successfully";
            $notification = array('message' => $message, 'alert-type' => 'success');
            return redirect()->back()->with($notification);

        } catch (\Exception $e) {
            $message = $e->getMessage();
            $notification = array('message' => $message, 'alert-type' => 'success');
            return redirect()->back()->with($notification);
        }
    }
}
