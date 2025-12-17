@extends('restaurant.layouts.master')
@section('title')
    <title>{{ __('translate.Restaurant || New Withdraw') }}</title>
@endsection
@section('body-header')
    <h3 class="crancy-header__title m-0">{{ __('translate.My Withdraw') }}</h3>
    <p class="crancy-header__text">{{ __('translate.Dashboard') }} >> {{ __('translate.New Withdraw') }}</p>
@endsection
@section('style_section')
    <style>
        .gig-info-header, .profile-info-header {
            padding: 20px 30px;
            background-color: rgba(34, 190, 13, 0.2);
            border-radius: 8px 8px 0 0;
        }
    </style>
@endsection
@section('body-content')
    <section class="crancy-adashboard crancy-show">
        <div class="container container__bscreen">
            <!-- Content -->
            <div>
                <div class="row justify-content-center mt-5">
                    <div class="col-xl-12">
                        <div class="crancy-product-card">
                            <div class="create_new_btn_inline_box mb-3">
                                <h4 class="crancy-product-card__title">{{ __('translate.Create Withdraw') }}</h4>

                                <a href="{{ route('restaurant.my-withdraw.index') }}" class="crancy-btn "><i class="fa fa-list"></i> {{ __('translate.Withdraw List') }}</a>
                            </div>
                            <form method="post" action="{{ route('restaurant.my-withdraw.store') }}"
                                  enctype="multipart/form-data">
                                @csrf
                                <div class="d-flex flex-column gap-4">
                                    <!-- Profile Info -->
                                    <div class="profile-info-card">
                                        <div class="profile-info-body">
                                            <div class="row g-4">
                                                <div class="col-12">
                                                    <div class="form-container">
                                                        <label for="gender"
                                                               class="form-label">{{ __('translate.Withdraw Method') }} <span
                                                                class="text-lime-300">*</span>
                                                        </label>
                                                        <select id="withdraw_method" autocomplete="off"
                                                                class="form-select shadow-none" name="method_id">
                                                            <option value="">{{ __('translate.Select') }}</option>
                                                            @foreach ($methods as $method)
                                                                <option
                                                                    value="{{ $method->id }}">{{ $method->method_name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>

                                                @foreach ($methods as $method)
                                                    <div class="col-12 d-none method_box"
                                                         id="method_id_{{ $method->id }}">
                                                        <div class="form-container">
                                                            <div class="card">
                                                                <div class="card-body" id="method_des">
                                                                    <div class="alert alert-primary withdraw-card"
                                                                         role="alert">
                                                                        <h5 class="mb-2">{{ __('translate.Withdraw Limit') }} :
                                                                            {{ currency($method->min_amount) }}
                                                                            - {{ currency($method->max_amount) }}
                                                                        </h5>
                                                                        <h5 class="mb-2">{{ __('translate.Withdraw charge') }}
                                                                            : {{ $method->withdraw_charge }}%</h5>
                                                                        {!! clean(nl2br($method->description)) !!}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach


                                                <div class="col-12">
                                                    <div class="form-container">
                                                        <label for="fname" class="form-label"
                                                        >{{ __('translate.Amount') }}</label
                                                        >
                                                        <input
                                                            type="text"
                                                            class="form-control shadow-none"
                                                            placeholder="{{ __('translate.Amount') }}"
                                                            name="amount"
                                                            value=""
                                                        />
                                                    </div>
                                                </div>


                                                <div class="col-12">
                                                    <div class="form-container">
                                                        <label for="fname" class="form-label"
                                                        >{{ __('translate.Bank/Account Information') }}</label
                                                        >

                                                        <textarea rows="5" class="crancy__item-input crancy__item-textarea seo_description_box"
                                                                  name="description" id="" cols="30" rows="10"
                                                                  placeholder="{{ __('translate.Bank/Account Information') }}"></textarea>

                                                    </div>
                                                </div>


                                            </div>
                                        </div>
                                    </div>

                                    <!-- Submit Btn -->
                                    <div class="d-flex align-items-center gap-3">
                                        <button type="submit" class="crancy-btn mg-top-25">
                                            {{ __('translate.Send Withdraw Request') }}
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="14"
                                                height="10"
                                                viewBox="0 0 14 10"
                                                fill="none"
                                            >
                                                <path
                                                    d="M9 9L13 5M13 5L9 1M13 5L1 5"
                                                    stroke="white"
                                                    stroke-width="1.5"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                />
                                            </svg>
                                        </button>
                                        <a
                                            href=""
                                            class="crancy-btn mg-top-25"
                                        >{{ __('translate.Cancel') }}</a
                                        >
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>>

@endsection



@push('js_section')

    <script>
        (function ($) {
            "use strict"
            $(document).ready(function () {
                $("#withdraw_method").on("change", function () {
                    $(".method_box").addClass('d-none');
                    $(`#method_id_${$(this).val()}`).removeClass('d-none');

                })
            });
        })(jQuery);

    </script>
@endpush
