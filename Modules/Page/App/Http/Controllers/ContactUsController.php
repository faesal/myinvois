<?php

namespace Modules\Page\App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Page\App\Http\Requests\ContactUsRequest;
use Modules\Page\App\Models\ContactUs;
use Modules\Page\App\Models\ContactUsTranslation;

class ContactUsController extends Controller
{

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index(Request $request)
    {
        $contact_us = ContactUs::first();
        $translate = ContactUsTranslation::where('lang_code', $request->lang_code)->first();

        return view('page::contact_us', compact('contact_us','translate'));
    }

    public function update(ContactUsRequest $request): RedirectResponse
    {
        $contact_us = ContactUs::first();

        if ($request->lang_code == admin_lang()) {
            $contact_us->email = $request->email;
            $contact_us->phone = $request->phone;
            $contact_us->map_code = $request->map_code;
            $contact_us->save();
        }

        $translate = ContactUsTranslation::where('lang_code', $request->lang_code)->first();

        if ($translate) {
            $translate->title = $request->title;
            $translate->save();
        } else {
            $translate = new ContactUsTranslation();
            $translate->contact_us_id =$contact_us->id;
            $translate->lang_code = $request->lang_code;
            $translate->title = $request->title;
            $translate->save();
        }

        $notification = trans('translate.Updated Successfully');

        return redirect()->back()->with([
            'message' => $notification,
            'alert-type' => 'success'
        ]);
    }


    public function setup_language($lang_code){
        $contact_translate = ContactUsTranslation::where('lang_code' , admin_lang())->first();

        $new_trans = new ContactUsTranslation();
        $new_trans->lang_code = $lang_code;
        $new_trans->contact_us_id = $contact_translate->contact_us_id;
        $new_trans->title = $contact_translate->title;
        $new_trans->save();

    }

}
