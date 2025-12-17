@extends('restaurant.layouts.master')
@section('title')
    <title>{{ __('translate.Order Details') }}</title>
@endsection

@section('body-header')
    <h3 class="crancy-header__title m-0">{{ __('translate.Order Details') }}</h3>
    <p class="crancy-header__text">{{ __('translate.Manage Order') }} >> {{ __('translate.Order Details') }}</p>
@endsection

@section('body-content')

    <section class="crancy-adashboard crancy-show mt-5">
        <div class="zum_icvoice">
            <div class="container container__bscreen">
                <div class="crancy-product-card">
                    <div class="create_new_btn_inline_box mb-4">
                        <h4 class="crancy-product-card__title">{{ __('translate.Order Details') }}</h4>

                        <a href="{{ route('restaurant.order.index') }}" class="crancy-btn "><i
                                class="fa fa-list"></i> {{ __('translate.Order List') }}</a>
                    </div>

                    <div class="row mb-5">
                        <div class="col-lg-4 col-md-6">
                            <div class="zum_icvoice_item_main">

                                @php
                                    $address = json_decode($order->delivery_address);
                                @endphp

                                @if($order->order_type == 'delivery')
                                    <div class="zum_invoice_text">
                                        <h2>{{__('translate.Billing Address')}}</h2>
                                    </div>
                                    <div class="zum_icvoice_item">
                                        <ul class="zum_invoice_lixt">
                                            <li>{{__('translate.Full Name')}} :
                                                <span>{{$address->contact_person_name ?? ''}}</span></li>
                                            <li>
                                                <a href="mailto:{{$address->contact_person_email ?? ''}} ">
                                                    {{__('translate.Email')}} :
                                                    <span> {{$address->contact_person_email ?? ''}} </span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="tel:{{$address->contact_person_number ?? ''}}">
                                                    {{__('translate.Phone')}} :
                                                    <span> {{$address->contact_person_number ?? ''}}</span>
                                                </a>
                                            </li>
                                            <li>
                                                {{__('translate.Address')}} : <span> {{$address->address ?? ''}} </span>
                                            </li>
                                        </ul>
                                    </div>
                                @endif
                                <div class="zum_icvoice_item">
                                    <h2>{{__('translate.Payment Information')}}:</h2>
                                    <ul class="zum_invoice_lixt">
                                        <li>{{__('translate.Method')}} : <span>{{$order->payment_method}}</span></li>
                                        <li>
                                            @if($order->payment_status == 'success')
                                                <a href="javascript:;">
                                                    {{__('translate.State')}} :<span class="tag">{{$order->payment_status}}</span>
                                                </a>
                                            @else
                                                <a href="javascript:;">
                                                    {{__('translate.State')}} :<span
                                                        class="tag denger">{{$order->payment_status}}</span>
                                                </a>
                                            @endif
                                        </li>
                                        <li>
                                            {{__('translate.Transaction')}} <span> {!! clean(nl2br($order->tnx_info)) !!}</span>
                                        </li>

                                    </ul>
                                </div>

                            </div>

                            @if($order->order_type == 'pickup')
                                <p><strong>{{__('translate.Contact person name')}}
                                        : </strong> {{$address->contact_person_name ?? ''}}</p>
                                <p><strong>{{__('translate.Contact person phone')}}
                                        : </strong> {{$address->contact_person_number ?? ''}}</p>
                                <p><strong>{{__('translate.Contact person email')}}
                                        : </strong> {{$address->contact_person_email ?? ''}}</p>
                            @endif
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="zum_icvoice_item_main">
                                @if($order->order_type == 'delivery')
                                    <div class="zum_invoice_text">
                                        <h2>{{__('translate.Shipping Information')}}</h2>


                                    </div>
                                    <div class="zum_icvoice_item">
                                        <ul class="zum_invoice_lixt">
                                            <li>{{__('translate.Full Name')}} :
                                                <span>{{$address->contact_person_name ?? ''}}</span></li>
                                            <li>
                                                <a href="mailto:{{$address->contact_person_email ?? ''}}">
                                                    {{__('translate.Email')}} :
                                                    <span> {{$address->contact_person_email ?? ''}}</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="tel:{{$address->contact_person_number ?? ''}}">
                                                    {{__('translate.Phone')}} :
                                                    <span> {{$address->contact_person_number ?? ''}} </span>
                                                </a>
                                            </li>
                                            <li>
                                                {{__('translate.Address')}} : <span>{{$address->address ?? ''}}</span>
                                            </li>
                                        </ul>
                                    </div>
                                @endif
                                <div class="zum_icvoice_item">
                                    <h2>{{__('translate.Order Information')}}:</h2>
                                    <ul class="zum_invoice_lixt">
                                        <li>{{__('translate.Date')}} : <span>{{$order->created_at->format('F j, Y') }}</span></li>

                                        <li>
                                            {{__('translate.Shipping')}} : <span> {{__('translate.Fixed Shipping')}}</span>
                                        </li>

                                        <li>
                                            <a href="javascript:;">
                                                @if($order->order_status == 1)
                                                    {{__('translate.State')}} : <span class="badge bg-warning text-white">{{__('translate.Pending')}}</span>
                                                @elseif($order->order_status == 2)
                                                    {{__('translate.State')}} : <span
                                                        class="badge bg-success text-white">{{__('translate.Confirmed')}}</span>
                                                @elseif($order->order_status == 3)
                                                    {{__('translate.State')}} : <span
                                                        class="badge bg-warning text-white">{{__('translate.Processing')}}</span>
                                                @elseif($order->order_status == 4)
                                                    {{__('translate.State')}} : <span
                                                        class="badge bg-inprocees text-white">{{__('translate.Food on the way')}}</span>
                                                @elseif($order->order_status == 5)
                                                    {{__('translate.State')}} : <span
                                                        class="badge bg-success text-white">{{__('translate.Delivered')}}</span>
                                                @elseif($order->order_status == 6)
                                                    {{__('translate.State')}} : <span
                                                        class="badge bg-warning text-white">{{__('translate.Cancel')}}</span>
                                                @endif
                                            </a>
                                        </li>

                                    </ul>
                                </div>

                            </div>
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <div class="order_status_box">
                                <form class="order_status">
                                    <div class="order_status_item">
                                        <div class="order_status_inner">
                                            <label for="exampleFormControlInput1"
                                                   class="form-label">{{__('translate.Payment status')}}</label>
                                            <select class="form-select" aria-label="Default select example">
                                                @if($order->payment_status == 'pending')
                                                    <option
                                                        value="pending" {{ $order->payment_status == 'pending' ? 'selected' : '' }}>{{ __('translate.Pending') }}</option>
                                                @elseif($order->payment_status == 'success')
                                                    <option
                                                        value="success" {{ $order->payment_status == 'success' ? 'selected' : '' }}>{{ __('translate.Success') }}</option>
                                                @endif
                                            </select>



                                        </div>
                                    </div>
                                    <div class="order_status_item">
                                        <div class="order_status_inner">
                                            <label for="exampleFormControlInput1"
                                                   class="form-label">{{__('translate.Order status')}}</label>
                                            <select class="form-select" aria-label="Default select example"
                                                    onchange="showConfirmationModal({{ $order->id }}, this.value)"
                                                    name="order_status"
                                                    name="order_status">
                                                @if($order->order_status == 5)
                                                    <option
                                                        value="5" {{ $order->order_status == 5 ? 'selected' : '' }}>{{ __('translate.Delivered') }}</option>
                                                @else
                                                    @if($order->order_status == 1)
                                                        <option
                                                            value="1" {{ $order->order_status == 1 ? 'selected' : '' }} disabled>{{ __('translate.Pending') }}</option>
                                                        <option
                                                            value="2" {{ $order->order_status == 2 ? 'selected' : '' }}>{{ __('translate.Confirmed') }}</option>
                                                            <option
                                                            value="6" {{ $order->order_status == 6 ? 'selected' : '' }}>{{ __('translate.Cancel') }}</option>
                                                    @elseif($order->order_status == 2)
                                                        <option
                                                            value="2" {{ $order->order_status == 2 ? 'selected' : '' }} disabled>{{ __('translate.Confirmed') }}</option>
                                                        <option
                                                            value="3" {{ $order->order_status == 3 ? 'selected' : '' }}>{{ __('translate.Processing') }}</option>
                                                    @elseif($order->order_status == 3)
                                                        <option
                                                            value="3" {{ $order->order_status == 3 ? 'selected' : '' }} disabled>{{ __('translate.Processing') }}</option>
                                                        <option
                                                            value="4" {{ $order->order_status == 4 ? 'selected' : '' }}>{{ __('translate.Food On The Way') }}</option>
                                                    @elseif($order->order_status == 6)
                                                    <option
                                                            value="6" {{ $order->order_status == 6 ? 'selected' : '' }}>{{ __('translate.Cancel') }}</option>
                                                    @else
                                                        <option
                                                            value="4" {{ $order->order_status == 4 ? 'selected' : '' }} disabled>{{ __('translate.Food On The Way') }}</option>
                                                        <option
                                                            value="5" {{ $order->order_status == 5 ? 'selected' : '' }}>{{ __('translate.Delivered 2') }}</option>
                                                    @endif
                                                @endif

                                            </select>


                                        </div>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="tabel_main">
                                <table class=" zum_tabel table table-striped">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{__('translate.Product')}}</th>
                                        <th>{{__('translate.Variant')}}</th>
                                        <th>{{__('translate.Restaurant')}}</th>
                                        <th>{{__('translate.Unit Price')}}</th>
                                        <th>{{__('translate.Quantity')}}</th>
                                        <th>{{__('translate.Total')}}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @php
                                        $total = 0;
                                        $calculate = 0;
                                    @endphp
                                    @foreach($order->items ?? [] as $key => $order_item)
                                        @php
                                            $product = Modules\Product\App\Models\Product::where('status', 'enable')->whereIn('id', [$order_item['product_id']])->first();
                                            $total += $order_item->total;
                                        @endphp
                                        <tr>
                                            <td>{{$key + 1}}</td>
                                            <td><a href="{{ route('restaurant.product.edit', ['product' => $product->id, 'lang_code' => admin_lang()] ) }}">{{$product->name}}</a></td>
                                            <td>
                                                <div class="tabel_modal_main">
                                                    @foreach (json_decode($order_item['size']) as $size => $price)
                                                        {{__('translate.Size')}} : {{ $size }}
                                                    @endforeach
                                                    @if(json_decode($order_item['addons']))
                                                        <span data-bs-toggle="modal"
                                                              data-bs-target="#exampleModal{{$key}}">
                                                        {{__('translate.See more')}}
                                                    </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                {{$order?->restaurant?->restaurant_name}}
                                            </td>
                                            <td>
                                                @foreach (json_decode($order_item['size']) as $size => $price)
                                                    {{(currency($price))}}
                                                @endforeach
                                            </td>
                                            <td>{{$order_item->qty}}</td>
                                            <td>{{currency($order_item->total)}}</td>
                                        </tr>

                                        <!-- Modal 2 -->
                                        <div class="modal adon_modal_main fade" id="exampleModal{{$key}}" tabindex="-1"
                                             aria-labelledby="exampleModalLabel"
                                             aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title"
                                                            id="exampleModalLabel">{{__('translate.See Addon')}}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <ul class="modal_adon">
                                                            @foreach (json_decode($order_item['addons']) as $addonId => $quantity)
                                                                @php
                                                                    $addonsDb = Modules\Addon\App\Models\Addon::whereIn('id', [$addonId])->get();
                                                                    $calculate += ($addonsDb->first()->price * $quantity);
                                                                @endphp
                                                                @if ($addonsDb->isNotEmpty())
                                                                    <li> {{ $addonsDb->first()->name }}
                                                                        ({{ currency($addonsDb->first()->price) }}
                                                                        * {{ $quantity }})
                                                                    </li>
                                                                @endif

                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                    <div class="modal_btn_main">
                                                        <button type="button" data-bs-dismiss="modal" aria-label="Close"
                                                                class="modal_btn">{{__('translate.Close')}}</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="tabel_btm">
                                <ul class="tabel_btm_list">
                                    <li><span>{{__('translate.Subtotal')}} : </span>{{currency($total)}}</li>
                                    <li><span>{{__('translate.Discount')}} (-) </span>: {{currency($order->discount_amount)}}</li>
                                    <li><span>{{__('translate.Delivery Charge')}} :</span> {{currency($order->delivery_charge)}}
                                    </li>
                                    <li>{{__('translate.Total')}} : {{currency($order->grand_total)}}</li>
                                </ul>


                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal -->
    <div class="modal fade" id="deleteModal1" tabindex="-1" aria-labelledby="exampleModalLabel1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel1">{{ __('translate.Status Change Confirmation') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('translate.Are you sure you want to change the status of this item?') }}</p>
                </div>
                <div class="modal-footer">
                    <form action="" id="item_delect_confirmation1" class="delet_modal_form" method="POST">
                        @csrf
                        <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">{{ __('translate.Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('translate.Yes, Change') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="paymentConfirmationModal" tabindex="-1" aria-labelledby="paymentModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">{{ __('translate.Payment Status Change Confirmation') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('translate.Are you sure you want to change the payment status of this order?') }}</p>
                </div>
                <div class="modal-footer">
                    <form action="" id="paymentConfirmationForm" class="delet_modal_form" method="POST">
                        @csrf
                        <input type="hidden" name="payment_status" id="newPaymentStatus">
                        <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">{{ __('translate.Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('translate.Yes, Change') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('js_section')

    <script>

         "use strict"

        function showConfirmationModal(orderId, selectedValue) {

            $("#item_delect_confirmation1").attr("action", '{{ url("restaurant/order-status-change/") }}' + "/" + orderId);

            $('<input>').attr({
                type: 'hidden',
                name: 'order_status',
                value: selectedValue
            }).appendTo('#item_delect_confirmation1');

            $('#deleteModal1').modal('show');
        }
    </script>
@endpush
