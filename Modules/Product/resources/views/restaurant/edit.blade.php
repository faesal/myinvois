@extends('restaurant.layouts.master')
@section('title')
    <title>{{ __('translate.Edit Product') }}</title>
@endsection

@section('body-header')
    <h3 class="crancy-header__title m-0">{{ __('translate.Edit Product') }}</h3>
    <p class="crancy-header__text">{{ __('translate.Manage Product') }} >> {{ __('translate.Edit Product') }}</p>
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
                            <div class="row">
                                <div class="col-12 mg-top-30">
                                    <!-- Product Card -->
                                    <div class="crancy-product-card translation_main_box">

                                        <div class="crancy-customer-filter">
                                            <div
                                                class="crancy-customer-filter__single crancy-customer-filter__single--csearch">
                                                <div class="crancy-header__form crancy-header__form--customer">
                                                    <h4 class="crancy-product-card__title">{{ __('translate.Switch to language translation') }}</h4>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="translation_box">
                                            <ul>
                                                @foreach ($language_list as $language)
                                                    <li>
                                                        <a href="{{ route('restaurant.product.edit', ['product' => $product->id, 'lang_code' => $language->lang_code] ) }}">
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

                                                <p>{{ __('translate.Your editing mode') }} :
                                                    <b>{{ $edited_language->lang_name }}</b></p>
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
                <div class="col-12">
                    <div class="crancy-body">
                        <!-- Dashboard Inner -->
                        <div class="crancy-dsinner">
                            <form action="{{ route('restaurant.product.update', $product->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                <input type="hidden" name="lang_code" value="{{ $product_translate->lang_code }}">
                                <input type="hidden" name="translate_id" value="{{ $product_translate->id }}">

                                <div class="row">
                                    <div class="col-12 mg-top-30">
                                        <!-- Product Card -->
                                        <div class="crancy-product-card">
                                            <div class="create_new_btn_inline_box">
                                                <h4 class="crancy-product-card__title">{{ __('translate.Edit Product') }}</h4>

                                                <a href="{{ route('restaurant.product.index') }}" class="crancy-btn "><i class="fa fa-list"></i> {{ __('translate.Product List') }}</a>
                                            </div>


                                            <div class="row mg-top-30">

                                                @if (admin_lang() == request()->get('lang_code'))
                                                <div class="col-12">
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <div class="crancy__item-form--group w-100 h-100">
                                                                <label class="crancy__item-label">{{ __('translate.Image') }} * </label>
                                                                <div class="crancy-product-card__upload crancy-product-card__upload--border">
                                                                    <input type="file" class="btn-check" name="image" id="input-img1" autocomplete="off" onchange="previewImage(event)">
                                                                    <label class="crancy-image-video-upload__label" for="input-img1">
                                                                        <img id="view_img" src="{{ asset($product?->image)  }}">
                                                                        <h4 class="crancy-image-video-upload__title">{{ __('translate.Click here to') }} <span class="crancy-primary-color">{{ __('translate.Choose File') }}</span> {{ __('translate.and upload') }} </h4>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>
                                                @endif

                                                <div class="col-12">
                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Name') }} * </label>
                                                        <input class="crancy__item-input" type="text" name="name" id="name" value="{{ $product_translate?->name }}" required>
                                                    </div>
                                                </div>

                                                @if (admin_lang() == request()->get('lang_code'))
                                                <div class="col-12">
                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Slug') }} * </label>
                                                        <input class="crancy__item-input" type="text" value="{{ $product->slug }}" name="slug" id="slug">
                                                    </div>
                                                </div>
                                                @endif

                                                <div class="col-12">
                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Short Description') }} * </label>

                                                        <textarea class="crancy__item-input crancy__item-textarea seo_description_box"  name="short_description" required id="short_description">{{ $product_translate?->short_description }}</textarea>
                                                    </div>
                                                </div>

                                                @if (admin_lang() == request()->get('lang_code'))
                                                <div class="col-12">
                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Category') }} * </label>
                                                        <select class="form-select crancy__item-input" name="category_id" required>
                                                            <option value="">{{ __('translate.Select Category') }}</option>
                                                            @foreach ($categories as $category)
                                                                <option {{$category->id == $product->category_id ? 'selected' : ' '}} value ="{{$category->id}}">{{$category?->translate?->name}}</option>
                                                            @endforeach

                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Price') }} * </label>
                                                        <input class="crancy__item-input" type="number" name="product_price" id="product_price" value="{{ $product?->price }}" required>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Offer Price') }} * </label>
                                                        <input class="crancy__item-input" type="number" name="offer_price" value="{{ $product?->offer_price }}" id="offer_price">
                                                    </div>
                                                </div>
                                                @endif

                                                    <div class="col-12">
                                                        <div class="crancy__item-form--group mg-top-form-20">
                                                            <label class="sherah-wc__form-label">{{ __('translate.Product Size') }}</label>
                                                            <table class="table table-bordered table-hover"
                                                                   id="dynamic_field">
                                                                @foreach(json_decode($product_translate->size, true) as  $size => $price)
                                                                    <tr class='row_table'>
                                                                        <td>
                                                                            <input type="text" name="size[]"
                                                                                   placeholder="Enter Size"
                                                                                   class="form-control name_list"
                                                                                   value="{{ $size }}"/>
                                                                        </td>
                                                                        <td>
                                                                            <input type="number" name="price[]"
                                                                                   placeholder="Enter Price"
                                                                                   class="form-control name_email"
                                                                                   value="{{ $price }}"/>
                                                                        </td>
                                                                        @if ($loop->first)
                                                                        <td>
                                                                            <button type="button" name="remove"
                                                                                    class="btn btn-danger btn_remove">
                                                                                {{ __('translate.Remove') }}
                                                                            </button>
                                                                        </td>
                                                                        @else
                                                                            <td>
                                                                                <button type="button" name="remove"
                                                                                        class="btn btn-danger btn_remove">
                                                                                    {{ __('translate.Remove') }}
                                                                                </button>
                                                                            </td>
                                                                        @endif
                                                                    </tr>
                                                                @endforeach
                                                            </table>
                                                            <div class="w-100">
                                                                <button type="button" name="add" id="add"
                                                                                class="btn btn-dash mt-1">{{ __('translate.Add New Size') }}
                                                                        </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-12">
                                                        <div class="crancy__item-form--group mg-top-form-20">
                                                            <label class="sherah-wc__form-label">{{ __('translate.Product Specification') }}</label>
                                                            <div class="checkbox-group">
                                                                <table class="table table-bordered table-hover"
                                                                       id="dynamic_field1">
                                                                    @foreach(json_decode($product_translate->specification, true) as  $name)
                                                                        <tr class="row_table1">
                                                                            <td>
                                                                                <input type="text" name="specification[]"
                                                                                       placeholder="Enter Single Item" value="{{$name}}"
                                                                                       class="form-control name_list1" required/>
                                                                            </td>
                                                                            @if ($loop->first)
                                                                            <td>
                                                                                <button type="button" name="remove1"
                                                                                        class="btn btn-danger btn_remove1">
                                                                                    {{ __('translate.Remove') }}
                                                                                </button>
                                                                            </td>
                                                                            @else
                                                                                <td>
                                                                                    <button type="button" name="remove1"
                                                                                            class="btn btn-danger btn_remove1">
                                                                                        {{ __('translate.Remove') }}
                                                                                    </button>
                                                                                </td>
                                                                            @endif
                                                                        </tr>
                                                                    @endforeach
                                                                </table>
                                                                <div class="w-100">
                                                                    <button type="button" name="add1" id="add1"
                                                                                    class="btn btn-dash mt-1">{{ __('translate.Add New Specification') }}
                                                                            </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                @if (admin_lang() == request()->get('lang_code'))
                                                <div class="col-12">
                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Addon') }} * </label>
                                                        <select class="form-select crancy__item-input select2" name="addon_items[]" multiple>
                                                            <option value="" disabled>{{ __('translate.Select Addon') }}</option>
                                                            @foreach ($addons as $addon)
                                                                <option {{  in_array($addon->id, $selected_ids) ? 'selected' : '' }} value="{{ $addon->id }}">{{ $addon->translate->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                    @endif

                                                    @if (admin_lang() == request()->get('lang_code'))
                                                        <div class="col-12">
                                                            <div class="crancy__item-form--group mg-top-form-20">
                                                                <label class="crancy__item-label">{{__('translate.Visibility Status')}} </label>
                                                                <div class="crancy-ptabs__notify-switch  crancy-ptabs__notify-switch--two">
                                                                    <label class="crancy__item-switch">
                                                                        <input name="status" type="checkbox" {{ $product->status == 'enable' ? 'checked' : '' }}>
                                                                        <span class="crancy__item-switch--slide crancy__item-switch--round"></span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif

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

@push('style_section')
    <link rel="stylesheet" href="{{ asset('global/select2/select2.min.css') }}">
@endpush

@push('js_section')

    <script src="{{ asset('global/select2/select2.min.js') }}"></script>

    <script>
        (function($) {
            "use strict"
            $(document).ready(function () {
                $("#name").on("keyup",function(e){
                    let inputValue = $(this).val();
                    let slug = inputValue.toLowerCase().replace(/[^\w ]+/g,'').replace(/ +/g,'-');
                    $("#slug").val(slug);
                })

                $('.select2').select2();

                var i = 1;
                $("#add").click(function () {
                    $('#dynamic_field').append('<tr class="row_table"><td><input type="text" name="size[]" placeholder="Enter Size" class="form-control name_list"/></td><td><input type="text" name="price[]" placeholder="Enter Price" class="form-control name_email"/></td><td><button type="button" name="remove" class="btn btn-danger btn_remove">Remove</button></td></tr>');
                    i++;
                });

                $(document).on('click', '.btn_remove', function () {
                    $(this).closest('.row_table').remove();
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

    <script>

        "use strict"
        $(document).ready(function(){
            var i = 1;
            $("#add1").click(function(){
                $('#dynamic_field1').append('<tr  class="row_table1"> <td><input type="text" name="specification[]" placeholder="Enter Single Item" class="form-control name_list1" /></td><td><button type="button" name="remove1" class="btn btn-danger btn_remove1">Remove</button></td></tr>');
            });

            $(document).on('click', '.btn_remove1', function(){
                $(this).closest('.row_table1').remove();
            });

        });

    </script>
@endpush

