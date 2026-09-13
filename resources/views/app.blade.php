<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title inertia>{{ config('app.name', 'OrbGem') }}</title>

    <meta name="title" content="OrbGem">
    <meta name="author" content="Matheus Souza">
    <meta name="description" content="Organização financeira pessoal simples, clara e completa">
    <meta name="keywords" content="OrbGem, finanças, organização financeira, investimentos">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="canonical" href="{{ config('app.url') }}">
    <link rel="manifest" href="{{ asset('images/favicon/site.webmanifest') }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:url" content="{{ request()->url() }}">
    <meta property="og:site_name" content="OrbGem">
    <meta property="og:title" content="OrbGem">
    <meta property="og:description" content="Organização financeira pessoal simples, clara e completa">
    <meta property="og:image" content="{{ asset('images/icon.png') }}">
    <meta name="format-detection" content="telephone=no">

    {{-- Favicons --}}
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/favicon/favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon/favicon-16x16.png') }}">
    <meta name="msapplication-TileImage" content="{{ asset('images/favicon/android-chrome-192x192.png') }}">
    <meta name="theme-color" content="#ffffff">

    @viteReactRefresh
    @vite('resources/js/app.js')
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
