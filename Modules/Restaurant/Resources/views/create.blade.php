@extends('admin.master_layout')
@section('title')
    <title>{{ __('translate.Create Restaurant') }}</title>
@endsection

@section('body-header')
    <h3 class="crancy-header__title m-0">{{ __('translate.Create Restaurant') }}</h3>
    <p class="crancy-header__text">{{ __('translate.Manage Restaurant') }} >> {{ __('translate.Create Restaurant') }}</p>
@endsection

@section('body-content')

    <form action="{{ route('admin.restaurants.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

    <!-- Basic Information -->
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
                                    <div class="crancy-product-card">
                                        <div class="create_new_btn_inline_box">
                                            <h4 class="crancy-product-card__title">{{ __('translate.Basic Information') }}</h4>
                                        </div>

                                        <div class="row">

                                            <div class="col-12 mg-top-form-20">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="crancy__item-form--group w-100 h-100">
                                                            <label class="crancy__item-label">{{ __('translate.Logo Image') }} * </label>
                                                            <div class="crancy-product-card__upload crancy-product-card__upload--border">
                                                                <input type="file" class="btn-check" name="logo" id="input-img1" autocomplete="off" onchange="previewImage(event)">
                                                                <label class="crancy-image-video-upload__label" for="input-img1">
                                                                    <img id="view_img" src="{{ asset($general_setting->placeholder_image) }}">
                                                                    <h4 class="crancy-image-video-upload__title">{{ __('translate.Click here to') }} <span class="crancy-primary-color">{{ __('translate.Choose File') }}</span> {{ __('translate.and upload') }} </h4>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-8">
                                                        <div class="crancy__item-form--group w-100 h-100">
                                                            <label class="crancy__item-label">{{ __('translate.Cover Image') }} * </label>
                                                            <div class="crancy-product-card__upload crancy-product-card__upload--border">
                                                                <input type="file" class="btn-check" name="cover_image" id="input-coverimage" autocomplete="off" onchange="previewCoverImage(event)">
                                                                <label class="crancy-image-video-upload__label" for="input-coverimage">
                                                                    <img id="view_cover_img" src="{{ asset($general_setting->placeholder_image) }}">
                                                                    <h4 class="crancy-image-video-upload__title">{{ __('translate.Click here to') }} <span class="crancy-primary-color">{{ __('translate.Choose File') }}</span> {{ __('translate.and upload') }} </h4>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>



                                            <div class="col-md-6">
                                                <div class="crancy__item-form--group mg-top-form-20">
                                                    <label class="crancy__item-label">{{ __('translate.Restaurant Name') }} * </label>
                                                    <input class="crancy__item-input" type="text" name="restaurant_name" id="title" value="{{ old('restaurant_name') }}">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="crancy__item-form--group mg-top-form-20">
                                                    <label class="crancy__item-label">{{ __('translate.Slug') }} * </label>
                                                    <input class="crancy__item-input" type="text" name="slug" id="slug" value="{{ old('slug') }}">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="crancy__item-form--group mg-top-form-20">
                                                    <label class="crancy__item-label">{{ __('translate.City') }} * </label>
                                                    <select class="form-select crancy__item-input" name="city_id">
                                                        <option value="">{{ __('translate.Select City') }}</option>
                                                        @foreach ($cities as $city)
                                                            <option {{ $city->id == old('city_id') ? 'selected' : '' }} value="{{ $city->id }}">{{ $city->translate->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="crancy__item-form--group mg-top-form-20">
                                                    <label class="crancy__item-label">{{ __('translate.Cuisine') }} * </label>
                                                    <select class="form-select crancy__item-input select2" name="cuisines[]" multiple>
                                                        <option value="">{{ __('translate.Select Cuisine') }}</option>
                                                        @foreach ($cuisines as $cuisine)
                                                            <option {{ $cuisine->id == old('cuisine') ? 'selected' : '' }} value="{{ $cuisine->id }}">{{ $cuisine->translate->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
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
    <!-- End Basic Information -->


     <!-- Contact and google map -->
    <section class="crancy-adashboard crancy-show">
        <div class="container container__bscreen">
            <div class="row">
                <div class="col-12">
                    <div class="crancy-body">
                        <!-- Dashboard Inner -->
                        <div class="crancy-dsinner">
                            <div class="row">
                                <div class="col-12">
                                    <!-- Product Card -->
                                    <div class="crancy-product-card">
                                        <div class="create_new_btn_inline_box">
                                            <h4 class="crancy-product-card__title">{{ __('translate.Contact, Address & Delivery Area') }}</h4>
                                        </div>
                                        <div class="row">

                                            <div class="col-md-6">
                                                <div class="col-12">
                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.WhatsApp Phone') }} * </label>
                                                        <input class="crancy__item-input" type="text" name="whatsapp" id="whatsapp" value="{{ old('whatsapp') }}">
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Address') }} * </label>
                                                        <input class="crancy__item-input" type="text" name="address" id="plain_address" value="{{ old('address') }}">
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Latitude') }} * </label>
                                                        <input class="crancy__item-input" type="text" name="latitude" id="latitude" value="{{ old('latitude') }}" readonly>
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Longitude') }} * </label>
                                                        <input class="crancy__item-input" type="text" name="longitude" id="longitude" value="{{ old('longitude') }}" readonly>
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="crancy__item-form--group mg-top-form-20">
                                                        <label class="crancy__item-label">{{ __('translate.Maximum Delivery Distance (km)') }} * </label>
                                                        <input class="crancy__item-input" type="text" name="max_delivery_distance" id="max_delivery_distance" value="{{ old('max_delivery_distance') }}">
                                                    </div>
                                                </div>



                                            </div>
                                            <div class="col-md-6">
                                                <div class="crancy__item-form--group mg-top-form-20">
                                                    <label class="crancy__item-label">{{ __('translate.Your Location') }} * </label>

                                                    <input id="searchMapInput" class="mapControls" type="text" placeholder="{{ __('translate.Enter a location') }}">

                                                    <div id="google_map_area">

                                                    </div>

                                                </div>


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
    <!-- End Contact and google map -->

    <!--  Owner Info -->
    <section class="crancy-adashboard crancy-show">
        <div class="container container__bscreen">
            <div class="row">
                <div class="col-12">
                    <div class="crancy-body">
                        <!-- Dashboard Inner -->
                        <div class="crancy-dsinner">
                            <div class="row">
                                <div class="col-12">
                                    <!-- Product Card -->
                                    <div class="crancy-product-card">
                                        <div class="create_new_btn_inline_box">
                                            <h4 class="crancy-product-card__title">{{ __('translate.Restaurant Owner Information') }}</h4>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="crancy__item-form--group mg-top-form-20">
                                                    <label class="crancy__item-label">{{ __('translate.Name') }} * </label>
                                                    <input class="crancy__item-input" type="text" name="owner_name" id="owner_name" value="{{ old('owner_name') }}">
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <div class="crancy__item-form--group mg-top-form-20">
                                                    <label class="crancy__item-label">{{ __('translate.Email') }} * </label>
                                                    <input class="crancy__item-input" type="email" name="owner_email" id="owner_email" value="{{ old('owner_email') }}">
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <div class="crancy__item-form--group mg-top-form-20">
                                                    <label class="crancy__item-label">{{ __('translate.Phone') }} * </label>
                                                    <input class="crancy__item-input" type="text" name="owner_phone" id="owner_phone" value="{{ old('owner_phone') }}">
                                                </div>
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
    <!-- End Owner Info -->

    <!-- Account Info -->
    <section class="crancy-adashboard crancy-show">
        <div class="container container__bscreen">
            <div class="row">
                <div class="col-12">
                    <div class="crancy-body">
                        <!-- Dashboard Inner -->
                        <div class="crancy-dsinner">
                            <div class="row">
                                <div class="col-12">
                                    <!-- Product Card -->
                                    <div class="crancy-product-card">
                                        <div class="create_new_btn_inline_box">
                                            <h4 class="crancy-product-card__title">{{ __('translate.Account Information') }}</h4>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="crancy__item-form--group mg-top-form-20">
                                                    <label class="crancy__item-label">{{ __('translate.Name') }} * </label>
                                                    <input class="crancy__item-input" type="text" name="name" id="name" value="{{ old('name') }}">
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <div class="crancy__item-form--group mg-top-form-20">
                                                    <label class="crancy__item-label">{{ __('translate.Email') }} * </label>
                                                    <input class="crancy__item-input" type="email" name="email" id="email" value="{{ old('email') }}">
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <div class="crancy__item-form--group mg-top-form-20">
                                                    <label class="crancy__item-label">{{ __('translate.Password') }} * </label>
                                                    <input class="crancy__item-input" type="password" name="password" id="password" value="{{ old('password') }}">
                                                </div>
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
    <!-- End Account Info -->


    <!-- Others information -->
    <section class="crancy-adashboard crancy-show">
        <div class="container container__bscreen">
            <div class="row">
                <div class="col-12">
                    <div class="crancy-body">
                        <!-- Dashboard Inner -->
                        <div class="crancy-dsinner">
                            <div class="row">
                                <div class="col-12">
                                    <!-- Product Card -->
                                    <div class="crancy-product-card">
                                        <div class="create_new_btn_inline_box">
                                            <h4 class="crancy-product-card__title">{{ __('translate.Others Information') }}</h4>
                                        </div>
                                        <div class="row">

                                            <div class="col-md-6">
                                                <div class="crancy__item-form--group mg-top-form-20">
                                                    <label class="crancy__item-label">{{ __('translate.Opening Hour') }} * </label>
                                                    <input class="crancy__item-input clockpicker" type="text" name="opening_hour" id="opening_hour" value="{{ old('opening_hour') }}" data-align="top" data-autoclose="true" autocomplete="off">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="crancy__item-form--group mg-top-form-20">
                                                    <label class="crancy__item-label">{{ __('translate.Closing Hour') }} * </label>
                                                    <input class="crancy__item-input clockpicker" type="text" name="closing_hour" id="closing_hour" value="{{ old('closing_hour') }}" data-align="top" data-autoclose="true" autocomplete="off">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="crancy__item-form--group mg-top-form-20">
                                                    <label class="crancy__item-label">{{ __('translate.Minimum food processing time(minute)') }} * </label>
                                                    <input class="crancy__item-input" type="number" name="min_processing_time" id="min_processing_time" value="{{ old('min_processing_time') }}">
                                                </div>
                                            </div>



                                            <div class="col-md-6">
                                                <div class="crancy__item-form--group mg-top-form-20">
                                                    <label class="crancy__item-label">{{ __('translate.Maximum food processing time(minute)') }} * </label>
                                                    <input class="crancy__item-input" type="number" name="max_processing_time" id="max_processing_time" value="{{ old('max_processing_time') }}">
                                                </div>
                                            </div>


                                            <div class="col-md-6">
                                                <div class="crancy__item-form--group mg-top-form-20">
                                                    <label class="crancy__item-label">{{ __('translate.Time slot separated(minute)') }} * </label>
                                                    <input class="crancy__item-input" type="number" name="time_slot_separate" id="time_slot_separate" value="{{ old('time_slot_separate') }}">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="crancy__item-form--group mg-top-form-20">
                                                    <label class="crancy__item-label">{{ __('translate.Tags') }} </label>
                                                    <input class="crancy__item-input tags" type="text" name="tags" id="tags" value="{{ old('tags') }}">
                                                </div>
                                            </div>



                                            <div class="col-md-2">
                                                <div class="crancy__item-form--group mg-top-form-20">
                                                    <label class="crancy__item-label">{{__('translate.Make Featured')}} </label>
                                                    <div class="crancy-ptabs__notify-switch  crancy-ptabs__notify-switch--two">
                                                        <label class="crancy__item-switch">
                                                        <input name="is_featured" type="checkbox" >
                                                        <span class="crancy__item-switch--slide crancy__item-switch--round"></span>
                                                        </label>
                                                    </div>
                                                </div>

                                            </div>

                                            <div class="col-md-2">

                                                <div class="crancy__item-form--group mg-top-form-20">
                                                    <label class="crancy__item-label">{{__('translate.Pickup Order')}} </label>
                                                    <div class="crancy-ptabs__notify-switch  crancy-ptabs__notify-switch--two">
                                                        <label class="crancy__item-switch">
                                                        <input name="is_pickup_order" type="checkbox" >
                                                        <span class="crancy__item-switch--slide crancy__item-switch--round"></span>
                                                        </label>
                                                    </div>
                                                </div>

                                            </div>

                                            <div class="col-md-2">

                                                <div class="crancy__item-form--group mg-top-form-20">
                                                    <label class="crancy__item-label">{{__('translate.Delivery Order')}} </label>
                                                    <div class="crancy-ptabs__notify-switch  crancy-ptabs__notify-switch--two">
                                                        <label class="crancy__item-switch">
                                                        <input name="is_delivery_order" type="checkbox" >
                                                        <span class="crancy__item-switch--slide crancy__item-switch--round"></span>
                                                        </label>
                                                    </div>
                                                </div>

                                            </div>

                                        </div>

                                    </div>
                                    <!-- End Product Card -->
                                </div>
                            </div>

                            <button class="crancy-btn mg-top-25 reset_btn user_delete_btn" type="button" >{{ __('translate.Cancle') }}</button>
                            <button class="crancy-btn mg-top-25" type="submit">{{ __('translate.Save Data') }}</button>
                        </div>
                        <!-- End Dashboard Inner -->
                    </div>
                </div>

            </div>
        </div>
    </section>
    <!-- End Others information -->



    </form>



@endsection


@push('style_section')

<link rel="stylesheet" href="{{ asset('global/select2/select2.min.css') }}">

<link rel="stylesheet" href="{{ asset('global/tagify/tagify.css') }}">

<link rel="stylesheet" href="{{ asset('global/clockpicker/bootstrap-clockpicker.css') }}">

    <style>
        #google_map_area {
            height: 450px;
            width: 100%;
        }

        .tox .tox-promotion,
        .tox-statusbar__branding{
            display: none !important;
        }

        #map {
            width: 100%;
            height: 400px;
        }

        .mapControls {
            margin-top: 10px;
            border: 1px solid transparent;
            border-radius: 2px 0 0 2px;
            box-sizing: border-box;
            -moz-box-sizing: border-box;
            height: 32px;
            outline: none;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
        }

        #searchMapInput {
            background-color: #fff;
            font-family: Roboto;
            font-size: 15px;
            font-weight: 300;
            margin-left: 12px;
            padding: 0 11px 0 13px;
            text-overflow: ellipsis;
            width: 50%;
        }

        #searchMapInput:focus {
            border-color: #4d90fe;
        }


    </style>


@endpush

@push('js_section')


    <script src="{{ asset('global/select2/select2.min.js') }}"></script>

    <script src="{{ asset('global/tagify/tagify.js') }}"></script>

    <script src="{{ asset('global/clockpicker/bootstrap-clockpicker.js') }}"></script>

    <script>
        (function($) {
            "use strict"
            $(document).ready(function () {
                $("#title").on("keyup",function(e){
                    let inputValue = $(this).val();
                    let slug = inputValue.toLowerCase().replace(/[^\w ]+/g,'').replace(/ +/g,'-');
                    $("#slug").val(slug);
                })

                $('.select2').select2();

                $('.tags').tagify();

                $('.clockpicker').clockpicker();

                $('.reset_btn').on('click', function(){
                    location.reload();
                })

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

        function previewCoverImage(event) {
            var reader = new FileReader();
            reader.onload = function(){
                var output = document.getElementById('view_cover_img');
                output.src = reader.result;
            }

            reader.readAsDataURL(event.target.files[0]);
        };

    </script>


    <script>
        "use strict";

        document.addEventListener("DOMContentLoaded", function() {
            let my_location_lat = 0;
            let my_location_long = 0;

            function getLocation() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(showPosition, showError);
                } else {
                    alert("{{ __('translate.Geolocation is not supported by this browser.') }}");
                }
            }

            function showError(error) {
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        alert("{{ __('translate.User denied the request for Geolocation.') }}");
                        break;
                    case error.POSITION_UNAVAILABLE:
                        alert("{{ __('translate.Location information is unavailable.') }}");
                        break;
                    case error.TIMEOUT:
                        alert("{{ __('translate.The request to get user location timed out.') }}");
                        break;
                    default:
                        alert("{{ __('translate.An unknown error occurred.') }}");
                        break;
                }
            }

            function showPosition(position) {
                my_location_lat = position.coords.latitude;
                my_location_long = position.coords.longitude;
                initMap();
            }


            window.initMap = function () {
                var map = new google.maps.Map(document.getElementById('google_map_area'), {
                    center: { lat: my_location_lat, lng: my_location_long },
                    zoom: 13
                });

                var input = document.getElementById('searchMapInput');
                map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

                var autocomplete = new google.maps.places.Autocomplete(input);
                autocomplete.bindTo('bounds', map);

                var infowindow = new google.maps.InfoWindow();
                var marker = new google.maps.Marker({
                    map: map,
                    anchorPoint: new google.maps.Point(0, -29),
                    draggable: true
                });

                // Listener for autocomplete
                autocomplete.addListener('place_changed', function () {
                    infowindow.close();
                    marker.setVisible(false);
                    var place = autocomplete.getPlace();

                    if (!place.geometry) {
                        alert("{{ __('translate.No details available for input.') }}");
                        return;
                    }

                    if (place.geometry.viewport) {
                        map.fitBounds(place.geometry.viewport);
                    } else {
                        map.setCenter(place.geometry.location);
                        map.setZoom(17);
                    }

                    marker.setPosition(place.geometry.location);
                    marker.setVisible(true);

                    $("#plain_address").val(place.formatted_address);
                    $("#latitude").val(place.geometry.location.lat());
                    $("#longitude").val(place.geometry.location.lng());
                });

                // Listener for map clicks
                map.addListener('click', function (event) {
                    var clickedLocation = event.latLng;

                    marker.setPosition(clickedLocation);
                    marker.setVisible(true);

                    $("#latitude").val(clickedLocation.lat());
                    $("#longitude").val(clickedLocation.lng());

                    reverseGeocode(clickedLocation);
                });
            }

            function reverseGeocode(location) {
                var geocoder = new google.maps.Geocoder();
                geocoder.geocode({ location: location }, function (results, status) {
                    if (status === "OK" && results[0]) {
                        $("#plain_address").val(results[0].formatted_address);
                    }
                });
            }

            getLocation();
        });

    </script>

    <script src="https://maps.googleapis.com/maps/api/js?key={{ env('MAP_API') }}&libraries=places&callback=initMap" async defer></script>

@endpush



