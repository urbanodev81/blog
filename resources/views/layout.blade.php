<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') — {{ config('app.name') }}</title>
    <meta name="description" content="@yield('descricao')">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="@yield('titulo')">
    <meta property="og:description" content="@yield('descricao')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    @stack('blog_cabecalho')
</head>
<body class="blog-corpo">
    <header class="blog-topo"><a href="{{ url('/') }}">{{ config('app.name') }}</a></header>
    <main>@yield('conteudo')</main>
</body>
</html>
