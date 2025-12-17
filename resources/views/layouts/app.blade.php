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

	<!-- Core JS files -->
	<script src="{{ asset('assets/demo/demo_configurator.js') }}"></script>
	<script src="{{ asset('assets/js/bootstrap/bootstrap.bundle.min.js') }}"></script>
	<!-- /core JS files -->



	<!-- /theme JS files -->
    <link href="{{ asset('assets/icons/phosphor/styles.min.css') }}" rel="stylesheet" type="text/css">

<!-- DataTables CSS -->


<!-- DataTables JS -->

<script src="{{ asset('assets/js/jquery/jquery.min.js') }}"></script>

<script src="{{ asset('assets/js/vendor/tables/datatables/datatables.min.js')}}"></script>






</head>

<body>

	<!-- Main navbar -->
	<div class="navbar navbar-dark navbar-expand-lg navbar-static">
		<div class="container-fluid">
			<div class="d-flex d-lg-none me-2">
				<button type="button" class="navbar-toggler sidebar-mobile-main-toggle rounded-pill">
					<i class="ph-list"></i>
				</button>
			</div>

			<div class="navbar-brand flex-1 flex-lg-0">
				<a href="index.html" class="d-inline-flex align-items-center">
					<img src="../../../assets/images/logo_icon.svg" alt="">
					<img src="../../../assets/images/logo_text_light.svg" class="d-none d-sm-inline-block h-16px ms-3" alt="">
				</a>
			</div>

			<ul class="nav flex-row">
				<li class="nav-item d-lg-none">
					
				</li>

		

				
			</ul>

		

			<ul class="nav flex-row justify-content-end order-1 order-lg-2">
				<!--<li class="nav-item ms-lg-2">
					<a href="#" class="navbar-nav-link navbar-nav-link-icon rounded-pill" data-bs-toggle="offcanvas" data-bs-target="#notifications">
						<i class="ph-bell"></i>
						<span class="badge bg-yellow text-black position-absolute top-0 end-0 translate-middle-top zindex-1 rounded-pill mt-1 me-1">2</span>
					</a>
				</li>-->

				<li class="nav-item nav-item-dropdown-lg dropdown ms-lg-2">
					<a href="#" class="navbar-nav-link align-items-center rounded-pill p-1" data-bs-toggle="dropdown">
						<div class="status-indicator-container">
							<img src="../../../assets/images/demo/users/face11.jpg" class="w-32px h-32px rounded-pill" alt="">
							<span class="status-indicator bg-success"></span>
						</div>
						<span class="d-none d-lg-inline-block mx-lg-2">Victoria</span>
					</a>

					<div class="dropdown-menu dropdown-menu-end">
						<a href="{{url('/user/profile')}}" class="dropdown-item">
							<i class="ph-user-circle me-2"></i>
							My profile
						</a>
						<!--<a href="#" class="dropdown-item">
							<i class="ph-currency-circle-dollar me-2"></i>
							My subscription
						</a>
						<a href="#" class="dropdown-item">
							<i class="ph-shopping-cart me-2"></i>
							My orders
						</a>
						<a href="#" class="dropdown-item">
							<i class="ph-envelope-open me-2"></i>
							My inbox
							<span class="badge bg-primary rounded-pill ms-auto">26</span>
						</a>
						<div class="dropdown-divider"></div>
						<a href="#" class="dropdown-item">
							<i class="ph-gear me-2"></i>
							Account settings
						</a>-->
						<a href="{{url('/logout')}}" class="dropdown-item">
							<i class="ph-sign-out me-2"></i>
							Logout
						</a>
					</div>
				</li>
			</ul>
		</div>
	</div>
	<!-- /main navbar -->


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

        <!-- Main -->
        <li class="nav-item-header pt-0">
            <div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">Main</div>
            <i class="ph-dots-three sidebar-resize-show"></i>
        </li>

        <li class="nav-item">
            <a href="{{url('main')}}" class="nav-link">
                <i class="ph-gauge"></i>
                <span>
                    Dashboard
                    <span class="d-block fw-normal text-body opacity-50">Analytic Report</span>
                </span>
            </a>
        </li>

        <li class="nav-item">
            <a href="{{url('/customer/listing_customer')}}" class="nav-link">
                <i class="ph-users"></i>
                <span>
                    Customer Listing
                </span>
            </a>
        </li>

        <li class="nav-item">
            <a href="{{url('/invoice/create')}}" class="nav-link">
                <i class="ph-file-text"></i>
                <span>
                    Create New Invoice
                </span>
            </a>
        </li>

        <li class="nav-item">
            <a href="{{url('listing_submission')}}" class="nav-link">
                <i class="ph-upload-simple"></i>
                <span>
                    Listing Submission
                    <span class="d-block fw-normal text-body opacity-50">All submission to LHDN</span>
                </span>
            </a>
        </li>

        <li class="nav-item">
            <a href="{{url('credit_note/listing')}}" class="nav-link">
                <i class="ph-note-pencil"></i>
                <span>
                    Listing Credit Note
                </span>
            </a>
        </li>

        <li class="nav-item">
            <a href="{{url('debit_note/listing')}}" class="nav-link">
                <i class="ph-note"></i>
                <span>
                    Listing Debit Note
                </span>
            </a>
        </li>

        <li class="nav-item">
            <a href="{{url('refund_note/listing')}}" class="nav-link">
                <i class="ph-arrow-counter-clockwise"></i>
                <span>
                    Listing Refund
                </span>
            </a>
        </li>

        <li class="nav-item">
            <a href="{{url('select_items')}}" class="nav-link">
                <i class="ph-list-dashes"></i>
                <span>
                    Consolidate List
                </span>
            </a>
        </li>

        <li class="nav-item">
            <a href="{{url('compare')}}" class="nav-link">
                <i class="ph-git-compare"></i>
                <span>
                    Compare List
                </span>
            </a>
        </li>

		<!-- NEW: Developer Documentation -->
		<li class="nav-item">
			<a href="{{url('developer/documentation')}}" class="nav-link">
				<i class="ph-code"></i>
				<span>
					Developer Documentation
					<span class="d-block fw-normal text-body opacity-50">API Reference</span>
				</span>
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
					<div class="card-header">
						<h5 class="mb-0">Invoice Submission</h5>
					</div>

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

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"></script>

<script>
    // ============================================================
    // UNIVERSAL SWEETALERT2 FUNCTIONS (AJAX & GLOBAL USE)
    // ============================================================
    function popupSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: message,
            confirmButtonText: 'OK',
            confirmButtonColor: '#22c55e',
            timer: 3000,
            timerProgressBar: true,
        });
    }

    function popupError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: message,
            confirmButtonText: 'OK',
            confirmButtonColor: '#ef4444',
        });
    }

    function popupWarning(message) {
    Swal.fire({
        icon: 'warning',   // required for spacing
        title: 'Warning!',
        text: message,
        confirmButtonText: 'OK',
        confirmButtonColor: '#f59e0b',

        // Use custom HTML icon
        iconHtml: `
            <div class="custom-warning-circle">
                <span class="custom-warning-mark">!</span>
            </div>
        `,
        customClass: {
            icon: 'custom-warning-wrapper'
        }
    });
}


    function popupInfo(message) {
        Swal.fire({
            icon: 'info',
            title: 'Information',
            text: message,
            confirmButtonText: 'OK',
            confirmButtonColor: '#3b82f6',
        });
    }

    // ============================================================
    // FLASH MESSAGE POPUPS (FROM CONTROLLERS)
    // ============================================================
    @if(session('success'))
        popupSuccess("{{ session('success') }}");
    @endif

    @if(session('error'))
        popupError("{{ session('error') }}");
    @endif

    @if(session('warning'))
        popupWarning("{{ session('warning') }}");
    @endif

    @if(session('info'))
        popupInfo("{{ session('info') }}");
    @endif
</script>


<!-- Optional: Perfect rounded popup styling -->
<style>
    .swal-popup-rounded {
        border-radius: 14px !important;
        padding: 25px !important;
    }
    .swal-title-bold {
        font-weight: 700 !important;
        font-size: 24px !important;
    }
    .swal2-icon.swal2-warning {
        transform: scale(0.45) !important;
        margin-top: 0 !important;
        margin-bottom: -5px !important;
    }
</style>


</body>
</html>

