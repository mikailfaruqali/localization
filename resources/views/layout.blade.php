<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('vendor/snawbar-localization/images/snawbar.png') }}">
    <title>Snawbar Localization</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/snawbar-localization/css/app.css') }}">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark py-1 fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="{{ route('snawbar.localization.view') }}">
                <img src="{{ asset('vendor/snawbar-localization/images/snawbar.png') }}" alt="Snawbar" width="42" height="42" class="me-2 flex-shrink-0">
                <span class="text-nowrap">Localization</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item mx-1 mx-lg-2">
                        <a class="nav-link" href="{{ route('snawbar.localization.view') }}">
                            <i class="fas fa-folder me-2"></i>
                            Files
                        </a>
                    </li>
                    <li class="nav-item mx-1 mx-lg-2">
                        <a class="nav-link" href="{{ route('snawbar.overrides.index') }}">
                            <i class="fas fa-edit me-2"></i>Overrides
                        </a>
                    </li>
                    <li class="nav-item mx-1 mx-lg-2">
                        <a class="nav-link" href="https://snawbar.com" target="_blank">
                            <i class="fas fa-external-link-alt me-2"></i>snawbar.com
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container main-content">
        @yield('content')
    </main>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="{{ asset('vendor/snawbar-localization/js/app.js') }}"></script>
</body>
</html>