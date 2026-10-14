<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="theme-admin">
    <input id="nav-toggle" class="nav-toggle" type="checkbox">
    <div class="shell">
        <aside class="side">
            <a class="brand" href="{{ route('admin.jobs.index') }}">
                <span class="mark">管</span>
                <span>
                    <strong>求人管理</strong>
                    <small>管理画面</small>
                </span>
            </a>
            <nav>
                <a href="{{ route('admin.jobs.index') }}" @class(['active' => request()->routeIs('admin.jobs.*')])>求人管理</a>
                <a href="{{ route('admin.applications.index') }}" @class(['active' => request()->routeIs('admin.applications.*')])>応募者</a>
                <a href="{{ route('admin.interviews.index') }}" @class(['active' => request()->routeIs('admin.interviews.*')])>面接カレンダー</a>
                <a href="{{ route('admin.documents.index') }}" @class(['active' => request()->routeIs('admin.documents.*')])>資料アップロード</a>
            </nav>
            <div class="side-user">
                <div>
                    <strong>{{ auth()->user()->name }}</strong>
                    <small>管理者</small>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">ログアウト</button>
                </form>
            </div>
        </aside>
        <div class="main">
            <label for="nav-toggle" class="nav-burger">メニュー</label>
            @include('partials.alerts')
            @yield('content')
        </div>
    </div>
    <script src="{{ asset('js/busy.js') }}"></script>
</body>
</html>
