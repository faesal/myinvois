<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title') - MySyncTax Admin Portal</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

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
        .full-title { display: inline-block; }
        /* Mobile short title */
        .mobile-title { display: none; }

        @media (max-width: 768px) {
            .full-title { display: none !important; }
            .mobile-title {
                display: inline-block !important;
                font-size: 15px !important;
                font-weight: 600;
            }
            .dev-header .title-area img.logo { width: 23px !important; }
        }

        .dev-header .profile-area {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        /* Admin Avatar Color */
        .dev-header .profile-area .avatar {
            background: #1e293b; /* Dark Slate for Admin */
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
            overflow-y: auto;
        }

        .dev-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .dev-menu li { margin-bottom: 6px; }

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
            transition: all 0.2s;
        }

        .dev-menu a:hover,
        .dev-menu a.active {
            background: #f1f5f9;
            color: #0f172a;
            font-weight: 600;
        }

        /* Section Headers in Sidebar */
        .menu-header {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #9ca3af;
            font-weight: 700;
            margin: 25px 0 10px 15px;
        }
        .menu-header:first-child { margin-top: 5px; }

        /* ===== CONTENT AREA ===== */
        .dev-content {
            margin-left: 240px;
            padding: 95px 30px 40px;
        }

        /* ===== MOBILE ===== */
        .mobile-menu-btn { display: none; }

        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: inline-block;
                font-size: 22px;
                padding: 6px 10px;
                cursor: pointer;
            }

            .dev-sidebar { transform: translateX(-100%); }
            .dev-sidebar.open { transform: translateX(0); }

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

            .sidebar-overlay.active { display: block; }

            .dev-content {
                margin-left: 0 !important;
                padding: 90px 18px 40px;
            }
        }

        /* ===== SWEETALERT2 CUSTOM STYLING ===== */
        .swal2-popup { font-family: 'Inter', sans-serif !important; }
        .swal2-title { font-size: 24px !important; font-weight: 600 !important; }
        .swal2-html-container { font-size: 16px !important; }
        .swal2-confirm { padding: 10px 30px !important; font-weight: 500 !important; }
    </style>
</head>
<body>

    <div class="dev-header">
        <div class="title-area">
            <i class="fa-solid fa-bars mobile-menu-btn d-md-none" id="openSidebar"></i>
            <img src="https://img.icons8.com/?size=512&id=59833&format=png" class="logo">
            <span class="full-title text-uppercase ms-2" style="letter-spacing: 0.5px;">MySyncTax Admin</span>
            <span class="mobile-title">Admin</span>
        </div>

        <div class="profile-area">
            <i class="fa-regular fa-bell fa-lg me-3"></i>

            <div class="dropdown">
                <a class="d-flex align-items-center dropdown-toggle text-decoration-none"
                   href="#" id="userDropdown" role="button"
                   data-bs-toggle="dropdown" aria-expanded="false">

                    <div class="avatar me-2">
                        {{ strtoupper(substr(auth()->user()->name ?? 'AD', 0, 2)) }}
                    </div>

                    <span class="fw-semibold">{{ auth()->user()->name ?? 'Administrator' }}</span>
                </a>

                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li>
                        <a class="dropdown-item" href="{{ route('developer.profile.edit') }}">
                            <i class="fa-solid fa-user-shield me-2"></i> Admin Profile
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

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="dev-sidebar" id="mobileSidebar">
        <ul class="dev-menu">
            

            <li class="menu-header">User Management</li>
            
            <li>
                <a href="{{ route('admin.subscribers.index') }}" class="{{ request()->routeIs('admin.subscribers*') ? 'active' : '' }}">
                    <i class="fa-solid fa-users-gear"></i> Manage Subscribers
                </a>
            </li>

            <li>
                <a href="{{ route('admin.developers.index') }}" class="{{ request()->routeIs('admin.developers*') ? 'active' : '' }}">
                    <i class="fa-solid fa-user-shield"></i> Manage Developers
                </a>
            </li>


        </ul>
    </div>

    <div class="dev-content">
        @yield('content')
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

            // Flash Messages
            @if(session('success'))
                Swal.fire({
                    icon: 'success', title: 'Success!', text: '{{ session('success') }}',
                    confirmButtonText: 'OK', confirmButtonColor: '#22c55e', timer: 3000, timerProgressBar: true,
                });
            @endif

            @if(session('error'))
                Swal.fire({
                    icon: 'error', title: 'Error!', text: '{{ session('error') }}',
                    confirmButtonText: 'OK', confirmButtonColor: '#ef4444',
                });
            @endif
        });
    </script>

    @yield('scripts')

</body>
</html>