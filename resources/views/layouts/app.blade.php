<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'JoyTel API Dashboard')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
        }
        .sidebar .nav-link {
            color: #fff;
            border-radius: 0.375rem;
            margin: 0.125rem 0;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: #495057;
            color: #fff;
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            transition: transform 0.2s;
        }
        .bg-gradient-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
        }
        .bg-gradient-success {
            background: linear-gradient(45deg, #28a745, #1e7e34);
        }
        .bg-gradient-warning {
            background: linear-gradient(45deg, #ffc107, #d39e00);
        }
        .bg-gradient-danger {
            background: linear-gradient(45deg, #dc3545, #bd2130);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">
                            <i class="bi bi-broadcast"></i> JoyTel API
                        </h4>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('/') ? 'active' : '' }}" 
                               href="{{ route('dashboard') }}">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('orders*') ? 'active' : '' }}" 
                               href="{{ route('orders.index') }}">
                                <i class="bi bi-list-ul"></i> Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('queue*') ? 'active' : '' }}" 
                               href="{{ route('queue.monitor') }}">
                                <i class="bi bi-clock-history"></i> Queue Monitor
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('logs*') ? 'active' : '' }}" 
                               href="{{ route('logs.index') }}">
                                <i class="bi bi-file-text"></i> API Logs
                            </a>
                        </li>
                        @if(auth()->user()?->isDev())
                            <li class="nav-item">
                                <a class="nav-link {{ Request::is('settings*') ? 'active' : '' }}" 
                                   href="{{ route('settings.joytel') }}">
                                    <i class="bi bi-gear"></i> Settings
                                </a>
                            </li>
                        @endif
                        <li class="nav-item">
                            <a class="nav-link" href="{{ config('app.url') }}/api/utils/health" target="_blank">
                                <i class="bi bi-heart-pulse"></i> Health Check
                            </a>
                        </li>
                    </ul>
                    
                    <hr class="text-white">
                    
                    <div class="text-white-50 px-3">
                        <small>
                            <i class="bi bi-server"></i> Environment: {{ app()->environment() }}<br>
                            <i class="bi bi-clock"></i> {{ now()->format('H:i:s') }}
                        </small>
                    </div>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">@yield('page-title', 'Dashboard')</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        @auth
                            <div class="me-3 text-end">
                                <div class="small text-muted">{{ auth()->user()->email }}</div>
                                <div class="small fw-semibold text-uppercase">{{ auth()->user()->isDev() ? 'Dev' : 'Admin' }}</div>
                            </div>
                            <form method="POST" action="{{ route('logout') }}" class="me-2">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </button>
                            </form>
                        @endauth
                        @yield('page-actions')
                    </div>
                </div>

                <!-- Alerts -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Content -->
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Auto refresh page every 30 seconds for monitoring pages
        if (window.location.pathname.includes('queue') || window.location.pathname.includes('logs')) {
            setTimeout(function() {
                window.location.reload();
            }, 30000);
        }

        // CSRF Token setup for AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
    
    @stack('scripts')
</body>
</html>