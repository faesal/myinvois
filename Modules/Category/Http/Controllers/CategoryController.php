<?php

namespace Modules\Category\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Category\Entities\Category;
use Modules\Category\Entities\CategoryTranslation;
use Modules\Category\Http\Requests\CategoryRequest;
use Modules\Language\App\Models\Language;
use Modules\Listing\Entities\Listing;
use Image, File, Str;
use Modules\Product\App\Models\Product;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(): Renderable
    {
        $categories = Category::latest()->get();

        return view('category::index', ['categories' => $categories]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {

        return view('category::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param CategoryRequest $request
     * @return Renderable
     */
    public function store(CategoryRequest $request)
    {
        $category = new Category();

        if($request->image){
            $image_name = 'category-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$request->image->getClientOriginalExtension();
            $image_name ='uploads/custom-images/'.$image_name;
            $request->image->move(public_path('uploads/custom-images'), $image_name);
            $category->icon = $image_name;
        }

        $category->slug = $request->slug;
        $category->status = $request->status ? 'enable' : 'disable';
        $category->save();

        $languages = Language::all();
        foreach($languages as $language){
            $sub_translation = new CategoryTranslation();
            $sub_translation->lang_code = $language->lang_code;
            $sub_translation->category_id = $category->id;
            $sub_translation->name = $request->name;
            $sub_translation->save();
        }

        $notify_message= trans('translate.Created Successfully');
        $notify_message=array('message'=>$notify_message,'alert-type'=>'success');
        return redirect()->route('admin.category.edit', ['category' => $category->id, 'lang_code' => admin_lang()])->with($notify_message);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit(Request $request ,$id)
    {

        $category = Category::findOrFail($id);
        $category_translate = CategoryTranslation::where(['category_id' => $id, 'lang_code' => $request->lang_code])->first();

        return view('category::edit', ['category' => $category, 'category_translate' => $category_translate]);
    }

    /**
     * Update the specified resource in storage.
     * @param CategoryRequest $request
     * @param int $id
     * @return Renderable
     */
    public function update(CategoryRequest $request, $id)
    {
        $category = Category::findOrFail($id);

        if($request->lang_code == admin_lang()){

            if($request->image){
                $old_image = $category->icon;
                $image_name = 'category-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$request->image->getClientOriginalExtension();
                $image_name ='uploads/custom-images/'.$image_name;
                $request->image->move(public_path('uploads/custom-images'), $image_name);
                $category->icon = $image_name;
                $category->save();
                if($old_image){
                    if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
                }
            }

            $category->slug = $request->slug;
            $category->status = $request->status ? 'enable' : 'disable';
            $category->save();

            $sub_translation = CategoryTranslation::findOrFail($request->translate_id);
            $sub_translation->name = $request->name;
            $sub_translation->save();

        }else{

            $sub_translation = CategoryTranslation::findOrFail($request->translate_id);
            $sub_translation->name = $request->name;
            $sub_translation->save();
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

        $product = Product::where('category_id', $id)->count();

        if($product > 0){
            $notify_message = trans('translate.Multiple product created under it, so you can not delete it');
            $notify_message = array('message'=>$notify_message,'alert-type'=>'error');
            return redirect()->back()->with($notify_message);
        }

        $category = Category::findOrFail($id);
        $old_icon = $category->icon;

        if($old_icon){
            if(File::exists(public_path().'/'.$old_icon))unlink(public_path().'/'.$old_icon);
        }

        $category->delete();

        CategoryTranslation::where('category_id', $id)->delete();

        $notify_message= trans('translate.Delete Successfully');
        $notify_message=array('message'=>$notify_message,'alert-type'=>'success');
        return redirect()->route('admin.category.index')->with($notify_message);
    }

    public function setup_language($lang_code): void
    {
        $cat_translates = CategoryTranslation::where('lang_code', admin_lang())->get();
        foreach($cat_translates as $cat_translate){
            $listing_cat_translate = new CategoryTranslation();
            $listing_cat_translate->lang_code = $lang_code;
            $listing_cat_translate->category_id = $cat_translate->category_id;
            $listing_cat_translate->name = $cat_translate->name;
            $listing_cat_translate->save();
        }
    }
}
