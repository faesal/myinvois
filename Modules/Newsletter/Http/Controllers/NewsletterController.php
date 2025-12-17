<?php

namespace Modules\Newsletter\Http\Controllers;

use App\Helper\EmailHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\EmailSetting\App\Models\EmailTemplate;
use Modules\Newsletter\Entities\Subscriber;
use Modules\Newsletter\Http\Requests\NewsletterRequest;
use Str, Mail, Hash, Auth;
use Modules\Newsletter\Emails\NewsletterVerification;

class NewsletterController extends Controller
{

    public function newsletter_request(NewsletterRequest $request): RedirectResponse
    {

        $newsletter = new Subscriber();
        $newsletter->email = $request->email;
        $newsletter->verified_token = Str::random(25);
        $newsletter->save();

        EmailHelper::mail_setup();

        $verification_link = route('newsletter-verification').'?verification_link='.$newsletter->verified_token.'&email='.$newsletter->email;
        $verification_link = '<a href="'.$verification_link.'">'.$verification_link.'</a>';

        $template = EmailTemplate::find(3);
        $message = $template->description;
        $subject = $template->subject;
        $message = str_replace('{{verification_link}}',$verification_link,$message);


        Mail::to($newsletter->email)->send(new NewsletterVerification($message,$subject));

        $notification = trans('translate.A verification link has been send to your email, please verify and enjoy our newsletter');
        $notification = array('message'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);

    }


    public function newsletter_verification(Request $request): RedirectResponse
    {
        $newsletter = Subscriber::where(['email' => $request->email, 'verified_token' => $request->verification_link])->first();

        if($newsletter){
            $newsletter->verified_token = null;
            $newsletter->is_verified = 1;
            $newsletter->status = 1;
            $newsletter->save();

            $notification = trans('translate.Email verification successfully');
            $notification = array('message'=>$notification,'alert-type'=>'success');
            return redirect()->route('home')->with($notification);
        }else{
            $notification = trans('translate.Something went wrong');
            $notification = array('message'=>$notification,'alert-type'=>'error');
            return redirect()->route('home')->with($notification);
        }
    }

}
