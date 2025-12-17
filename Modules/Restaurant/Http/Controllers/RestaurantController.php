<?php

namespace Modules\Restaurant\Http\Controllers;

use Image, File, Str;
use App\Models\Review;
use Illuminate\Http\Request;
use Modules\City\Entities\City;
use App\Models\RestaurantWishlist;
use Illuminate\Routing\Controller;
use Modules\Order\App\Models\Order;
use Illuminate\Support\Facades\Hash;
use Modules\Cuisine\Entities\Cuisine;
use Modules\Product\App\Models\Product;
use Modules\Restaurant\Entities\Restaurant;
use Illuminate\Contracts\Support\Renderable;
use Modules\PaymentWithdraw\App\Models\SellerWithdraw;
use Modules\Restaurant\Http\Requests\RestaurantRequest;

class RestaurantController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $restaurants = Restaurant::latest()->get();

        return view('restaurant::index', ['restaurants' => $restaurants]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {

        $cities = City::with('translate')->get();
        $cuisines = Cuisine::with('translate')->get();

        return view('restaurant::create', ['cities' => $cities, 'cuisines' => $cuisines]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(RestaurantRequest $request)
    {

        $restaurant = new Restaurant();

        // set basic info
        $restaurant->restaurant_name = $request->restaurant_name;
        $restaurant->slug = $request->slug;
        $restaurant->city_id = $request->city_id;
        $restaurant->cuisines = json_encode($request->cuisines);

        if($request->logo){
            $image_name = 'restaurant-logo-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name ='uploads/custom-images/'.$image_name;
            Image::make($request->logo)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $restaurant->logo = $image_name;
        }

        if($request->cover_image){
            $image_name = 'restaurant-cover-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name ='uploads/custom-images/'.$image_name;
            Image::make($request->cover_image)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $restaurant->cover_image = $image_name;
        }

        // end basic info

        // set addres info
        $restaurant->whatsapp = $request->whatsapp;
        $restaurant->address = $request->address;
        $restaurant->latitude = $request->latitude;
        $restaurant->longitude = $request->longitude;
        $restaurant->max_delivery_distance = $request->max_delivery_distance;
        // end addres info

        // set owner info
        $restaurant->owner_name = $request->owner_name;
        $restaurant->owner_email = $request->owner_email;
        $restaurant->owner_phone = $request->owner_phone;
        // end owner info

        // set account info
        $restaurant->name = $request->name;
        $restaurant->email = $request->email;
        $restaurant->password = Hash::make($request->password);
        // end account info

        // set other info
        $restaurant->opening_hour = $request->opening_hour;
        $restaurant->closing_hour = $request->closing_hour;
        $restaurant->min_processing_time = $request->min_processing_time;
        $restaurant->max_processing_time = $request->max_processing_time;
        $restaurant->time_slot_separate = $request->time_slot_separate;
        $restaurant->tags = $request->tags;
        $restaurant->is_featured = $request->is_featured ? 'enable' : 'disable';
        $restaurant->is_pickup_order = $request->is_pickup_order ? 'enable' : 'disable';
        $restaurant->is_delivery_order = $request->is_delivery_order ? 'enable' : 'disable';
        $restaurant->admin_approval = 'enable';
        $restaurant->save();
        // end other info

        $notify_message= trans('translate.Created Successfully');
        $notify_message=array('message'=>$notify_message,'alert-type'=>'success');
        return redirect()->back()->with($notify_message);



    }


    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $restaurant = Restaurant::findOrFail($id);

        $cities = City::with('translate')->get();
        $cuisines = Cuisine::with('translate')->get();



        return view('restaurant::edit', ['restaurant' => $restaurant, 'cities' => $cities, 'cuisines' => $cuisines]);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(RestaurantRequest $request, $id)
    {
        $restaurant = Restaurant::findOrFail($id);

        // set basic info
        $restaurant->restaurant_name = $request->restaurant_name;
        $restaurant->city_id = $request->city_id;
        $restaurant->cuisines = json_encode($request->cuisines);

        if($request->logo){
            $old_image = $restaurant->logo;
            $image_name = 'restaurant-logo-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name ='uploads/custom-images/'.$image_name;
            Image::make($request->logo)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $restaurant->logo = $image_name;
            $restaurant->save();

            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }

        if($request->cover_image){
            $old_image = $restaurant->cover_image;
            $image_name = 'restaurant-cover-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name ='uploads/custom-images/'.$image_name;
            Image::make($request->cover_image)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $restaurant->cover_image = $image_name;
            $restaurant->save();
            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }

        // end basic info

        // set addres info
        $restaurant->whatsapp = $request->whatsapp;
        $restaurant->address = $request->address;
        $restaurant->latitude = $request->latitude;
        $restaurant->longitude = $request->longitude;
        $restaurant->max_delivery_distance = $request->max_delivery_distance;
        // end addres info

        // set owner info
        $restaurant->owner_name = $request->owner_name;
        $restaurant->owner_email = $request->owner_email;
        $restaurant->owner_phone = $request->owner_phone;
        // end owner info

        // set account info
        $restaurant->name = $request->name;
        // end account info

        // set other info
        $restaurant->opening_hour = $request->opening_hour;
        $restaurant->closing_hour = $request->closing_hour;
        $restaurant->min_processing_time = $request->min_processing_time;
        $restaurant->max_processing_time = $request->max_processing_time;
        $restaurant->time_slot_separate = $request->time_slot_separate;
        $restaurant->tags = $request->tags;
        $restaurant->is_featured = $request->is_featured ? 'enable' : 'disable';
        $restaurant->is_pickup_order = $request->is_pickup_order ? 'enable' : 'disable';
        $restaurant->is_delivery_order = $request->is_delivery_order ? 'enable' : 'disable';
        $restaurant->admin_approval = 'enable';
        $restaurant->save();
        // end other info

        $notify_message= trans('translate.Updated Successfully');
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
        $restaurant = Restaurant::findOrFail($id);


        $product_qty = Product::where('restaurant_id',$id)->count();

        if($product_qty > 0){
            $notification = trans('translate.You can not delete this restaurant, multiple products available under this restaurant');
            $notification = array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }

        $order_qty = Order::where('restaurant_id', $id)->count();

        if($order_qty > 0){
            $notification = trans('translate.You can not delete this restaurant, multiple orders available under this restaurant');
            $notification = array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }

        $existing_logo = $restaurant->logo;
        $existing_cover_image = $restaurant->cover_image;

        if($existing_logo){
            if(File::exists(public_path().'/'.$existing_logo))unlink(public_path().'/'.$existing_logo);
        }

        if($existing_cover_image){
            if(File::exists(public_path().'/'.$existing_cover_image))unlink(public_path().'/'.$existing_cover_image);
        }


        Review::where('restaurant_id',$id)->delete();
        RestaurantWishlist::where('restaurant_id',$id)->delete();
        SellerWithdraw::where('seller_id',$id)->delete();



        $restaurant->delete();


        $notification = trans('translate.Delete Successfully');
        $notification = array('message'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.restaurants.index')->with($notification);



    }

    public function trusted_status($id){
        $restaurant = Restaurant::findOrFail($id);
        if($restaurant->is_trusted == 1){
            $restaurant->is_trusted = 0;
            $restaurant->save();
            $message = trans('translate.Status Changed Successfully');
        }else{
            $restaurant->is_trusted = 1;
            $restaurant->save();
            $message = trans('translate.Status Changed Successfully');
        }
        return response()->json($message);
    }
}
