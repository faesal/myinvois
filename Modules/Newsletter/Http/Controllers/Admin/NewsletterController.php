<?php

namespace Modules\Newsletter\Http\Controllers\Admin;

use App\Helper\EmailHelper;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Modules\Newsletter\Entities\Subscriber;

use Modules\Newsletter\Emails\SubscirberSendMail;
use Modules\Newsletter\Http\Requests\SendNewsletterRequest;
use Str;
use Mail;


class NewsletterController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function subscriber_list()
    {
        $subscribers = Subscriber::latest()->get();

        return view('newsletter::admin.subscriber_list', compact('subscribers'));
    }

    public function subscriber_email()
    {
        return view('newsletter::admin.mail_box');
    }

    public function send_subscriber_email(SendNewsletterRequest $request): RedirectResponse
    {
        $subscribers = Subscriber::where('is_verified',1)->get();
        if($subscribers->count() > 0){
            EmailHelper::mail_setup();
            foreach($subscribers as $index => $subscriber){
                Mail::to($subscriber->email)->send(new SubscirberSendMail($request->subject,$request->message));
            }

            $notification = trans('translate.Email Send Successfully');
            $notification = array('message'=>$notification,'alert-type'=>'success');
            return redirect()->back()->with($notification);
        }else{

            $notification = trans('translate.Something Went Wrong');
            $notification = array('message'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }
    }

    public function delete_subscriber($id): RedirectResponse
    {
        $subscriber = Subscriber::find($id);
        $subscriber->delete();

        $notification = trans('translate.Delete Successfully');
        $notification = array('message'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

}
