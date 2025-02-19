<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Fav Icon  -->
    <link rel="icon" type="image/x-icon" href="{{ asset('vendor/snawbar-localization/images/snawbar.png') }}">

    <!--jquery-->
    <script type="text/javascript" src="{{ asset('vendor/snawbar-localization/js/cash.js') }}"></script>

    <!-- StyleSheets -->
    <link rel="stylesheet" href="{{ asset('vendor/snawbar-localization/css/bootstrap.min.css') }}">

    <!-- Page Title  -->
    <title>Snawbar Localization</title>
</head>
<body>
    <nav class="navbar navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="javascript:void(0)">
                <img src="{{ asset('vendor/snawbar-localization/images/snawbar.png') }}" width="30" height="24" class="d-inline-block align-text-top">
                Snawbar
            </a>
        </div>
    </nav>

    <!-- JavaScript -->
    <script src="{{ asset('vendor/snawbar-localization/js/bootstrap.min.js') }}"></script>
</body>
</html>
