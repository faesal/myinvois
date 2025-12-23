<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>MySyncTax</title>

	<!-- Global stylesheets -->
<!-- Replace with your actual paths if different in your Laravel project -->
	<link href="{{ asset('assets/icons/phosphor/styles.min.css') }}" rel="stylesheet" type="text/css">
	<link href="{{ asset('assets/css/ltr/all.min.css') }}" id="stylesheet" rel="stylesheet" type="text/css">
	<!-- /global stylesheets -->



	<!-- /core JS files -->

	<!-- Theme JS files -->

	<!-- /theme JS files -->
    <link href="{{ asset('assets/icons/phosphor/styles.min.css') }}" rel="stylesheet" type="text/css">

<!-- DataTables CSS -->


<!-- DataTables JS -->






</head>

<body>

	

	<!-- Breadcrumbs -->
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

			<!--<div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
				<div class="d-lg-flex mb-2 mb-lg-0">
					<a href="#" class="d-flex align-items-center text-body py-2">
						<i class="ph-lifebuoy me-2"></i>
						Support
					</a>

					<div class="dropdown ms-lg-3">
						<a href="#" class="d-flex align-items-center text-body dropdown-toggle py-2" data-bs-toggle="dropdown">
							<i class="ph-gear me-2"></i>
							<span class="flex-1">Settings</span>
						</a>

						<div class="dropdown-menu dropdown-menu-end w-100 w-lg-auto">
							<a href="#" class="dropdown-item">
								<i class="ph-shield-warning me-2"></i>
								Account security
							</a>
							<a href="#" class="dropdown-item">
								<i class="ph-chart-bar me-2"></i>
								Analytics
							</a>
							<a href="#" class="dropdown-item">
								<i class="ph-lock-key me-2"></i>
								Privacy
							</a>
							<div class="dropdown-divider"></div>
							<a href="#" class="dropdown-item">
								<i class="ph-gear me-2"></i>
								All settings
							</a>
						</div>
					</div>
				</div>
			</div>-->
		</div>
	</div>
	<!-- /breadcrumbs -->


	<!-- Page header -->
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
	<!-- /page header -->


	<!-- Page content -->
	<div class="page-content pt-0">

		<!-- Main sidebar -->
		<div class="sidebar sidebar-main sidebar-expand-lg align-self-start">

			<!-- Sidebar content -->
			<div class="sidebar-content">

				<!-- Sidebar header -->
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
				<!-- /sidebar header -->


				<!-- Main navigation -->
				<!-- Main navigation -->
<div class="sidebar-section">
    <ul class="nav nav-sidebar" data-nav-type="accordion">

        <!-- Developer Docs Header -->
        <li class="nav-item-header pt-0">
            <div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">
                Developer Documentation
            </div>
            <i class="ph-dots-three sidebar-resize-show"></i>
        </li>

        <!-- Introduction -->
    <li class="nav-item">
        <a href="#introduction" class="nav-link">
            <i class="ph-book"></i>
            <span>Introduction</span>
        </a>
    </li>

    <!-- Authentication -->
    <li class="nav-item">
        <a href="#authentication" class="nav-link">
            <i class="ph-lock"></i>
            <span>Authentication</span>
        </a>
    </li>

    <!-- JSON Structure -->
    <li class="nav-item">
        <a href="#json-structure" class="nav-link">
            <i class="ph-brackets-curly"></i>
            <span>JSON Structure</span>
        </a>
    </li>

    <!-- Sample JSON -->
    <li class="nav-item">
        <a href="#sample-json-completed" class="nav-link">
            <i class="ph-file-code"></i>
            <span>Normal Invoice with <br> QR Receipt</span>
        </a>
    </li>

    <!-- Send Data -->
    <li class="nav-item">
        <a href="#send-data" class="nav-link">
            <i class="ph-paper-plane-tilt"></i>
            <span>Send Data API</span>
        </a>
    </li>

    <!-- Responses -->
    <li class="nav-item">
        <a href="#responses" class="nav-link">
            <i class="ph-check-circle"></i>
            <span>Responses</span>
        </a>
    </li>

    <!-- Error Codes -->
    <li class="nav-item">
        <a href="#errors" class="nav-link">
            <i class="ph-warning-circle"></i>
            <span>Error Codes</span>
        </a>
    </li>

    <!-- Code Samples -->
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

	
    </ul>
</div>

<!-- /main navigation -->

				<!-- /main navigation -->

			</div>
			<!-- /sidebar content -->
			
		</div>
		<!-- /main sidebar -->


		<!-- Main content -->
		<div class="content-wrapper">

			<!-- Content area -->
			<div class="content">

				<!-- Info alert -->
				
			    <!-- /info alert -->


				<!-- Navigation types -->
				<div class="card">
					

					<div class="card-body">
						@yield('content')
					</div>
				</div>
				<!-- /navigation types -->



			
				<!-- /navigation markup -->

			</div>
			<!-- /content area -->

		</div>
		<!-- /main content -->

	</div>
	<!-- /page content -->


	<!-- Footer -->
    <center>
	<div class="navbar navbar-sm navbar-footer border-top">
           
			<span>&copy; {{date('Y')}} <a href="">MySyncTax</a></span>
       
		

	</div>
    </center>
	<!-- /footer -->


	<!-- Notifications -->
	<div class="offcanvas offcanvas-end" tabindex="-1" id="notifications">
		<div class="offcanvas-header py-0">
			<h5 class="offcanvas-title py-3">Activity</h5>
			<button type="button" class="btn btn-light btn-sm btn-icon border-transparent rounded-pill" data-bs-dismiss="offcanvas">
				<i class="ph-x"></i>
			</button>
		</div>

	</div>
	<!-- /notifications -->



	<!-- /demo config -->

</body>
</html>

