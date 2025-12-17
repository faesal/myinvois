@extends('admin.master_layout')
@section('title')
    <title>{{ __('translate.Create Coupon') }}</title>
@endsection

@section('body-header')
    <h3 class="crancy-header__title m-0">{{ __('translate.Create Coupon') }}</h3>
    <p class="crancy-header__text">{{ __('translate.Manage Promotion') }} >> {{ __('translate.Create Coupon') }}</p>
@endsection

@section('body-content')
    <!-- crancy Dashboard -->
    <section class="crancy-adashboard crancy-show">
        <div class="container container__bscreen">
            <div class="row">
                <div class="col-12">
                    <div class="crancy-body">
                        <!-- Dashboard Inner -->
                        <div class="crancy-dsinner">
                            <form action="{{ route('admin.coupon.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf

                                <div class="row">
                                    <div class="col-12 mg-top-30">
                                        <!-- Product Card -->
                                        <div class="crancy-product-card">
                                            <div class="create_new_btn_inline_box">
                                                <h4 class="crancy-product-card__title">{{ __('translate.Create Coupon') }}</h4>

                                                <a href="{{ route('admin.coupon.index') }}" class="crancy-btn "><i class="fa fa-list"></i> {{ __('translate.Coupon List') }}</a>
                                            </div>


                                            <div class="row mg-top-30">

                                                <div class="col-12">
                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Name') }} * </label>
                                                        <input class="crancy__item-input" type="text" name="name" id="name" value="{{ old('name') }}">
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Code') }} * </label>
                                                        <input class="crancy__item-input" type="text" name="code" id="code"  value="{{ old('code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Expired Date') }} * </label>
                                                        <input class="crancy__item-input datepicker" type="text" name="expired_date" id="expired_date" value="{{ old('expired_date') ? old('expired_date') : date('Y-m-d') }}">
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Minimum purchase price') }} * </label>
                                                        <input class="crancy__item-input" type="text" name="min_purchase_price" id="min_purchase_price" value="{{ old('min_purchase_price') }}">
                                                    </div>
                                                </div>


                                                <div class="col-12">
                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Discount Type') }} * </label>
                                                        <select class="form-select crancy__item-input" name="discount_type">
                                                            <option {{ old('discount_amount') == 'amount' ? 'selected' : '' }} value="amount">{{ __('translate.Amount') }}($)</option>
                                                            <option {{ old('discount_amount') == 'percentage' ? 'selected' : '' }} value="percentage">{{ __('translate.Percentage(%)') }}</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Discount') }} * </label>
                                                        <input class="crancy__item-input" type="text" name="discount_amount" id="discount_amount" value="{{ old('discount_amount') }}">
                                                    </div>
                                                </div>


                                                <div class="col-12">
                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{__('translate.Status')}} </label>
                                                        <div class="crancy-ptabs__notify-switch  crancy-ptabs__notify-switch--two">
                                                            <label class="crancy__item-switch">
                                                            <input name="status" type="checkbox" >
                                                            <span class="crancy__item-switch--slide crancy__item-switch--round"></span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>

                                            <button class="crancy-btn mg-top-25" type="submit">{{ __('translate.Save') }}</button>

                                        </div>
                                        <!-- End Product Card -->
                                    </div>
                                </div>
                            </form>
                        </div>
                        <!-- End Dashboard Inner -->
                    </div>
                </div>

            </div>
        </div>
    </section>
    <!-- End crancy Dashboard -->
@endsection



@push('style_section')
    <link rel="stylesheet" href="{{ asset('global/bootstrap_datepicker/bootstrap-datepicker.min.css') }}">

@endpush




@push('js_section')

<script src="{{ asset('global/bootstrap_datepicker/bootstrap-datepicker.min.js') }}"></script>

    <script>
        (function($) {
            "use strict"
            $(document).ready(function () {
                $("#name").on("keyup",function(e){
                    let inputValue = $(this).val();
                    let slug = inputValue.toLowerCase().replace(/[^\w ]+/g,'').replace(/ +/g,'-');
                    $("#slug").val(slug);
                })

                $('.datepicker').datepicker({
                    format: 'yyyy-mm-dd',
                    startDate: '-Infinity'
                });

            });
        })(jQuery);

        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function(){
                var output = document.getElementById('view_img');
                output.src = reader.result;
            }

            reader.readAsDataURL(event.target.files[0]);
        };
    </script>
@endpush


