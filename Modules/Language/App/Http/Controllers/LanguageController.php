<?php

namespace Modules\Language\App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\AboutUsTranslation;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Http\RedirectResponse;
use Modules\Language\App\Models\Language;
use Modules\City\Entities\CityTranslation;
use Modules\Page\App\Models\PrivacyPolicy;
use Modules\Blog\App\Models\BlogTranslation;
use Modules\Page\App\Models\TermAndCondition;
use Modules\Addon\App\Models\AddonTranslation;
use Modules\Page\App\Models\FooterTranslation;
use Modules\Cuisine\Entities\CuisineTranslation;
use Modules\Page\App\Models\HomepageTranslation;
use Modules\City\Http\Controllers\CityController;
use Modules\Page\App\Models\ContactUsTranslation;
use Modules\Category\Entities\CategoryTranslation;
use Modules\Product\App\Models\ProductTranslation;
use Modules\Blog\App\Models\BlogCategoryTranslation;
use Modules\Blog\App\Http\Controllers\BlogController;
use Modules\Addon\App\Http\Controllers\AddonController;
use Modules\Cuisine\Http\Controllers\CuisineController;
use Modules\Language\App\Http\Requests\LanguageRequest;
use Modules\Page\App\Http\Controllers\AboutusController;
use Modules\Page\App\Http\Controllers\PrivacyController;
use Modules\Category\Http\Controllers\CategoryController;
use Modules\Page\App\Http\Controllers\HomepageController;
use Modules\Page\App\Http\Controllers\ContactUsController;
use Modules\Product\App\Http\Controllers\ProductController;
use Modules\Blog\App\Http\Controllers\BlogCategoryController;
use Modules\Page\App\Http\Controllers\FooterContrllerController;
use Modules\Page\App\Http\Controllers\TermsConditiondController;


class LanguageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $languages = Language::latest()->get();

        return view('language::index', ['languages' => $languages]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('language::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LanguageRequest $request)
    {
        if($request->is_default){
            DB::table('languages')->update(['is_default' => 'No']);
        }

        $language = new Language();
        $language->lang_code = $request->lang_code;
        $language->lang_name = $request->lang_name;
        $language->lang_direction = $request->lang_direction;
        $language->status = $request->status ? 1 : 0;
        $language->is_default = $request->is_default ? 'Yes' : 'No';
        $language->save();


        /** generate local language */

        $path = base_path().'/lang'.'/'.$request->lang_code;

        if (! File::exists($path)) {
            File::makeDirectory($path);

            $sourcePath = base_path().'/lang/en';
            $destinationPath = $path;

            // Get all files from the source folder
            $files = File::allFiles($sourcePath);

            foreach ($files as $file) {
                $destinationFile = $destinationPath . '/' . $file->getRelativePathname();

                // Copy the file to the destination folder
                File::copy($file->getRealPath(), $destinationFile);
            }
        }

        $blog_cat_lang = new BlogCategoryController();
        $blog_cat_lang->setup_language($request->lang_code);

        $blog_lang = new BlogController();
        $blog_lang->setup_language($request->lang_code);

        $privacy_lang = new PrivacyController();
        $privacy_lang->setup_language($request->lang_code);

        $terms_condition_lang = new TermsConditiondController();
        $terms_condition_lang->setup_language($request->lang_code);

        $cuisine_lang = new CuisineController();
        $cuisine_lang->setup_language($request->lang_code);

        $city_lang = new CityController();
        $city_lang->setup_language($request->lang_code);

        $category_lang = new CategoryController();
        $category_lang->setup_language($request->lang_code);

        $addon_lang = new AddonController();
        $addon_lang->setup_language($request->lang_code);

        $footer_lang = new FooterContrllerController();
        $footer_lang->setup_language($request->lang_code);


        $about_lang = new AboutusController();
        $about_lang->setup_language($request->lang_code);

        $contact_lang = new ContactUsController();
        $contact_lang->setup_language($request->lang_code);

        $product_lang = new ProductController();
        $product_lang->setup_language($request->lang_code);

        $home_lang = new HomepageController();
        $home_lang->setup_language($request->lang_code);


        $notify_message = trans('translate.Created successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->route('admin.language.index')->with($notify_message);

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $language = Language::findOrFail($id);

        return view('language::edit', ['language' => $language]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(LanguageRequest $request, $id)
    {

        $language = Language::findOrFail($id);

        if($request->is_default){
            DB::table('languages')->update(['is_default' => 'No']);
        }

        if($language->is_default == 'Yes'){
            DB::table('languages')->where('id', 1)->update(['is_default' => 'Yes']);
        }

        $language->lang_name = $request->lang_name;
        $language->lang_direction = $request->lang_direction;
        $language->status = $request->status ? 1 : 0;
        $language->is_default = $request->is_default ? 'Yes' : 'No';
        $language->save();

        $notify_message = trans('translate.Updated successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->route('admin.language.index')->with($notify_message);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {

        if($id == 1){
            $notify_message = trans('translate.You can not delete english language');
            $notify_message = array('message' => $notify_message, 'alert-type' => 'error');
            return redirect()->route('admin.language.index')->with($notify_message);
        }

        $language = Language::findOrFail($id);
        $language->delete();

        BlogCategoryTranslation::where('lang_code' , $language->lang_code)->delete();
        BlogTranslation::where('lang_code' , $language->lang_code)->delete();
        PrivacyPolicy::where('lang_code' , $language->lang_code)->delete();
        TermAndCondition::where('lang_code' , $language->lang_code)->delete();
        CityTranslation::where('lang_code' , $language->lang_code)->delete();
        CuisineTranslation::where('lang_code' , $language->lang_code)->delete();
        CategoryTranslation::where('lang_code' , $language->lang_code)->delete();
        AddonTranslation::where('lang_code' , $language->lang_code)->delete();
        FooterTranslation::where('lang_code' , $language->lang_code)->delete();
        AboutUsTranslation::where('lang_code' , $language->lang_code)->delete();
        ContactUsTranslation::where('lang_code' , $language->lang_code)->delete();
        ProductTranslation::where('lang_code' , $language->lang_code)->delete();
        HomepageTranslation::where('lang_code' , $language->lang_code)->delete();

        $path = base_path().'/lang'.'/'.$language->lang_code;

        if (File::exists($path)) {
            File::deleteDirectory($path);
        }

        $notify_message = trans('translate.Deleted successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->route('admin.language.index')->with($notify_message);
    }


    public function theme_language(Request $request){

        if(!File::exists('lang/'.$request->lang_code.'/translate.php')){
            $notify_message = trans('translate.Requested language does not exist');
            $notify_message = array('message' => $notify_message, 'alert-type' => 'error');
            return redirect()->route('admin.language.index')->with($notify_message);
        }

        $data = include('lang/'.$request->lang_code.'/translate.php');

        return view('language::theme_language', [
            'data' => $data
        ]);


    }


    public function update_theme_language (Request $request){


        if(!File::exists('lang/'.$request->lang_code.'/translate.php')){
            $notify_message = trans('translate.Requested language does not exist');
            $notify_message = array('message' => $notify_message, 'alert-type' => 'error');
            return redirect()->route('admin.language.index')->with($notify_message);
        }

        $dataArray = [];
        foreach($request->values as $index => $value){
            $dataArray[$index] = $value;
        }

        file_put_contents('lang/'.$request->lang_code.'/translate.php', "");
        $dataArray = var_export($dataArray, true);
        file_put_contents('lang/'.$request->lang_code.'/translate.php', "<?php\n return {$dataArray};\n ?>");

        $notify_message = trans('translate.Updated successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);


    }
}
