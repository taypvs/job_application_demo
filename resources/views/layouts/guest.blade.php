<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="guest-body {{ \App\Support\Portal::isAdmin() ? 'theme-admin' : 'theme-user' }}">
    <main class="guest">
        <a class="brand brand-center" href="{{ route('login') }}">
            <span class="mark">{{ \App\Support\Portal::isAdmin() ? '管' : '求' }}</span>
            <span>
                <strong>{{ \App\Support\Portal::isAdmin() ? '求人管理' : '求人を探す' }}</strong>
                <small>{{ \App\Support\Portal::isAdmin() ? '管理画面' : '候補者' }}</small>
            </span>
        </a>
        <section class="card auth-card">
            @include('partials.alerts')
            @yield('content')
        </section>
    </main>
</body>
</html>
