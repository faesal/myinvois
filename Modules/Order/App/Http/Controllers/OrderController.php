<?php

namespace Modules\Order\App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\DeliveryMan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Mail\NewOrderConfirmation;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Modules\Order\App\Models\Order;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Modules\Order\App\Models\OrderItem;
use Modules\Product\App\Models\Product;
use Modules\SmsSetting\App\Models\SmsSetting;
use Modules\SmsSetting\App\Models\SmsTemplate;
use Modules\EmailSetting\App\Models\EmailTemplate;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
       // === Filters ===
    $tz = config('app.timezone', 'Asia/Kuala_Lumpur');

    // Date range (optional)
    $start = $request->filled('date_from')
        ? Carbon::parse($request->post('date_from'), $tz)->startOfDay()->utc()
        : null;
    $end   = $request->filled('date_to')
        ? Carbon::parse($request->post('date_to'), $tz)->endOfDay()->utc()
        : null;

    // Payment status: success | pending | failed | unpaid
    $pay = $request->post('payment_status'); // nullable

    // Search by order id / billcode / transaction id
    $q = trim((string) $request->post('search'));

    // Location (restaurant)
    $restaurantId = $request->post('restaurant_id');

    $printed = $request->post('printed'); // expected: "printed" | "pending" | null

    $base = Order::query()
        ->with(['user:id,name', 'restaurant:id,restaurant_name'])
        ->when($start && $end, fn($qq) => $qq->whereBetween('created_at', [$start, $end]))
        ->when($restaurantId, fn($qq) => $qq->where('restaurant_id', $restaurantId))
        ->when($q, function ($qq) use ($q) {
            $qq->where(function ($w) use ($q) {
                $w->where('id', (int)preg_replace('/\D/', '', $q))
                  ->orWhere('billcode', 'like', "%{$q}%")
                  ->orWhere('transaction_id', 'like', "%{$q}%");
            });
        })
        // Payment status filter
        ->when($pay === 'success', fn($qq) => $qq->where('payment_status', 'success'))
        ->when($pay === 'pending', fn($qq) => $qq->where('payment_status', 'pending'))
        ->when($pay === 'failed',  fn($qq) => $qq->where('payment_status', 'failed'))
        ->when($pay === 'unpaid',  fn($qq) => $qq->where(function($w){
            $w->whereNull('payment_status')->orWhere('payment_status','!=','success');
        }))
        // 🔽 Printed filter
        ->when($printed === '1', fn($qq) => $qq->where('printed', '>', 0))
        ->when($printed === '0', fn($qq) => $qq->where(function ($w) {
            $w->whereNull('printed')->orWhere('printed', '=', 0);
        }));
    // === KPIs from the **filtered** dataset ===
    $cloneForAgg = (clone $base);

    $totalTransactions = (clone $cloneForAgg)->count('id');
    $totalPaid = (clone $cloneForAgg)->where('payment_status','success')->sum('grand_total');
    $totalUnpaid = (clone $cloneForAgg)->where(function($w){
        $w->whereNull('payment_status')->orWhere('payment_status','!=','success');
    })->sum('grand_total');

    // === Table data (paginated) ===
    $orders = $base->select([
            'id','created_at','user_id','restaurant_id',
            'grand_total','payment_status','printed'
        ])
        ->orderByDesc('id')
        ->get();

    // For the Location filter dropdown
    $locations = DB::table('restaurants')->select('id','restaurant_name')->orderBy('restaurant_name')->get();


        return view('order::index', compact(
            'orders','totalTransactions','totalPaid','totalUnpaid','locations'
        ));
    }

    /**
     * Show the order details resource.
     */
    public function order_details($id): Factory|Application|View|\Illuminate\Contracts\Foundation\Application
    {
        $deliverymans=DeliveryMan::latest()->get();
        $order = Order::find($id);
        return view('order::details', compact('order','deliverymans'));
    }

    public function order_status_change(Request $request, $id)
    {

        $order = Order::find($id);
        $order->order_status = $request->order_status;
        $order->save();

        $template = null;
        if($request->order_status == 2){
            $template = EmailTemplate::find(6);
            $this->send_sms_top($order, 'order_accept_to_user');
        }elseif($request->order_status == 3){
            $template = EmailTemplate::find(7);
            $this->send_sms_top($order, 'order_process_to_user');
        }elseif($request->order_status == 4){
            $template = EmailTemplate::find(8);
            $this->send_sms_top($order, 'order_on_way_to_user');
        }elseif($request->order_status == 5){
            $template = EmailTemplate::find(9);
            $this->send_sms_top($order, 'order_deliver_to_user');
        }elseif($request->order_status == 6){
            $template = EmailTemplate::find(10);
            $this->send_sms_top($order, 'order_cancel_to_user');
        }

        if($template != null){
            $message = $template->description;
            $subject = $template->subject;

            $message = str_replace('{{order_id}}',$order->id,$message);

            try{

                if($order->is_guest && $order->is_guest == 1){

                    $address_info = json_decode($order->delivery_address,true);

                    $message = str_replace('{{user_name}}',$address_info['contact_person_name'] ?? '',$message);

                    Mail::to($address_info['contact_person_email'])->send(new NewOrderConfirmation($message,$subject));

                }else{
                    $user = User::find($order->user_id);
                    $message = str_replace('{{user_name}}',$user->name,$message);
                    $message = str_replace('{{order_id}}',$order->id,$message);

                    Mail::to($user->email)->send(new NewOrderConfirmation($message,$subject));
                }

            }catch(Exception $ex){
                Log::info($ex->getMessage());
            }
        }


        $message = trans('translate.Status Changed Successfully');
        $notification = array('message'=>$message,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

    private function send_sms_top($order, $order_status){
        try{

            $sms_setting = SmsSetting::where('key', $order_status)->first();

            if($sms_setting->value == 'active'){
                $template = SmsTemplate::where('template_key', $order_status)->first();

                if($template){
                    $message = $template->description;
                    $subject = $template->subject;

                    if($order->is_guest && $order->is_guest == 1){

                        $address_info = json_decode($order->delivery_address,true);
                        $message = str_replace('{{order_id}}',$order->id,$message);
                        $message = str_replace('{{user_name}}',$address_info['contact_person_name'] ?? '',$message);

                        if($address_info['contact_person_number']){
                            sendMobileOTP($address_info['contact_person_number'] ?? '0', $message);
                        }

                    }else{
                        $user = User::find($order->user_id);
                        $message = str_replace('{{user_name}}',$user->name,$message);
                        $message = str_replace('{{order_id}}',$order->id,$message);

                        if($user->phone){
                            sendMobileOTP($user->phone, $message);
                        }

                    }

                }
            }

        }catch(Exception $ex){
            Log::info($ex->getMessage());
        }
    }

    public function payment_status_change(Request $request, $id): RedirectResponse
    {
        $order = Order::find($id);
        $order->payment_status = $request->payment_status;
        $order->save();

        $message = trans('translate.Status Changed Successfully');
        $notification = array('message'=>$message,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

    public function deliveryman(Request $request, $id): RedirectResponse
    {
        $order = Order::find($id);
        $order->delivery_man_id = $request->delivery_man_id;
        $order->save();

        $message = trans('translate.Order Delivered Successfully');
        $notification = array('message'=>$message,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

    public function delete_order($id): RedirectResponse
    {
        try{
            OrderItem::where('order_id',$id)->delete();
            Order::where('id',$id)->delete();

            $message = trans('translate.Delete Successfully');
            $notification = array('message'=>$message,'alert-type'=>'success');
            return redirect()->route('admin.order.index')->with($notification);

        }catch(\Exception $e)
        {
            $message = $e->getMessage();
            $notification = array('message'=>$message,'alert-type'=>'success');
            return redirect()->back()->with($notification);
        }
    }

    public function invoice($id): Factory|Application|View|\Illuminate\Contracts\Foundation\Application
    {
        $order = Order::findOrFail($id);
        return view('order::invoice',compact('order'));
    }

}
