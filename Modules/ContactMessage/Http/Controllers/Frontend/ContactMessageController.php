<?php

namespace Modules\ContactMessage\Http\Controllers\Frontend;

use Mail;
use Exception;
use App\Helper\EmailHelper;
use App\Helpers\MailHelper;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\EmailSetting\App\Models\EmailTemplate;
use Modules\ContactMessage\Entities\ContactMessage;
use Modules\GlobalSetting\App\Models\GlobalSetting;
use Modules\ContactMessage\Emails\SendContactMessage;
use Modules\ContactMessage\Http\Requests\ContactMessageRequest;

class ContactMessageController extends Controller
{
    public function store_contact_message(ContactMessageRequest $request): \Illuminate\Http\RedirectResponse
    {

        $mail_status = GlobalSetting::where('key', 'send_contact_message')->first();
        $contact_mail = GlobalSetting::where('key', 'contact_message_mail')->first();

        $contact_message = new ContactMessage();
        $contact_message->name = $request->name;
        $contact_message->email = $request->email;
        $contact_message->phone = $request->phone;
        $contact_message->subject = $request->subject;
        $contact_message->message = $request->message;
        $contact_message->save();

        if($mail_status->value == 'enable'){

            EmailHelper::mail_setup();

            $template = EmailTemplate::find(2);
            $message = $template->description;
            $subject = $template->subject;
            $message = str_replace('{{user_name}}',$request->name,$message);
            $message = str_replace('{{user_email}}',$request->email,$message);
            $message = str_replace('{{user_phone}}',$request->phone,$message);
            $message = str_replace('{{message_subject}}',$request->subject,$message);
            $message = str_replace('{{message}}',$request->message,$message);

            try{
                Mail::to($contact_mail->value)->send(new SendContactMessage($message,$subject, $request->email, $request->name));

            }catch(Exception $ex){
                Log::info($ex->getMessage());
            }

        }

        $notification= trans('translate.Your message send successfully');
        $notification=array('message'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);

    }

}
