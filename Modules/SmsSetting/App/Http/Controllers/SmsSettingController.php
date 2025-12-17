<?php

namespace Modules\SmsSetting\App\Http\Controllers;

use Exception;
use Twilio\Rest\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\SmsSetting\App\Models\SmsSetting;
use Modules\SmsSetting\App\Models\SmsTemplate;
use Modules\SmsSetting\App\Http\Requests\SmsTemplateRequest;
use Modules\SmsSetting\App\Http\Requests\TwilioSettingRequest;
use Modules\SmsSetting\App\Http\Requests\BiztechSettingRequest;

class SmsSettingController extends Controller
{

    public $setting_data;

    public function __construct()
    {
        $setting_data = SmsSetting::all();

        $sms_setting = array();

        foreach($setting_data as $data_item){
            $sms_setting[$data_item->key] = $data_item->value;
        }

        $this->setting_data = (object) $sms_setting;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        return view('smssetting::sms_configuration', ['sms_setting' => $this->setting_data]);
    }


    public function update(Request $request)
    {

        SmsSetting::where('key', 'new_order_to_user')->update(['value' => $request->new_order_to_user ? 'active' : 'inactive']);
        SmsSetting::where('key', 'order_accept_to_user')->update(['value' => $request->order_accept_to_user ? 'active' : 'inactive']);
        SmsSetting::where('key', 'order_process_to_user')->update(['value' => $request->order_process_to_user ? 'active' : 'inactive']);
        SmsSetting::where('key', 'order_on_way_to_user')->update(['value' => $request->order_on_way_to_user ? 'active' : 'inactive']);
        SmsSetting::where('key', 'order_deliver_to_user')->update(['value' => $request->order_deliver_to_user ? 'active' : 'inactive']);
        SmsSetting::where('key', 'order_cancel_to_user')->update(['value' => $request->order_cancel_to_user ? 'active' : 'inactive']);


        $notify_message = trans('translate.Updated successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);
    }


    public function twilio_configuration()
    {

        return view('smssetting::twilio_configuration', ['sms_setting' => $this->setting_data]);
    }


    public function update_twilio_configuration(TwilioSettingRequest $request)
    {
        SmsSetting::where('key', 'twilio_sid')->update(['value' => $request->twilio_sid]);
        SmsSetting::where('key', 'twilio_auth_token')->update(['value' => $request->twilio_auth_token]);
        SmsSetting::where('key', 'default_phone_code')->update(['value' => $request->default_phone_code]);
        SmsSetting::where('key', 'twilio_phone_number')->update(['value' => $request->twilio_phone_number]);
        SmsSetting::where('key', 'twilio_status')->update(['value' => $request->twilio_status ? 'active' : 'inactive']);


        $notify_message = trans('translate.Updated successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);
    }


    public function biztech_configuration()
    {

        return view('smssetting::biztech_configuration', ['sms_setting' => $this->setting_data]);
    }



    public function update_biztech_configuration(BiztechSettingRequest $request)
    {
        SmsSetting::where('key', 'biztech_api_key')->update(['value' => $request->biztech_api_key]);
        SmsSetting::where('key', 'biztech_client_id')->update(['value' => $request->biztech_client_id]);
        SmsSetting::where('key', 'biztech_sender_id')->update(['value' => $request->biztech_sender_id]);
        SmsSetting::where('key', 'default_phone_code')->update(['value' => $request->default_phone_code]);
        SmsSetting::where('key', 'biztech_status')->update(['value' => $request->biztech_status ? 'active' : 'inactive']);


        $notify_message = trans('translate.Updated successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);
    }



    public function sms_template(){

        $template_list = SmsTemplate::all();


        return view('smssetting::template_list', ['template_list' => $template_list]);
    }

    public function edit_sms_template($id){

        $template_item = SmsTemplate::findOrFail($id);

        return view('smssetting::template_edit', ['template_item' => $template_item]);


    }


    public function update_sms_template(SmsTemplateRequest $request, $id){

        $template_item = SmsTemplate::findOrFail($id);
        $template_item->subject = $request->subject;
        $template_item->description = $request->description;
        $template_item->save();

        $notify_message = trans('translate.Updated successfully');
        $notify_message = array('message' => $notify_message, 'alert-type' => 'success');
        return redirect()->back()->with($notify_message);
    }


}
