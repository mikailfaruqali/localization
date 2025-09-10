<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('vendor/snawbar-localization/images/snawbar.png') }}">
    <script type="text/javascript" src="{{ asset('vendor/snawbar-localization/js/jquery.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('vendor/snawbar-localization/css/app.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Snawbar Localization</title>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <a class="navbar-brand" href="{{ route('snawbar.localization.view') }}">
                    <img src="{{ asset('vendor/snawbar-localization/images/snawbar.png') }}" alt="Snawbar">
                    <span>Snawbar Localization</span>
                </a>
                <div class="navbar-right">
                    <a href="https://snawbar.com" class="navbar-link" target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                        snawbar.com
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container main-content">
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('vendor/snawbar-localization/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('vendor/snawbar-localization/js/app.js') }}"></script>
</body>
</html>