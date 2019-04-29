<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title') - shorter is better</title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">
    <link href="{{ asset('vendor/@fortawesome/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
    <link type="text/css" href="{{ asset('css/argon.css?v=1.0.0') }}" rel="stylesheet">
    <link rel="icon" href="{{ asset('img/favicon.png') }}" type="image/png">
</head>

<body>
@include('master.sidebar')
@yield('content')
<script src="{{ asset('vendor/jquery/dist/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/chart.js/dist/Chart.min.js') }}"></script>
<script src="{{ asset('vendor/chart.js/dist/Chart.extension.js') }}"></script>
<script src="{{ asset('js/argon.js?v=1.0.0') }}"></script>
</body>
</html>