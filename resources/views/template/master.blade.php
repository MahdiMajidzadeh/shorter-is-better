<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - Shorter Is Better</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    <link href="{{ asset('index.css') }}" rel="stylesheet">
    <link rel="icon" href="{{ asset('favicon-2.png') }}" type="image/x-icon"/>
    @fluxAppearance
    @stack('css')
</head>
<body class="min-h-screen bg-white antialiased dark:bg-zinc-800">
@yield('content')
@fluxScripts
@stack('js')
</body>
</html>
