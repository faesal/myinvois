@extends('admin.master_layout')
@section('title')
    <title>{{ __('translate.Working Step') }}</title>
@endsection

@section('body-header')
    <h3 class="crancy-header__title m-0">{{ __('translate.Working Step') }}</h3>
    <p class="crancy-header__text">{{ __('translate.Manage Section') }} >> {{ __('translate.Working Step') }}</p>
@endsection

@section('body-content')

    <!-- crancy Dashboard -->
    <section class="crancy-adashboard crancy-show language_box">
        <div class="container container__bscreen">
            <div class="row">
                <div class="col-12">
                    <div class="crancy-body">
                        <!-- Dashboard Inner -->
                        <div class="crancy-dsinner">
                            <div class="row">
                                <div class="col-12 mg-top-30">
                                    <!-- Product Card -->
                                    <div class="crancy-product-card translation_main_box">

                                        <div class="crancy-customer-filter">
                                            <div class="crancy-customer-filter__single crancy-customer-filter__single--csearch">
                                                <div class="crancy-header__form crancy-header__form--customer">
                                                    <h4 class="crancy-product-card__title">{{ __('translate.Switch to language translation') }}</h4>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="translation_box">
                                            <ul >
                                                @foreach ($language_list as $language)
                                                <li><a href="{{ route('admin.working-step', ['lang_code' => $language->lang_code] ) }}">
                                                    @if (request()->get('lang_code') == $language->lang_code)
                                                        <i class="fas fa-eye"></i>
                                                    @else
                                                        <i class="fas fa-edit"></i>
                                                    @endif

                                                    {{ $language->lang_name }}</a></li>
                                                @endforeach
                                            </ul>

                                            <div class="alert alert-secondary" role="alert">

                                                @php
                                                    $edited_language = $language_list->where('lang_code', request()->get('lang_code'))->first();
                                                @endphp

                                            <p>{{ __('translate.Your editing mode') }} : <b>{{ $edited_language->lang_name }}</b></p>
                                            </div>
                                        </div>

                                    </div>
                                    <!-- End Product Card -->
                                </div>
                            </div>
                        </div>
                        <!-- End Dashboard Inner -->
                    </div>
                </div>

            </div>
        </div>
    </section>
    <!-- End crancy Dashboard -->

    <!-- crancy Dashboard -->
    <section class="crancy-adashboard crancy-show">
        <div class="container container__bscreen">
            <div class="row">
                <div class="col-md-12">
                    <div class="crancy-body">
                        <!-- Dashboard Inner -->
                        <div class="crancy-dsinner">
                            <form action="{{ route('admin.update-working-step') }}" method="POST" enctype="multipart/form-data">
                                @csrf

                                @method('PUT')

                                <input type="hidden" name="lang_code" value="{{ request()->get('lang_code') }}">
                                <input type="hidden" name="translate_id" value="{{ $translate->id }}">

                                <div class="row">
                                    <div class="col-12 mg-top-30">
                                        <!-- Product Card -->
                                        <div class="crancy-product-card">
                                            <div class="row">
                                                <div class="col-md-6 mg-top-form-20">
                                                    @if (admin_lang() == request()->get('lang_code'))
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <div class="crancy__item-form--group w-100 h-100">
                                                                    <label class="crancy__item-label">{{ __('translate.Icon one') }} </label>
                                                                    <div class="crancy-product-card__upload crancy-product-card__upload--border">
                                                                        <input type="file" class="btn-check" name="working_step_icon1" id="input-img1" autocomplete="off" onchange="previewImage(event)">
                                                                        <label class="crancy-image-video-upload__label" for="input-img1">
                                                                            <img id="view_img" src="{{ asset($homepage->working_step_icon1) }}">
                                                                            <h4 class="crancy-image-video-upload__title">{{ __('translate.Click here to') }} <span class="crancy-primary-color">{{ __('translate.Choose File') }}</span> {{ __('translate.and upload') }} </h4>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Title one') }} * </label>
                                                        <input class="crancy__item-input" type="text" name="working_step_title1" id="working_step_title1" value="{{ $translate->working_step_title1 }}">
                                                    </div>

                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Description one') }} * </label>
                                                        <input class="crancy__item-input" type="text" name="working_step_des1" id="working_step_des1" value="{{ $translate->working_step_des1 }}">
                                                    </div>

                                                </div>

                                                <div class="col-md-6 mg-top-form-20">
                                                    @if (admin_lang() == request()->get('lang_code'))
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <div class="crancy__item-form--group w-100 h-100">
                                                                    <label class="crancy__item-label">{{ __('translate.Icon two') }} </label>
                                                                    <div class="crancy-product-card__upload crancy-product-card__upload--border">
                                                                        <input type="file" class="btn-check" name="working_step_icon2" id="input-img2" autocomplete="off" onchange="previewImage2(event)">
                                                                        <label class="crancy-image-video-upload__label" for="input-img2">
                                                                            <img id="view_img2" src="{{ asset($homepage->working_step_icon2) }}">
                                                                            <h4 class="crancy-image-video-upload__title">{{ __('translate.Click here to') }} <span class="crancy-primary-color">{{ __('translate.Choose File') }}</span> {{ __('translate.and upload') }} </h4>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Title two') }} * </label>
                                                        <input class="crancy__item-input" type="text" name="working_step_title2" id="working_step_title2" value="{{ $translate->working_step_title2 }}">
                                                    </div>

                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Description two') }} * </label>
                                                        <input class="crancy__item-input" type="text" name="working_step_des2" id="working_step_des2" value="{{ $translate->working_step_des2 }}">
                                                    </div>

                                                </div>

                                                <div class="col-md-6 mg-top-form-20">
                                                    @if (admin_lang() == request()->get('lang_code'))
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <div class="crancy__item-form--group w-100 h-100">
                                                                    <label class="crancy__item-label">{{ __('translate.Icon three') }} </label>
                                                                    <div class="crancy-product-card__upload crancy-product-card__upload--border">
                                                                        <input type="file" class="btn-check" name="working_step_icon3" id="input-img3" autocomplete="off" onchange="previewImage3(event)">
                                                                        <label class="crancy-image-video-upload__label" for="input-img3">
                                                                            <img id="view_img3" src="{{ asset($homepage->working_step_icon3) }}">
                                                                            <h4 class="crancy-image-video-upload__title">{{ __('translate.Click here to') }} <span class="crancy-primary-color">{{ __('translate.Choose File') }}</span> {{ __('translate.and upload') }} </h4>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Title three') }} * </label>
                                                        <input class="crancy__item-input" type="text" name="working_step_title3" id="working_step_title3" value="{{ $translate->working_step_title3 }}">
                                                    </div>

                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Description three') }} * </label>
                                                        <input class="crancy__item-input" type="text" name="working_step_des3" id="working_step_des3" value="{{ $translate->working_step_des3 }}">
                                                    </div>

                                                </div>

                                                <div class="col-md-6 mg-top-form-20">
                                                    @if (admin_lang() == request()->get('lang_code'))
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <div class="crancy__item-form--group w-100 h-100">
                                                                    <label class="crancy__item-label">{{ __('translate.Icon four') }} </label>
                                                                    <div class="crancy-product-card__upload crancy-product-card__upload--border">
                                                                        <input type="file" class="btn-check" name="working_step_icon4" id="input-img4" autocomplete="off" onchange="previewImage4(event)">
                                                                        <label class="crancy-image-video-upload__label" for="input-img4">
                                                                            <img id="view_img4" src="{{ asset($homepage->working_step_icon4) }}">
                                                                            <h4 class="crancy-image-video-upload__title">{{ __('translate.Click here to') }} <span class="crancy-primary-color">{{ __('translate.Choose File') }}</span> {{ __('translate.and upload') }} </h4>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Title four') }} * </label>
                                                        <input class="crancy__item-input" type="text" name="working_step_title4" id="working_step_title4" value="{{ $translate->working_step_title4 }}">
                                                    </div>

                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Description four') }} * </label>
                                                        <input class="crancy__item-input" type="text" name="working_step_des4" id="working_step_des4" value="{{ $translate->working_step_des4 }}">
                                                    </div>

                                                </div>





                                            </div>

                                            <button class="crancy-btn mg-top-25" type="submit">{{ __('translate.Update') }}</button>

                                        </div>
                                        <!-- End Product Card -->
                                    </div>
                                </div>

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

        function videoPreviewImage(event) {
            var reader = new FileReader();
            reader.onload = function(){
                var output = document.getElementById('video_image');
                output.src = reader.result;
            }

            reader.readAsDataURL(event.target.files[0]);
        };

    </script>
@endpush
