<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Fav Icon  -->
    <link rel="icon" type="image/x-icon" href="{{ asset('vendor/snawbar-localization/images/snawbar.png') }}">

    <!--jquery-->
    <script type="text/javascript" src="{{ asset('vendor/snawbar-localization/js/jquery.min.js') }}"></script>

    <!-- StyleSheets -->
    <link rel="stylesheet" href="{{ asset('vendor/snawbar-localization/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/snawbar-localization/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/snawbar-localization/css/select2-bootstrap-5-theme.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/snawbar-localization/css/styles.css') }}">

    <!-- Page Title  -->
    <title>Snawbar Localization</title>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark shadow">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="{{ route('snawbar.localization.view') }}">
                <img src="{{ asset('vendor/snawbar-localization/images/snawbar.png') }}" width="50" height="50" class="d-inline-block align-text-top">
                <span class="ms-2">Snawbar</span>
            </a>
            <a href="https://snawbar.com" class="text-white text-decoration-none" target="_blank">www.snawbar.com</a>
        </div>
    </nav>

    <div class="container-fluid mt-4 mb-4">
        @yield('content')
    </div>

    <!-- Toast -->
    <div id="toast-container" class="position-fixed top-0 end-0 p-3"></div>

    <!-- JavaScript -->
    <script src="{{ asset('vendor/snawbar-localization/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('vendor/snawbar-localization/js/select2.min.js') }}"></script>
    <script src="{{ asset('vendor/snawbar-localization/js/scripts.js') }}"></script>
</body>
</html>
