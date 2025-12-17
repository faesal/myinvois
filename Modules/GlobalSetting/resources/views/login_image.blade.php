@extends('admin.master_layout')
@section('title')
    <title>{{ __('translate.Login Page') }}</title>
@endsection

@section('body-header')
    <h3 class="crancy-header__title m-0">{{ __('translate.Login Page') }}</h3>
    <p class="crancy-header__text">{{ __('translate.Manage Pages') }} >> {{ __('translate.Login Page') }}</p>
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
                            <form action="{{ route('admin.login-image-update') }}" enctype="multipart/form-data" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="col-12 mg-top-30">
                                        <!-- Product Card -->
                                        <div class="crancy-product-card">
                                            <h4 class="crancy-product-card__title">{{ __('translate.Login Page') }}</h4>
                                            <div class="row">
                                                <div class="col-md-4 ml-auto">
                                                    <div class="crancy__item-form--group mg-top-25 w-100">
                                                        <label for="image1" class="form-label">{{ __('translate.Image One') }}</label>
                                                        <div class="crancy-product-card__upload crancy-product-card__upload--border">
                                                            <input type="file" class="btn-check" name="login_image_one" id="input-img1" autocomplete="off" onchange="previewImage(event)">
                                                            <label class="crancy-image-video-upload__label" for="input-img1">
                                                                <img id="view_img" src="{{ asset($general_setting->login_image_one) }}">
                                                                <h4 class="crancy-image-video-upload__title">{{ __('translate.Click here to') }} <span class="crancy-primary-color">{{ __('translate.Choose File') }}</span> {{ __('translate.and upload') }} </h4>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-12 mg-top-20">
                                                    <div class="sign-up-from-inner">
                                                        <label for="exampleFormControlInput1" class="form-label">{{ __('translate.Title One') }}</label>
                                                        <input type="text" class="form-control" id="exampleFormControlInput1" placeholder="Enter title" name="login_title_one" value="{{ $general_setting->login_title_one }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-12 mg-top-20 mb-5">
                                                    <div class="sign-up-from-inner">
                                                        <label for="exampleFormControlInput1" class="form-label">{{ __('translate.Description One') }}</label>
                                                        <input type="text" class="form-control" id="exampleFormControlInput1"
                                                               placeholder="Enter your description" name="login_description_one" value="{{ $general_setting->login_description_one }}">
                                                    </div>
                                                </div>

                                            </div>

                                            <hr>

                                            <div class="row">
                                                <div class="col-md-4 ml-auto">
                                                    <div class="crancy__item-form--group mg-top-25 w-100">
                                                        <label for="image2" class="form-label">{{ __('translate.Image Two') }}</label>
                                                        <div class="crancy-product-card__upload crancy-product-card__upload--border">
                                                            <input type="file" class="btn-check" name="login_image_two" id="input-img2" autocomplete="off" onchange="previewImage2(event)">
                                                            <label class="crancy-image-video-upload__label" for="input-img2">
                                                                <img id="view_img2" src="{{ asset($general_setting->login_image_two) }}">
                                                                <h4 class="crancy-image-video-upload__title">{{ __('translate.Click here to') }} <span class="crancy-primary-color">{{ __('translate.Choose File') }}</span> {{ __('translate.and upload') }} </h4>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-12 mg-top-20">
                                                    <div class="sign-up-from-inner">
                                                        <label for="exampleFormControlInput1" class="form-label">{{ __('translate.Title Two') }}</label>
                                                        <input type="text" class="form-control" id="exampleFormControlInput1" placeholder="Enter title" name="login_title_two" value="{{ $general_setting->login_title_two }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-12 mg-top-20 mb-5">
                                                    <div class="sign-up-from-inner">
                                                        <label for="exampleFormControlInput1" class="form-label">{{ __('translate.Description Two') }}</label>
                                                        <input type="text" class="form-control" id="exampleFormControlInput1"
                                                               placeholder="Enter your description" name="login_description_two" value="{{ $general_setting->login_description_two }}">
                                                    </div>
                                                </div>

                                            </div>

                                            <hr>

                                            <div class="row">
                                                <div class="col-md-4 ml-auto">
                                                    <div class="crancy__item-form--group mg-top-25 w-100">
                                                        <label for="image3" class="form-label">{{ __('translate.Image Three') }}</label>
                                                        <div class="crancy-product-card__upload crancy-product-card__upload--border">
                                                            <input type="file" class="btn-check" name="login_image_three" id="input-img3" autocomplete="off" onchange="previewImage3(event)">
                                                            <label class="crancy-image-video-upload__label" for="input-img3">
                                                                <img id="view_img3" src="{{ asset($general_setting->login_image_three) }}">
                                                                <h4 class="crancy-image-video-upload__title">{{ __('translate.Click here to') }} <span class="crancy-primary-color">{{ __('translate.Choose File') }}</span> {{ __('translate.and upload') }} </h4>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-12 mg-top-20">
                                                    <div class="sign-up-from-inner">
                                                        <label for="exampleFormControlInput1" class="form-label">{{ __('translate.Title Three') }}</label>
                                                        <input type="text" class="form-control" value="{{ $general_setting->login_title_three }}" id="exampleFormControlInput1" placeholder="Enter title" name="login_title_three">
                                                    </div>
                                                </div>

                                                <div class="col-md-12 mg-top-20">
                                                    <div class="sign-up-from-inner">
                                                        <label for="exampleFormControlInput1" class="form-label">{{ __('translate.Description Three') }}</label>
                                                        <input type="text" class="form-control" id="exampleFormControlInput1"
                                                               placeholder="Enter your description" name="login_description_three" value="{{ $general_setting->login_description_three }}">
                                                    </div>
                                                </div>

                                            </div>

                                            <button class="crancy-btn mg-top-25" type="submit">{{ __('translate.Update') }}</button>

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



@push('js_section')
    <script>
        "use strict";

        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function(){
                var output = document.getElementById('view_img');
                output.src = reader.result;
            }

            reader.readAsDataURL(event.target.files[0]);
        };

        function previewImage2(event) {
            var reader = new FileReader();
            reader.onload = function(){
                var output = document.getElementById('view_img2');
                output.src = reader.result;
            }

            reader.readAsDataURL(event.target.files[0]);
        };

        function previewImage3(event) {
            var reader = new FileReader();
            reader.onload = function(){
                var output = document.getElementById('view_img3');
                output.src = reader.result;
            }

            reader.readAsDataURL(event.target.files[0]);
        };
    </script>
@endpush
