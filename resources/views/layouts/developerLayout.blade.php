<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title') - MySyncTax Developer Portal</title>



    <!-- Google Fonts -->

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">



    <!-- Icons -->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">



    <!-- Bootstrap -->

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">



    <!-- SweetAlert2 CSS -->

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.min.css">



    <style>

        body {

            font-family: 'Inter', sans-serif;

            background: #f5f6fa;

            margin: 0;

            overflow-x: hidden;

        }



        /* ===== HEADER ===== */

        .dev-header {

            width: 100%;

            height: 65px;

            background: #ffffff;

            border-bottom: 1px solid #e5e7eb;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 25px;

            position: fixed;

            top: 0;

            left: 0;

            z-index: 1000;

        }



        .dev-header .title-area {

            display: flex;

            align-items: center;

            gap: 10px;

            font-weight: 600;

            font-size: 16px;

        }



        .dev-header img.logo {

            width: 26px;

            height: auto;

        }



        /* Desktop full title */

        .full-title {

            display: inline-block;

        }



        /* Mobile short title */

        .mobile-title {

            display: none;

        }



        /* Adjustments for mobile title */

        @media (max-width: 768px) {

            .full-title {

                display: none !important;

            }



            .mobile-title {

                display: inline-block !important;

                font-size: 15px !important;

                font-weight: 600;

            }



            .dev-header .title-area img.logo {

                width: 23px !important;

            }

        }



        .dev-header .profile-area {

            display: flex;

            align-items: center;

            gap: 18px;

        }



        .dev-header .profile-area .avatar {

            background: #6366f1;

            color: white;

            font-size: 14px;

            font-weight: 600;

            width: 36px;

            height: 36px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

        }



        /* ===== SIDEBAR ===== */

        .dev-sidebar {

            position: fixed;

            top: 65px;

            left: 0;

            width: 240px;

            height: calc(100vh - 65px);

            background: #ffffff;

            border-right: 1px solid #e5e7eb;

            padding: 25px 15px;

            z-index: 1500;

            transition: transform 0.25s ease-in-out;

        }



        .dev-menu {

            list-style: none;

            padding: 0;

            margin: 0;

        }



        .dev-menu li {

            margin-bottom: 6px;

        }



        .dev-menu a {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 10px 15px;

            border-radius: 8px;

            color: #374151;

            text-decoration: none;

            font-size: 15px;

            font-weight: 500;

        }



        .dev-menu a:hover,

        .dev-menu a.active {

            background: #eef2ff;

            color: #4338ca;

        }



        /* Submenu */

        .submenu {

            padding-left: 40px;

            margin-top: -6px;

            margin-bottom: 10px;

        }



        .submenu a {

            padding: 7px 0;

            font-size: 14px;

        }



        /* ===== CONTENT AREA ===== */

        .dev-content {

            margin-left: 240px;

            padding: 95px 30px 40px;

        }



        /* ===== MOBILE ===== */

        .mobile-menu-btn {

            display: none;

        }



        /* MOBILE RULES */

        @media (max-width: 768px) {

            .mobile-menu-btn {

                display: inline-block;

                font-size: 22px;

                padding: 6px 10px;

                cursor: pointer;

            }



            .dev-sidebar {

                transform: translateX(-100%);

            }



            .dev-sidebar.open {

                transform: translateX(0);

            }



            /* Overlay */

            .sidebar-overlay {

                position: fixed;

                top: 65px;

                left: 0;

                width: 100%;

                height: calc(100vh - 65px);

                background: rgba(0, 0, 0, 0.45);

                z-index: 1400;

                display: none;

            }



            .sidebar-overlay.active {

                display: block;

            }



            .dev-content {

                margin-left: 0 !important;

                padding: 90px 18px 40px;

            }

        }



        /* ===== SWEETALERT2 CUSTOM STYLING ===== */

        .swal2-popup {

            font-family: 'Inter', sans-serif !important;

        }



        .swal2-title {

            font-size: 24px !important;

            font-weight: 600 !important;

        }



        .swal2-html-container {

            font-size: 16px !important;

        }



        .swal2-confirm {

            padding: 10px 30px !important;

            font-weight: 500 !important;

        }

    </style>



</head>

<body>



   <!-- ===== HEADER ===== -->

<div class="dev-header">



    <div class="title-area">



        <!-- Mobile Hamburger Button -->

        <i class="fa-solid fa-bars mobile-menu-btn d-md-none" id="openSidebar"></i>



        <!-- Logo -->

        <img src="https://img.icons8.com/?size=512&id=59833&format=png" class="logo">



        <!-- Desktop Title -->

        <span class="full-title">MySyncTax LHDN Developer Portal</span>



        <!-- Mobile Title -->

        <span class="mobile-title">MySyncTax</span>



    </div>



    <div class="profile-area">



        <i class="fa-regular fa-bell fa-lg me-3"></i>



        <!-- USER DROPDOWN -->

        <div class="dropdown">



            <a class="d-flex align-items-center dropdown-toggle text-decoration-none"

               href="#" id="userDropdown" role="button"

               data-bs-toggle="dropdown" aria-expanded="false">



                <!-- Avatar -->

                <div class="avatar me-2">

                    {{ strtoupper(substr(auth()->user()->name,0,2)) }}

                </div>



                <!-- Username -->

                <span class="fw-semibold">{{ auth()->user()->name }}</span>

            </a>



            <!-- Dropdown Menu -->

            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">



                <li>

                    <a class="dropdown-item" href="{{ route('developer.profile.edit') }}">

                        <i class="fa-solid fa-user me-2"></i> Profile

                    </a>

                </li>



                <li><hr class="dropdown-divider"></li>



                <li>

                    <form method="POST" action="{{ route('logout') }}">

                        @csrf

                        <button class="dropdown-item text-danger" type="submit">

                            <i class="fa-solid fa-right-from-bracket me-2"></i> Logout

                        </button>

                    </form>

                </li>



            </ul>



        </div>

    </div>

</div>



    <!-- Overlay -->

    <div class="sidebar-overlay" id="sidebarOverlay"></div>



    <!-- ===== SIDEBAR ===== -->

    <div class="dev-sidebar" id="mobileSidebar">



        <ul class="dev-menu">

            <li>

                <a href="{{ url('/developer/dashboard') }}" class="{{ request()->is('developer/dashboard') ? 'active' : '' }}">

                    <i class="fa-solid fa-gauge"></i> Dashboard

                </a>

            </li>



            <li>

                <a href="{{ url('/developer/consolidate') }}">

                    <i class="fa-solid fa-file-invoice"></i> Consolidate List

                </a>

            </li>



           <li>

                <a href="{{ route('developer.invoices.index') }}">

                    <i class="fa-solid fa-file-invoice"></i> Invoice Submissions

                </a>

            </li>



            <li>

                <a href="{{ route('developer.companies.index') }}">

                    <i class="fa-solid fa-building"></i> LHDN Accounts

                </a>

            </li>



            <ul class="submenu">

                <li>

                    <a href="{{ route('developer.companies.add') }}">

                        <i class="fa-solid fa-plus"></i> Add New Account

                    </a>

                </li>



            </ul>
            <li>

<a href="{{ url('/developer/documentation') }}">

    <i class="fa-solid fa-file-invoice"></i> API Documentation

</a>

</li>
        </ul>

       

    </div>



    <!-- ===== MAIN CONTENT ===== -->

    <div class="dev-content">

        @yield('content')

    </div>



<!-- jQuery MUST be here, BEFORE dashboard scripts -->

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>



<!-- Bootstrap (optional) -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>



<!-- SweetAlert2 JS -->

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"></script>



<script>

    document.addEventListener("DOMContentLoaded", function() {

        const sidebar = document.getElementById("mobileSidebar");

        const overlay = document.getElementById("sidebarOverlay");

        const openBtn = document.getElementById("openSidebar");



        openBtn.addEventListener("click", function() {

            sidebar.classList.add("open");

            overlay.classList.add("active");

        });



        overlay.addEventListener("click", function() {

            sidebar.classList.remove("open");

            overlay.classList.remove("active");

        });



        // ============================================================

        // SWEETALERT2 FLASH MESSAGES

        // ============================================================



        @if(session('success'))

            Swal.fire({

                icon: 'success',

                title: 'Success!',

                text: '{{ session('success') }}',

                confirmButtonText: 'OK',

                confirmButtonColor: '#22c55e',

                timer: 3000,

                timerProgressBar: true,

            });

        @endif



        @if(session('error'))

            Swal.fire({

                icon: 'error',

                title: 'Error!',

                text: '{{ session('error') }}',

                confirmButtonText: 'OK',

                confirmButtonColor: '#ef4444',

            });

        @endif



        @if(session('warning'))

            Swal.fire({

                icon: 'warning',

                title: 'Warning!',

                text: '{{ session('warning') }}',

                confirmButtonText: 'OK',

                confirmButtonColor: '#f59e0b',

            });

        @endif



        @if(session('info'))

            Swal.fire({

                icon: 'info',

                title: 'Information',

                text: '{{ session('info') }}',

                confirmButtonText: 'OK',

                confirmButtonColor: '#3b82f6',

            });

        @endif

    });

</script>



@yield('scripts')



</body>

</html>