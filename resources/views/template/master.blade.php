<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'fa' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=vazirmatn:400,500,600,700|inter:400,500,600,700" rel="stylesheet">
    <link href="{{ versioned_asset('index.css') }}" rel="stylesheet">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ versioned_asset('favicon-32.png') }}" type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ versioned_asset('apple-touch-icon.png') }}">
    @fluxAppearance
    @stack('css')
</head>
<body class="min-h-screen bg-white antialiased dark:bg-zinc-800">
@yield('content')
@fluxScripts
@stack('js')
</body>
</html>
