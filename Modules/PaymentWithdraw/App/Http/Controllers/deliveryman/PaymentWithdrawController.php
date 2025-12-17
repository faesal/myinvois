<?php

namespace Modules\PaymentWithdraw\App\Http\Controllers\deliveryman;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use App\Http\Controllers\Controller;
use App\Models\DeliveryManWithdraw;
use Illuminate\Http\RedirectResponse;
use Modules\PaymentWithdraw\App\Models\SellerWithdraw;

class PaymentWithdrawController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $withdraw_list = DeliveryManWithdraw::with('deliveryman')->latest()->get();

        return view('paymentwithdraw::deliveryman.index', [
            'withdraw_list' => $withdraw_list
        ]);
    }


    /**
     * Show the specified resource.
     */
    public function show($id): Factory|Application|View|\Illuminate\Contracts\Foundation\Application
    {
        $withdraw = DeliveryManWithdraw::findOrFail($id);
        return view('paymentwithdraw::deliveryman.show', [
            'withdraw' => $withdraw
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function withdraw_approval($id): RedirectResponse
    {
        $withdraw = DeliveryManWithdraw::findOrFail($id);
        $withdraw->status = 'approved';
        $withdraw->save();

        $notify_message= trans('translate.Withdraw approved successful');
        $notify_message=array('message'=>$notify_message,'alert-type'=>'success');
        return redirect()->back()->with($notify_message);
    }

    public function withdraw_rejected($id): RedirectResponse
    {
        $withdraw = DeliveryManWithdraw::findOrFail($id);
        $withdraw->status = 'rejected';
        $withdraw->save();

        $notify_message= trans('translate.Withdraw rejected successful');
        $notify_message=array('message'=>$notify_message,'alert-type'=>'success');
        return redirect()->back()->with($notify_message);
    }




    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): RedirectResponse
    {
        $withdraw = DeliveryManWithdraw::findOrFail($id);
        $withdraw->delete();

        $notify_message= trans('translate.Withdraw deleted successful');
        $notify_message=array('message'=>$notify_message,'alert-type'=>'success');
        return redirect()->route('admin.deliveryman-withdraw-list.index')->with($notify_message);
    }
}
