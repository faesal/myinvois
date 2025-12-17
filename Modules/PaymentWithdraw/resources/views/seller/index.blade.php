@extends('restaurant.layouts.master')
@section('title')
    <title>{{ __('translate.Restaurant || My Earnings') }}</title>
@endsection
@section('body-header')
    <h3 class="crancy-header__title m-0">{{ __('translate.Dashboard') }}</h3>
    <p class="crancy-header__text">{{ __('translate.Dashboard') }} >> {{ __('translate.My Earnings') }}</p>
@endsection
@section('body-content')

    <section class="crancy-adashboard crancy-show">
        <div class="container container__bscreen">
            <div class="row">
                                <div class="col-lg-4 col-12 mg-top-30">
                                    <!-- Progress Card -->
                                    <div class="crancy-ecom-card crancy-ecom-card__v2">
                                        <div class="flex-main">

                                        <div class="flex-1">
                                            <div class="crancy-ecom-card__heading">
                                            <div class="crancy-ecom-card__icon">
                                                <h4 class="crancy-ecom-card__title">{{ __('translate.Total Earnings') }} </h4>
                                            </div>

                                            </div>
                                            <div class="crancy-ecom-card__content">
                                            <div class="crancy-ecom-card__camount">
                                                <div class="crancy-ecom-card__camount__inside">
                                                    <h3 class="crancy-ecom-card__amount">{{ currency($total_income) }}</h3>

                                                </div>

                                            </div>

                                            </div>
                                        </div>
                                        <span>
                                            <div class="d-inline-flex justify-content-center align-items-center bg-success-white rounded-circle grid-icon-size text-primary">
                                                <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M16.3333 14.875C16.3333 13.7475 15.2886 12.8334 14 12.8334C12.7113 12.8334 11.6666 13.7475 11.6666 14.875C11.6666 16.0026 12.7113 16.9167 14 16.9167C15.2886 16.9167 16.3333 17.8308 16.3333 18.9584C16.3333 20.086 15.2886 21 14 21C12.7113 21 11.6666 20.086 11.6666 18.9584" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                                    <path d="M14 11.0834V12.8334" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M14 21V22.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M6.79916 12.2693C7.61585 9.81921 9.90868 8.16663 12.4913 8.16663H15.5088C18.0914 8.16663 20.3842 9.81921 21.2009 12.2693L23.0343 17.7693C24.3293 21.6544 21.4375 25.6666 17.3422 25.6666H10.6579C6.56259 25.6666 3.67077 21.6544 4.96583 17.7693L6.79916 12.2693Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                                    <path d="M16.4336 8.16671L11.5665 8.16671L9.93191 6.29182C8.32956 4.45394 10.1989 1.70685 12.5383 2.46153L13.6207 2.81071C13.8671 2.8902 14.133 2.8902 14.3794 2.81071L15.4618 2.46153C17.8012 1.70685 19.6705 4.45394 18.0682 6.29183L16.4336 8.16671Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                                    </svg>


                                            </div>
                                        </span>
                                        </div>
                                    </div>
                                    <!-- End Progress Card -->
                                </div>

                                <div class="col-lg-4 col-12 mg-top-30">
                                    <!-- Progress Card -->
                                    <div class="crancy-ecom-card crancy-ecom-card__v2">
                                        <div class="flex-main">


                                            <div class="flex-1">
                                            <div class="crancy-ecom-card__heading">
                                             <div class="crancy-ecom-card__icon">
                                                <h4 class="crancy-ecom-card__title">{{ __('translate.Commission Deducted') }} </h4>
                                            </div>

                                             </div>
                                            <div class="crancy-ecom-card__content">
                                                <div class="crancy-ecom-card__camount">
                                                    <div class="crancy-ecom-card__camount__inside">
                                                        <h3 class="crancy-ecom-card__amount">{{ currency($total_commission) }}</h3>

                                                    </div>

                                                </div>

                                            </div>
                                        </div>
                                        <span>
                                            <div class="d-inline-flex justify-content-center align-items-center bg-success-white rounded-circle grid-icon-size text-primary">
                                                <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M6.79916 12.2693C7.61585 9.81921 9.90868 8.16663 12.4913 8.16663H15.5088C18.0914 8.16663 20.3842 9.81921 21.2009 12.2693L23.0343 17.7693C24.3293 21.6544 21.4375 25.6666 17.3422 25.6666H10.6579C6.56259 25.6666 3.67077 21.6544 4.96583 17.7693L6.79916 12.2693Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                                    <path d="M16.4336 8.16671L11.5665 8.16671L9.93191 6.29182C8.32956 4.45394 10.1989 1.70685 12.5383 2.46153L13.6207 2.81071C13.8671 2.8902 14.133 2.8902 14.3794 2.81071L15.4618 2.46153C17.8012 1.70685 19.6705 4.45394 18.0682 6.29183L16.4336 8.16671Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                                    <path d="M10.5 19.8334C13.1301 21.3672 14.6741 21.4019 17.5 19.8334" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                                    <path d="M15.1667 13.9418C15.1667 13.2652 14.5399 12.7168 13.7667 12.7168C12.9935 12.7168 12.3667 13.2652 12.3667 13.9418C12.3667 14.6183 12.9935 15.1668 13.7667 15.1668C14.5399 15.1668 15.1667 15.7152 15.1667 16.3918C15.1667 17.0683 14.5399 17.6168 13.7667 17.6168C12.9935 17.6168 12.3667 17.0683 12.3667 16.3918" stroke="currentColor" stroke-linecap="round"/>
                                                    <path d="M13.7667 11.6666V12.7166" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M13.7667 17.6165V18.6665" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>



                                            </div>
                                        </span>
                                        </div>

                                    </div>
                                    <!-- End Progress Card -->
                                </div>

                                <div class="col-lg-4 col-12 mg-top-30">
                                    <!-- Progress Card -->
                                    <div class="crancy-ecom-card crancy-ecom-card__v2">
                                        <div class="flex-main">

                                            <div class="flex-1">
                                            <div class="crancy-ecom-card__heading">
                                            <div class="crancy-ecom-card__icon">
                                                <h4 class="crancy-ecom-card__title">{{ __('translate.Net Earnings') }} </h4>
                                            </div>

                                        </div>
                                        <div class="crancy-ecom-card__content">
                                            <div class="crancy-ecom-card__camount">
                                                <div class="crancy-ecom-card__camount__inside">
                                                    <h3 class="crancy-ecom-card__amount">{{ currency($net_income) }}</h3>

                                                </div>

                                            </div>

                                        </div>
                                            </div>
                                            <span>
                                                <div class="d-inline-flex justify-content-center align-items-center bg-success-white rounded-circle grid-icon-size text-primary">
                                                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M16.9166 16.9167C16.9166 15.3059 15.6107 14 13.9999 14C12.3891 14 11.0833 15.3059 11.0833 16.9167C11.0833 18.5275 12.3891 19.8334 13.9999 19.8334C15.6107 19.8334 16.9166 18.5275 16.9166 16.9167Z" stroke="currentColor" stroke-width="1.5"/>
                                                        <path d="M6.79904 12.2693C7.61572 9.81921 9.90856 8.16663 12.4911 8.16663H15.5087C18.0913 8.16663 20.3841 9.81921 21.2008 12.2693L23.0341 17.7693C24.3292 21.6544 21.4374 25.6666 17.342 25.6666H10.6578C6.56247 25.6666 3.67064 21.6544 4.96571 17.7693L6.79904 12.2693Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                                        <path d="M16.4335 8.16671L11.5664 8.16671L9.93178 6.29183C8.32944 4.45394 10.1987 1.70685 12.5382 2.46153L13.6206 2.81071C13.867 2.8902 14.1328 2.8902 14.3793 2.81071L15.4617 2.46153C17.8011 1.70685 19.6704 4.45394 18.0681 6.29183L16.4335 8.16671Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                                        </svg>




                                                </div>
                                            </span>
                                        </div>
                                    </div>
                                    <!-- End Progress Card -->
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-4 col-12 mg-top-30">
                                    <!-- Progress Card -->
                                    <div class="crancy-ecom-card crancy-ecom-card__v2">
                                        <div class="flex-main">

                                            <div class="flex-1">
                                            <div class="crancy-ecom-card__heading">
                                            <div class="crancy-ecom-card__icon">
                                                <h4 class="crancy-ecom-card__title">{{ __('translate.Available Balance') }} </h4>
                                            </div>

                                        </div>
                                        <div class="crancy-ecom-card__content">
                                            <div class="crancy-ecom-card__camount">
                                                <div class="crancy-ecom-card__camount__inside">
                                                    <h3 class="crancy-ecom-card__amount"> {{ currency($current_balance) }}</h3>

                                                </div>

                                            </div>

                                        </div>
                                            </div>
                                            <span>
                                                <div class="d-inline-flex justify-content-center align-items-center bg-success-white rounded-circle grid-icon-size text-primary">
                                                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M23.3332 21H16.3332M23.3332 21C24.6218 21 25.6665 22.0446 25.6665 23.3333C25.6665 24.622 24.6218 25.6666 23.3332 25.6666H16.3332C15.0445 25.6666 13.9998 24.622 13.9998 23.3333C13.9998 22.0446 15.0445 21 16.3332 21M23.3332 21C24.6218 21 25.6665 19.9553 25.6665 18.6666C25.6665 17.378 24.6218 16.3333 23.3332 16.3333H22.5554M16.3332 21C15.0445 21 13.9998 19.9553 13.9998 18.6666C13.9998 17.378 15.0445 16.3333 16.3332 16.3333H22.5554M15.9545 25.6666H12.0452C7.26726 25.6666 3.89347 20.9857 5.40438 16.453L6.57105 12.953C7.52384 10.0946 10.1988 8.16663 13.2118 8.16663H14.7879C17.8009 8.16663 20.4758 10.0946 21.4286 12.953L22.5554 16.3333" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                                        <path d="M16.4336 8.16671L11.5665 8.16671L9.93191 6.29183C8.32956 4.45394 10.1989 1.70685 12.5383 2.46153L13.6207 2.81071C13.8671 2.8902 14.133 2.8902 14.3794 2.81071L15.4618 2.46153C17.8012 1.70685 19.6705 4.45394 18.0682 6.29183L16.4336 8.16671Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                                        </svg>





                                                </div>
                                            </span>
                                        </div>
                                    </div>
                                    <!-- End Progress Card -->
                                </div>

                                <div class="col-lg-4 col-12 mg-top-30">
                                    <!-- Progress Card -->
                                    <div class="crancy-ecom-card crancy-ecom-card__v2">
                                        <div class="flex-main">

                                        <div class="flex-1">
                                            <div class="crancy-ecom-card__heading">
                                            <div class="crancy-ecom-card__icon">
                                                <h4 class="crancy-ecom-card__title">{{ __('translate.Total Withdraw') }} </h4>
                                            </div>

                                            </div>
                                            <div class="crancy-ecom-card__content">
                                            <div class="crancy-ecom-card__camount">
                                                <div class="crancy-ecom-card__camount__inside">
                                                    <h3 class="crancy-ecom-card__amount">{{ currency($total_withdraw_amount) }}</h3>


                                                </div>

                                            </div>

                                            </div>
                                        </div>
                                        <span>
                                            <div class="d-inline-flex justify-content-center align-items-center bg-success-white rounded-circle grid-icon-size text-primary">
                                                <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M2.33337 9.33337H4.66671L12.1815 12.6211C13.188 13.0615 13.6509 14.2314 13.2181 15.2412L13.0379 15.6618C12.6028 16.677 11.427 17.1473 10.4118 16.7122L8.16671 15.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M2.66878 19.1626C2.2983 18.9773 1.8478 19.1275 1.66255 19.498C1.47731 19.8684 1.62748 20.319 1.99796 20.5042L2.66878 19.1626ZM13.1688 15.6626L12.498 15.3271L11.8271 16.6688L12.498 17.0042L13.1688 15.6626ZM18.9164 18.6667V19.4167H21.3334V18.6667V17.9167H18.9164V18.6667ZM23.3334 20.6667H22.5834V21.3334H23.3334H24.0834V20.6667H23.3334ZM21.3334 23.3334V22.5834H11.2219V23.3334V24.0834H21.3334V23.3334ZM7.64421 22.4888L7.97962 21.818L2.66878 19.1626L2.33337 19.8334L1.99796 20.5042L7.3088 23.1596L7.64421 22.4888ZM16.2332 18.0333L16.5686 17.3625L13.1688 15.6626L12.8334 16.3334L12.498 17.0042L15.8978 18.7041L16.2332 18.0333ZM11.2219 23.3334V22.5834C10.0964 22.5834 8.98632 22.3213 7.97962 21.818L7.64421 22.4888L7.3088 23.1596C8.52378 23.7671 9.86352 24.0834 11.2219 24.0834V23.3334ZM23.3334 21.3334H22.5834C22.5834 22.0237 22.0237 22.5834 21.3334 22.5834V23.3334V24.0834C22.8522 24.0834 24.0834 22.8522 24.0834 21.3334H23.3334ZM21.3334 18.6667V19.4167C22.0237 19.4167 22.5834 19.9764 22.5834 20.6667H23.3334H24.0834C24.0834 19.1479 22.8522 17.9167 21.3334 17.9167V18.6667ZM18.9164 18.6667V17.9167C18.1014 17.9167 17.2976 17.7269 16.5686 17.3625L16.2332 18.0333L15.8978 18.7041C16.835 19.1727 17.8685 19.4167 18.9164 19.4167V18.6667Z" fill="currentColor"/>
                                                    <path d="M21.875 9.02595C21.875 8.4234 21.2874 7.93494 20.5625 7.93494C19.8376 7.93494 19.25 8.4234 19.25 9.02595C19.25 9.6285 19.8376 10.117 20.5625 10.117C21.2874 10.117 21.875 10.6054 21.875 11.208C21.875 11.8105 21.2874 12.299 20.5625 12.299C19.8376 12.299 19.25 11.8105 19.25 11.208" stroke="currentColor" stroke-linecap="round"/>
                                                    <path d="M20.5625 7V7.93516" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M20.5625 12.2994V13.2346" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M16.3025 8.38189C16.8848 6.51864 18.6104 5.25 20.5625 5.25V5.25C22.5146 5.25 24.2402 6.51864 24.8225 8.38189L25.1676 9.48622C26.1384 12.593 23.8174 15.75 20.5625 15.75V15.75C17.3076 15.75 14.9866 12.593 15.9574 9.48622L16.3025 8.38189Z" stroke="currentColor" stroke-width="1.25" stroke-linejoin="round"/>
                                                    <path d="M21.9314 5.03125L19.1936 5.03125L18.2742 3.97663C17.3729 2.94282 18.4243 1.39758 19.7403 1.82209L20.3491 2.0185C20.4877 2.06322 20.6373 2.06322 20.7759 2.0185L21.3847 1.82209C22.7007 1.39758 23.7521 2.94282 22.8508 3.97663L21.9314 5.03125Z" stroke="currentColor" stroke-width="1.25" stroke-linejoin="round"/>
                                                    </svg>


                                            </div>
                                        </span>
                                        </div>
                                    </div>
                                    <!-- End Progress Card -->
                                </div>

                                <div class="col-lg-4 col-12 mg-top-30">
                                    <!-- Progress Card -->
                                    <div class="crancy-ecom-card crancy-ecom-card__v2">
                                        <div class="flex-main">


                                            <div class="flex-1">
                                            <div class="crancy-ecom-card__heading">
                                             <div class="crancy-ecom-card__icon">
                                                <h4 class="crancy-ecom-card__title">{{ __('translate.Pending Withdraw') }}</h4>
                                            </div>

                                             </div>
                                            <div class="crancy-ecom-card__content">
                                                <div class="crancy-ecom-card__camount">
                                                    <div class="crancy-ecom-card__camount__inside">
                                                        <h3 class="crancy-ecom-card__amount">{{ currency($pending_withdraw) }}</h3>

                                                    </div>

                                                </div>

                                            </div>
                                        </div>
                                        <span>
                                            <div class="d-inline-flex justify-content-center align-items-center bg-success-white rounded-circle grid-icon-size text-primary">
                                                <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M15.9545 25.6666H12.0452C7.26726 25.6666 3.89347 20.9857 5.40438 16.453L6.57105 12.953C7.52384 10.0946 10.1988 8.16663 13.2118 8.16663H14.7879C17.8009 8.16663 20.4758 10.0946 21.4286 12.953L22.5554 16.3333" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M16.4335 8.16671L11.5664 8.16671L9.93178 6.29182C8.32944 4.45394 10.1987 1.70685 12.5382 2.46153L13.6206 2.81071C13.867 2.8902 14.1328 2.8902 14.3793 2.81071L15.4617 2.46153C17.8011 1.70685 19.6704 4.45394 18.0681 6.29183L16.4335 8.16671Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                                    <rect x="23.0742" y="19.7036" width="2.20376" height="6.61129" rx="1.10188" transform="rotate(90 23.0742 19.7036)" stroke="currentColor" stroke-width="1.25"/>
                                                    <rect x="20.8704" y="17.5" width="2.20376" height="6.61129" rx="1.10188" transform="rotate(90 20.8704 17.5)" stroke="currentColor" stroke-width="1.25"/>
                                                    <rect x="21.4214" y="21.9072" width="2.20376" height="6.61129" rx="1.10188" transform="rotate(90 21.4214 21.9072)" stroke="currentColor" stroke-width="1.25"/>
                                                    </svg>

                                            </div>
                                        </span>
                                        </div>

                                    </div>
                                    <!-- End Progress Card -->
                                </div>

                            </div>

            <!-- Content -->
            @if($withdraw_list->count() > 0)
            <!-- crancy Table -->

            <div class="crancy-table crancy-table--v3 mg-top-30">

                <div class="crancy-customer-filter">
                    <div class="crancy-customer-filter__single crancy-customer-filter__single--csearch d-flex items-center justify-between create_new_btn_box">
                        <div class="crancy-header__form crancy-header__form--customer create_new_btn_inline_box">
                            <h4 class="crancy-product-card__title">{{ __('translate.Withdraw List') }}</h4>
                            <a href="{{ route('restaurant.my-withdraw.create') }}" class="crancy-btn "><span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                                    <path d="M8 1V15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                                    <path d="M1 8H15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                                </svg>
                                </span> {{ __('translate.New Withdraw') }}</a>
                        </div>
                    </div>
                </div>
                <!-- crancy Table -->
                <div id="crancy-table__main_wrapper" class="dataTables_wrapper dt-bootstrap5 no-footer">

                    <table class="crancy-table__main crancy-table__main-v3 dataTable no-footer" id="dataTable">
                        <!-- crancy Table Head -->
                        <thead class="crancy-table__head">
                        <tr>

                            <th class="crancy-table__column-2 crancy-table__h2 sorting" >
                                {{ __('translate.Serial') }}
                            </th>

                            <th class="crancy-table__column-2 crancy-table__h2 sorting" >
                                {{ __('translate.Restaurant Name') }}
                            </th>

                            <th class="crancy-table__column-2 crancy-table__h2 sorting" >
                                {{ __('translate.Total Amount') }}
                            </th>

                            <th class="crancy-table__column-2 crancy-table__h2 sorting" >
                                {{ __('translate.Withdraw Amount') }}
                            </th>

                            <th class="crancy-table__column-2 crancy-table__h2 sorting" >
                                {{ __('translate.Withdraw Charge') }}
                            </th>

                            <th class="crancy-table__column-2 crancy-table__h2 sorting" >
                                {{ __('translate.Status') }}
                            </th>

                            <th class="crancy-table__column-3 crancy-table__h3 sorting">
                                {{ __('translate.Action') }}
                            </th>

                        </tr>
                        </thead>
                        <!-- crancy Table Body -->
                        <tbody class="crancy-table__body">
                        @foreach ($withdraw_list as $index => $withdraw)

                            <tr class="odd">

                                <td class="crancy-table__column-2 crancy-table__data-2">
                                    <h4 class="crancy-table__product-title">{{ ++$index }}</h4>
                                </td>

                                <td class="crancy-table__column-2 crancy-table__data-2">
                                    <h4 class="crancy-table__product-title">
                                        <a href="#">{{ $withdraw?->restaurant?->restaurant_name }}</a>
                                    </h4>
                                </td>


                                <td class="crancy-table__column-2 crancy-table__data-2">
                                    <h4 class="crancy-table__product-title">{{ currency($withdraw->total_amount) }}</h4>
                                </td>

                                <td class="crancy-table__column-2 crancy-table__data-2">
                                    <h4 class="crancy-table__product-title">{{ currency($withdraw->withdraw_amount) }}</h4>
                                </td>

                                <td class="crancy-table__column-2 crancy-table__data-2">
                                    <h4 class="crancy-table__product-title">{{ currency($withdraw->charge_amount) }}</h4>
                                </td>


                                <td class="crancy-table__column-2 crancy-table__data-2">
                                    @if ($withdraw->status == 'approved')
                                        <span class="badge bg-success text-white">{{ __('translate.Approved') }}</span>
                                    @elseif ($withdraw->status == 'rejected')
                                        <span class="badge bg-danger text-white">{{ __('translate.Rejected') }}</span>
                                    @else
                                        <span class="badge bg-danger text-white">{{ __('translate.Pending') }}</span>
                                    @endif
                                </td>

                                <td class="crancy-table__column-2 crancy-table__data-2">
                                    <a data-bs-toggle="modal"
                                            data-bs-target="#withdrawShow{{ $withdraw->id }}" class="crancy-btn"><i class="fas fa-eye"></i> {{ __('translate.View') }}</a>
                                </td>
                            </tr>
                        @endforeach

                        </tbody>
                        <!-- End crancy Table Body -->
                    </table>
                </div>
                </div>
                <!-- End crancy Table -->
            @endif
        </div>
    </section>>



    @foreach ($withdraw_list as $index => $withdraw)
        <div class="modal fade" id="withdrawShow{{ $withdraw->id }}" tabindex="-1" aria-labelledby="exampleModalLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content ">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">{{ __('translate.Withdraw Details') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered table-striped">
                            <tbody>
                            <tr>
                                <td>{{ __('translate.Withdraw Method') }}</td>
                                <td>{{ $withdraw->withdraw_method_name }}</td>
                            </tr>
                            <tr>
                                <td> {{ __('translate.Total Amount') }}</td>
                                <td>{{ currency($withdraw->total_amount) }}</td>
                            </tr>

                            <tr>
                                <td> {{ __('translate.Withdraw Amount') }}</td>
                                <td>{{ currency($withdraw->withdraw_amount) }}</td>
                            </tr>

                            <tr>
                                <td> {{ __('translate.Charge Amount') }}</td>
                                <td>{{ currency($withdraw->charge_amount) }}</td>
                            </tr>

                            <tr>
                                <td> {{ __('translate.Status') }}</td>
                                <td>
                                    @if ($withdraw->status == 'approved')
                                        <span class="status-badge in-progress"> {{ __('translate.Approved') }} </span>
                                    @elseif ($withdraw->status == 'rejected')
                                        <span class="status-badge pending"> {{ __('translate.Rejected') }} </span>
                                    @else
                                        <span class="status-badge pending"> {{ __('translate.Pending') }} </span>
                                    @endif
                                </td>
                            </tr>

                            <tr>
                                <td> {{ __('translate.Withdraw Method') }}</td>
                                <td>{{ $withdraw->withdraw_method_name }}</td>
                            </tr>

                            <tr>
                                <td> {{ __('translate.Bank/Account Info') }}</td>
                                <td>{!! clean(nl2br(html_decode($withdraw->description))) !!}</td>
                            </tr>

                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    @endforeach

@endsection

