<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <input id="nav-toggle" class="nav-toggle" type="checkbox">
    <div class="shell">
        <aside class="side">
            <a class="brand" href="{{ route('home') }}">
                <span class="mark">管</span>
                <span>
                    <strong>求人管理</strong>
                    <small>Kanri</small>
                </span>
            </a>
            <nav>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.jobs.index') }}" @class(['active' => request()->routeIs('admin.jobs.*')])>求人管理</a>
                    <a href="{{ route('admin.applications.index') }}" @class(['active' => request()->routeIs('admin.applications.*')])>応募者</a>
                    <a href="{{ route('admin.interviews.index') }}" @class(['active' => request()->routeIs('admin.interviews.*')])>面接カレンダー</a>
                    <a href="{{ route('admin.documents.index') }}" @class(['active' => request()->routeIs('admin.documents.*')])>資料アップロード</a>
                @else
                    <a href="{{ route('user.jobs.index') }}" @class(['active' => request()->routeIs('user.jobs.*')])>求人一覧</a>
                    <a href="{{ route('user.cv.index') }}" @class(['active' => request()->routeIs('user.cv.*')])>職務経歴書</a>
                    <a href="{{ route('user.applications.index') }}" @class(['active' => request()->routeIs('user.applications.*')])>応募状況</a>
                    <a href="{{ route('user.mailbox.index') }}" @class(['active' => request()->routeIs('user.mailbox.*')])>
                        受信箱
                        @if ($unreadMailboxCount > 0)
                            <span class="count">{{ $unreadMailboxCount }}</span>
                        @endif
                    </a>
                @endif
            </nav>
            <div class="side-user">
                <div>
                    <strong>{{ auth()->user()->name }}</strong>
                    <small>{{ auth()->user()->isAdmin() ? '管理者' : '候補者' }}</small>
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
    @if (auth()->user()->isCandidate())
        @include('partials.chatbot')
    @endif
</body>
</html>
