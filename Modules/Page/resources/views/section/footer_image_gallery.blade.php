@extends('admin.master_layout')
@section('title')
    <title>{{ __('translate.Footer Image Gallery') }}</title>
@endsection

@section('body-header')
    <h3 class="crancy-header__title m-0">{{ __('translate.Footer Image Gallery') }}</h3>
    <p class="crancy-header__text">{{ __('translate.Manage Section') }} >> {{ __('translate.Footer Image Gallery') }}</p>
@endsection

@section('body-content')

    <!-- End crancy Dashboard -->

    <!-- crancy Dashboard -->
    <section class="crancy-adashboard crancy-show">
        <div class="container container__bscreen">
            <div class="row">
                <div class="col-12">
                    <div class="crancy-body">
                        <!-- Dashboard Inner -->
                        <div class="crancy-dsinner">
                            <form action="{{ route('admin.update-footer-image-gallery') }}" method="POST" enctype="multipart/form-data">
                                @csrf

                                @method('PUT')

                                <div class="row">
                                    <div class="col-12 mg-top-30">
                                        <!-- Product Card -->
                                        <div class="crancy-product-card">

                                            <div class="row">

                                                <div class="col-12">
                                                    <div class="row">
                                                        <div class="col-md-6">

                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="crancy__item-form--group w-100 h-100">
                                                                        <label class="crancy__item-label">{{ __('translate.Image One') }} </label>
                                                                        <div class="crancy-product-card__upload crancy-product-card__upload--border">
                                                                            <input type="file" class="btn-check" name="footer_img_one" id="input-img1" autocomplete="off" onchange="previewImage(event)">
                                                                            <label class="crancy-image-video-upload__label" for="input-img1">
                                                                                <img id="view_img" src="{{ asset($homepage->footer_img_one) }}" class="intro_imagee">
                                                                                <h4 class="crancy-image-video-upload__title">{{ __('translate.Click here to') }} <span class="crancy-primary-color">{{ __('translate.Choose File') }}</span> {{ __('translate.and upload') }} </h4>
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                            </div>

                                                            <div class="crancy__item-form--group mg-top-form-20">
                                                                <label class="crancy__item-label">{{ __('translate.Link') }} </label>
                                                                <input class="crancy__item-input" type="text" name="footer_img_one_link" value="{{ $homepage->footer_img_one_link }}">
                                                            </div>

                                                        </div>



                                                        <div class="col-md-6">
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="crancy__item-form--group w-100 h-100">
                                                                        <label class="crancy__item-label">{{ __('translate.Image Two') }} </label>
                                                                        <div class="crancy-product-card__upload crancy-product-card__upload--border">
                                                                            <input type="file" class="btn-check" name="footer_img_two" id="input-img2" autocomplete="off" onchange="previewImage2(event)">
                                                                            <label class="crancy-image-video-upload__label" for="input-img2">
                                                                                <img class="intro_imagee" id="view_img2" src="{{ asset($homepage->footer_img_two) }}">
                                                                                <h4 class="crancy-image-video-upload__title">{{ __('translate.Click here to') }} <span class="crancy-primary-color">{{ __('translate.Choose File') }}</span> {{ __('translate.and upload') }} </h4>
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="crancy__item-form--group mg-top-form-20">
                                                                <label class="crancy__item-label">{{ __('translate.Link') }} </label>
                                                                <input class="crancy__item-input" type="text" name="footer_img_two_link" value="{{ $homepage->footer_img_two_link }}">
                                                            </div>

                                                        </div>



                                                        <div class="col-md-6 mg-top-20 ">
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="crancy__item-form--group w-100 h-100">
                                                                        <label class="crancy__item-label">{{ __('translate.Image Three') }} </label>
                                                                        <div class="crancy-product-card__upload crancy-product-card__upload--border">
                                                                            <input type="file" class="btn-check" name="footer_img_three" id="input-img3" autocomplete="off" onchange="previewImage3(event)">
                                                                            <label class="crancy-image-video-upload__label" for="input-img3">
                                                                                <img id="view_img3" src="{{ asset($homepage->footer_img_three) }}" class="intro_imagee">
                                                                                <h4 class="crancy-image-video-upload__title">{{ __('translate.Click here to') }} <span class="crancy-primary-color">{{ __('translate.Choose File') }}</span> {{ __('translate.and upload') }} </h4>
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="crancy__item-form--group mg-top-form-20">
                                                                <label class="crancy__item-label">{{ __('translate.Link') }} </label>
                                                                <input class="crancy__item-input" type="text" name="footer_img_three_link" value="{{ $homepage->footer_img_three_link }}">
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6  mg-top-20">
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="crancy__item-form--group w-100 h-100">
                                                                        <label class="crancy__item-label">{{ __('translate.Image Four') }} </label>
                                                                        <div class="crancy-product-card__upload crancy-product-card__upload--border">
                                                                            <input type="file" class="btn-check" name="footer_img_four" id="input-img4" autocomplete="off" onchange="previewImage4(event)">
                                                                            <label class="crancy-image-video-upload__label" for="input-img4">
                                                                                <img class="intro_imagee" id="view_img4" src="{{ asset($homepage->footer_img_four) }}">
                                                                                <h4 class="crancy-image-video-upload__title">{{ __('translate.Click here to') }} <span class="crancy-primary-color">{{ __('translate.Choose File') }}</span> {{ __('translate.and upload') }} </h4>
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="crancy__item-form--group mg-top-form-20">
                                                                <label class="crancy__item-label">{{ __('translate.Link') }} </label>
                                                                <input class="crancy__item-input" type="text" name="footer_img_four_link" value="{{ $homepage->footer_img_four_link }}">
                                                            </div>

                                                        </div>


                                                        <div class="col-md-6  mg-top-20">
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="crancy__item-form--group w-100 h-100">
                                                                        <label class="crancy__item-label">{{ __('translate.Image Four') }} </label>
                                                                        <div class="crancy-product-card__upload crancy-product-card__upload--border">
                                                                            <input type="file" class="btn-check" name="footer_img_five" id="input-img5" autocomplete="off" onchange="previewImage5(event)">
                                                                            <label class="crancy-image-video-upload__label" for="input-img5">
                                                                                <img class="intro_imagee" id="view_img5" src="{{ asset($homepage->footer_img_five) }}">
                                                                                <h4 class="crancy-image-video-upload__title">{{ __('translate.Click here to') }} <span class="crancy-primary-color">{{ __('translate.Choose File') }}</span> {{ __('translate.and upload') }} </h4>
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="crancy__item-form--group mg-top-form-20">
                                                                <label class="crancy__item-label">{{ __('translate.Link') }} </label>
                                                                <input class="crancy__item-input" type="text" name="footer_img_five_link" value="{{ $homepage->footer_img_five_link }}">
                                                            </div>

                                                        </div>


                                                        <div class="col-md-6  mg-top-20">
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="crancy__item-form--group w-100 h-100">
                                                                        <label class="crancy__item-label">{{ __('translate.Image Four') }} </label>
                                                                        <div class="crancy-product-card__upload crancy-product-card__upload--border">
                                                                            <input type="file" class="btn-check" name="footer_img_six" id="input-img6" autocomplete="off" onchange="previewImage6(event)">
                                                                            <label class="crancy-image-video-upload__label" for="input-img6">
                                                                                <img class="intro_imagee" id="view_img6" src="{{ asset($homepage->footer_img_six) }}">
                                                                                <h4 class="crancy-image-video-upload__title">{{ __('translate.Click here to') }} <span class="crancy-primary-color">{{ __('translate.Choose File') }}</span> {{ __('translate.and upload') }} </h4>
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="crancy__item-form--group mg-top-form-20">
                                                                <label class="crancy__item-label">{{ __('translate.Link') }} </label>
                                                                <input class="crancy__item-input" type="text" name="footer_img_six_link" value="{{ $homepage->footer_img_six_link }}">
                                                            </div>

                                                        </div>


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

        function previewImage4(event) {
            var reader = new FileReader();
            reader.onload = function(){
                var output = document.getElementById('view_img4');
                output.src = reader.result;
            }

            reader.readAsDataURL(event.target.files[0]);
        };

        function previewImage5(event) {
            var reader = new FileReader();
            reader.onload = function(){
                var output = document.getElementById('view_img5');
                output.src = reader.result;
            }

            reader.readAsDataURL(event.target.files[0]);
        };

        function previewImage6(event) {
            var reader = new FileReader();
            reader.onload = function(){
                var output = document.getElementById('view_img6');
                output.src = reader.result;
            }

            reader.readAsDataURL(event.target.files[0]);
        };




    </script>
@endpush
