<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>MySyncTax</title>

    <link href="{{ asset('assets/icons/phosphor/styles.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/ltr/all.min.css') }}" id="stylesheet" rel="stylesheet" type="text/css">

    {{-- Essential Layout Scripts Only --}}
    <script src="{{ asset('assets/demo/demo_configurator.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>

    <style>
        .sidebar .collapse { visibility: visible !important; }
        .nav-item-submenu.nav-item-open > .nav-group-sub { display: block !important; }
        @media (max-width: 991.98px) {
            .sidebar { z-index: 9999 !important; }
            .navbar-toggler { cursor: pointer; position: relative; z-index: 10000; }
        }
        .table-responsive { display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    </style>
</head>

<body>
    <div class="navbar navbar-dark navbar-expand-lg navbar-static">
        <div class="container-fluid">
            <div class="d-flex d-lg-none me-2">
                <button type="button" class="navbar-toggler sidebar-mobile-main-toggle rounded-pill">
                    <i class="ph-list"></i>
                </button>
            </div>
            <div class="navbar-brand flex-1 flex-lg-0">
                <a href="{{ url('main') }}" class="d-inline-flex align-items-center text-white fw-bold fs-4 text-decoration-none">
                    MySynctax
                </a>
            </div>
            <ul class="nav flex-row justify-content-end order-1 order-lg-2">
                <li class="nav-item nav-item-dropdown-lg dropdown ms-lg-2">
                    <a href="#" class="navbar-nav-link align-items-center rounded-pill p-1" data-bs-toggle="dropdown">
                        <div class="status-indicator-container">
                            <img src="https://ui-avatars.com/api/?name=Victoria&background=0D8ABC&color=fff" class="w-32px h-32px rounded-pill" alt="Profile Icon">
                            <span class="status-indicator bg-success"></span>
                        </div>
                        <span class="d-none d-lg-inline-block mx-lg-2">Victoria</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a href="{{url('/user/profile')}}" class="dropdown-item"><i class="ph-user-circle me-2"></i> My profile</a>
                        <a href="{{url('/logout')}}" class="dropdown-item"><i class="ph-sign-out me-2"></i> Logout</a>
                    </div>
                </li>
            </ul>
        </div>
    </div>

    <div class="page-header page-header-light shadow-sm">
        <div class="page-header-content d-lg-flex">
            <div class="d-flex">
                <div class="breadcrumb py-2">
                    <a href="{{ url('main') }}" class="breadcrumb-item"><i class="ph-house"></i></a>
                    <a href="#" class="breadcrumb-item">MySyncTax</a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-content pt-0">
        <div class="sidebar sidebar-main sidebar-expand-lg align-self-start">
            <div class="sidebar-content">
                <div class="sidebar-section">
                    <div class="sidebar-section-body d-flex justify-content-center">
                        <h5 class="sidebar-resize-hide flex-grow-1 my-auto">Navigation</h5>
                        <div>
                            <button type="button" class="btn btn-light btn-icon btn-sm rounded-pill border-transparent sidebar-control sidebar-main-resize d-none d-lg-inline-flex">
                                <i class="ph-arrows-left-right"></i>
                            </button>
                            <button type="button" class="btn btn-light btn-icon btn-sm rounded-pill border-transparent sidebar-mobile-main-toggle d-lg-none">
                                <i class="ph-x"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="sidebar-section">
                    <ul class="nav nav-sidebar" data-nav-type="accordion">
                        <li class="nav-item-header pt-0">
                            <div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">Main</div>
                            <i class="ph-dots-three sidebar-resize-show"></i>
                        </li>

                        <li class="nav-item">
                            <a href="{{url('main')}}" class="nav-link {{ request()->is('main') ? 'active' : '' }}">
                                <i class="ph-gauge"></i>
                                <span>Dashboard<span class="d-block fw-normal text-body opacity-50">Analytic Report</span></span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{url('/customer/listing_customer')}}" class="nav-link">
                                <i class="ph-users"></i>
                                <span>Customer Listing</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('consolidate.import') }}" class="nav-link {{ request()->routeIs('consolidate.import') ? 'active' : '' }}">
                                <i class="ph-upload-simple"></i>
                                <span>Consolidate Import</span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('manage_customer.index') }}" class="nav-link {{ request()->routeIs('manage_customer*') ? 'active' : '' }}">
                                <i class="ph-address-book"></i>
                                <span>Manage Customer<span class="d-block fw-normal text-body opacity-50">View & Edit Customers</span></span>
                            </a>
                        </li>

                        <li class="nav-item nav-item-submenu {{ 
                            (request()->is('listing_submission') && request()->query('type') == 'self_bill') || 
                            (request()->is('invoice/create') && request()->query('type') == 'self_bill') ||
                            request()->is('self_bill/*')
                            ? 'nav-item-open' : '' 
                        }}">
                            <a href="#" class="nav-link">
                                <i class="ph-file-text"></i>
                                <span>Self Bill Invoice</span>
                            </a>

                            <ul class="nav-group-sub collapse {{ 
                                (request()->is('listing_submission') && request()->query('type') == 'self_bill') || 
                                (request()->is('invoice/create') && request()->query('type') == 'self_bill') ||
                                request()->is('self_bill/*')
                                ? 'show' : '' 
                            }}">
                                <li class="nav-item">
                                    <a href="{{ url('invoice/create?type=self_bill') }}" class="nav-link {{ (request()->is('invoice/create') && request()->query('type') == 'self_bill') ? 'active' : '' }}">
                                        Create New Self Bill Invoice
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ url('listing_submission?type=self_bill') }}" 
                                       class="nav-link {{ (request()->is('listing_submission') && request()->query('type') == 'self_bill') ? 'active' : '' }}">
                                       Listing Submission
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('self_bill_note.listing', ['note_type' => 'credit_note']) }}" class="nav-link {{ request()->is('*/credit_note/*') ? 'active' : '' }}">Listing Credit Note</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('self_bill_note.listing', ['note_type' => 'debit_note']) }}" class="nav-link {{ request()->is('*/debit_note/*') ? 'active' : '' }}">Listing Debit Note</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('self_bill_note.listing', ['note_type' => 'refund_note']) }}" class="nav-link {{ request()->is('*/refund_note/*') ? 'active' : '' }}">Listing Refund</a>
                                </li>
                            </ul>
                        </li>

                        <li class="nav-item nav-item-submenu {{ 
                            ((request()->is('listing_submission') || request()->is('invoice/create')) && request()->query('type') != 'self_bill') || 
                            request()->is('credit_note/listing') || 
                            request()->is('debit_note/listing') || 
                            request()->is('refund_note/listing') || 
                            request()->is('select_items') || 
                            request()->is('compare') 
                            ? 'nav-item-open' : '' 
                        }}">
                            <a href="#" class="nav-link">
                                <i class="ph-receipt"></i>
                                <span>Normal Invoice</span>
                            </a>

                            <ul class="nav-group-sub collapse {{ 
                                ((request()->is('listing_submission') || request()->is('invoice/create')) && request()->query('type') != 'self_bill') || 
                                request()->is('credit_note/listing') || 
                                request()->is('debit_note/listing') || 
                                request()->is('refund_note/listing') || 
                                request()->is('select_items') || 
                                request()->is('compare') 
                                ? 'show' : '' 
                            }}">
                                <li class="nav-item">
                                    <a href="{{url('/invoice/create')}}" class="nav-link {{ (request()->is('invoice/create') && request()->query('type') != 'self_bill') ? 'active' : '' }}">Create New Invoice</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{url('listing_submission')}}" class="nav-link {{ (request()->is('listing_submission') && request()->query('type') != 'self_bill') ? 'active' : '' }}">Listing Submission</a>
                                </li>
                                <li class="nav-item"><a href="{{url('credit_note/listing')}}" class="nav-link {{ request()->is('credit_note/listing') ? 'active' : '' }}">Listing Credit Note</a></li>
                                <li class="nav-item"><a href="{{url('debit_note/listing')}}" class="nav-link {{ request()->is('debit_note/listing') ? 'active' : '' }}">Listing Debit Note</a></li>
                                <li class="nav-item"><a href="{{url('refund_note/listing')}}" class="nav-link {{ request()->is('refund_note/listing') ? 'active' : '' }}">Listing Refund</a></li>
                                <li class="nav-item"><a href="{{url('select_items')}}" class="nav-link {{ request()->is('select_items') ? 'active' : '' }}">Consolidate List</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="content-wrapper">
            <div class="content">
                <div class="card">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">
                            {{ (request()->query('type') == 'self_bill' || request()->is('self_bill/*')) ? 'Self-Bill Management' : 'Invoice Submission' }}
                        </h5>
                    </div>
                    <div class="card-body table-responsive">
                        @yield('content')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <center>
        <div class="navbar navbar-sm navbar-footer border-top">
            <span>&copy; {{date('Y')}} <a href="">MySyncTax</a></span>
        </div>
    </center>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"></script>
    <script>
        @if(session('success')) Swal.fire({ icon: 'success', title: 'Success!', text: "{{ session('success') }}", timer: 3000 }); @endif
        @if(session('error')) Swal.fire({ icon: 'error', title: 'Error!', text: "{{ session('error') }}" }); @endif
    </script>
    
    {{-- This allows child pages to push custom scripts --}}
    @yield('scripts')
</body>
</html>