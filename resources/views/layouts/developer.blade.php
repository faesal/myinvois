<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>MySyncTax</title>

    <link href="{{ asset('assets/icons/phosphor/styles.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/ltr/all.min.css') }}" id="stylesheet" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/icons/phosphor/styles.min.css') }}" rel="stylesheet" type="text/css">

</head>

<body>

    

    <div class="page-header page-header-light shadow">
        <div class="page-header-content d-lg-flex">
            <div class="d-flex">
                <div class="breadcrumb py-2">
                    <a href="index.html" class="breadcrumb-item"><i class="ph-house"></i></a>
                    <a href="#" class="breadcrumb-item">MySyncTax</a>
                    
                </div>

                <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                    <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                </a>
            </div>

            </div>
    </div>
    <div class="page-header">
        <div class="page-header-content d-lg-flex">
            <div class="d-flex">
                <h4 class="page-title mb-0">
                    MySyncTax
                </h4>

                <a href="#page_header" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                    <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                </a>
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
            <div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">
                Developer Documentation
            </div>
            <i class="ph-dots-three sidebar-resize-show"></i>
        </li>

        <li class="nav-item">
        <a href="#introduction" class="nav-link">
            <i class="ph-book"></i>
            <span>Introduction</span>
        </a>
    </li>

    <li class="nav-item">
        <a href="#authentication" class="nav-link">
            <i class="ph-lock"></i>
            <span>Authentication</span>
        </a>
    </li>

    <li class="nav-item">
        <a href="#json-structure" class="nav-link">
            <i class="ph-brackets-curly"></i>
            <span>JSON Structure</span>
        </a>
    </li>

    <li class="nav-item">
        <a href="#sample-json-completed" class="nav-link">
            <i class="ph-file-code"></i>
            <span>Normal Invoice with <br> QR Receipt</span>
        </a>
    </li>

    <li class="nav-item">
        <a href="#send-data" class="nav-link">
            <i class="ph-paper-plane-tilt"></i>
            <span>Send Data API</span>
        </a>
    </li>

    <li class="nav-item">
        <a href="#responses" class="nav-link">
            <i class="ph-check-circle"></i>
            <span>Responses</span>
        </a>
    </li>

    <li class="nav-item">
        <a href="#errors" class="nav-link">
            <i class="ph-warning-circle"></i>
            <span>Error Codes</span>
        </a>
    </li>

    <li class="nav-item">
        <a href="#samples" class="nav-link">
            <i class="ph-code"></i>
            <span>Code Samples</span>
        </a>
    </li>

    <li class="nav-item">
        <a href="#invoice-with-customer" class="nav-link">
            <i class="ph-code"></i>
            <span>ERP Invoice <br>(With Customer)</span>
        </a>
    </li>

    <li class="nav-item">
        <a href="#note-api" class="nav-link">
            <i class="ph-code"></i>
            <span>Credit Note, Debit Note, <br>Refund (To Customer)</span>
        </a>
    </li>

    <li class="nav-item">
        <a href="#selfbill-invoice" class="nav-link">
            <i class="ph-code"></i>
            <span>Self-Bill Invoice</span>
        </a>
    </li>

    <li class="nav-item">
        <a href="#selfbill-note" class="nav-link">
            <i class="ph-code"></i>
            <span>Credit,Debit,Refund <br>(To Supplier)</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a href="#general-tin-types" class="nav-link">
            <i class="ph-code"></i>
            <span>General TIN</span>
        </a>
    </li>

    <li class="nav-item">
        <a href="#add-customer" class="nav-link">
            <i class="ph-code"></i>
            <span>Add / Update Customer</span>
        </a>
    </li>

    <li class="nav-item">
        <a href="#add-supplier" class="nav-link">
            <i class="ph-code"></i>
            <span>Add / Update Supplier</span>
        </a>
    </li>

    <li class="nav-item">
        <a href="#generate-pdf" class="nav-link">
            <i class="ph-file-pdf"></i>
            <span>Generate PDF</span>
        </a>
    </li>

    <li class="nav-item">
        <a href="#cancel-document" class="nav-link">
            <i class="ph-x-circle"></i>
            <span>Cancel Document</span>
        </a>
    </li>

    <li class="nav-item">
        <a href="#send-email" class="nav-link">
            <i class="ph-envelope-simple"></i>
            <span>Send Email</span>
        </a>
    </li>
    
    </ul>
</div>

</div>
            </div>
        <div class="content-wrapper">

            <div class="content">

                <div class="card">
                    

                    <div class="card-body">
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
    <div class="offcanvas offcanvas-end" tabindex="-1" id="notifications">
        <div class="offcanvas-header py-0">
            <h5 class="offcanvas-title py-3">Activity</h5>
            <button type="button" class="btn btn-light btn-sm btn-icon border-transparent rounded-pill" data-bs-dismiss="offcanvas">
                <i class="ph-x"></i>
            </button>
        </div>

    </div>
    </body>
</html>