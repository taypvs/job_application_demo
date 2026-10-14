<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="theme-user">
    <input id="nav-toggle" class="nav-toggle" type="checkbox">
    <header class="topbar">
        <a class="brand" href="{{ route('user.jobs.index') }}">
            <span class="mark">求</span>
            <span>
                <strong>求人を探す</strong>
                <small>候補者</small>
            </span>
        </a>
        <nav class="topnav">
            <a href="{{ route('user.jobs.index') }}" @class(['active' => request()->routeIs('user.jobs.*')])>求人一覧</a>
            <a href="{{ route('user.cv.index') }}" @class(['active' => request()->routeIs('user.cv.*')])>職務経歴書</a>
            <a href="{{ route('user.applications.index') }}" @class(['active' => request()->routeIs('user.applications.*')])>応募状況</a>
            <a href="{{ route('user.mailbox.index') }}" @class(['active' => request()->routeIs('user.mailbox.*')])>
                受信箱
                @if ($unreadMailboxCount > 0)
                    <span class="count">{{ $unreadMailboxCount }}</span>
                @endif
            </a>
        </nav>
        <div class="top-user">
            <span>{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">ログアウト</button>
            </form>
        </div>
        <label for="nav-toggle" class="nav-burger">メニュー</label>
    </header>
    <main class="user-main">
        @include('partials.alerts')
        @yield('content')
    </main>
    @include('partials.chatbot')
    <script src="{{ asset('js/busy.js') }}"></script>
</body>
</html>
