@extends('layouts.guest')

@section('title', 'ログイン')

@section('content')
    <h1>ログイン</h1>
    @if (\App\Support\Portal::isAdmin())
        <p class="lead">求人・応募者・面接日程を管理します。</p>
    @else
        <p class="lead">求人の確認、応募、選考状況の確認ができます。</p>
    @endif
    <form method="POST" action="{{ route('login.store') }}" class="stack">
        @csrf
        <label>
            メールアドレス
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        </label>
        <label>
            パスワード
            <input type="password" name="password" required>
        </label>
        <label class="check">
            <input type="checkbox" name="remember" value="1">
            ログイン状態を保持する
        </label>
        <button class="btn btn-primary" type="submit">ログイン</button>
    </form>
    @if (\App\Support\Portal::isAdmin())
        <p class="foot-link"><a href="{{ \App\Support\Portal::baseUrl(\App\Support\Portal::CANDIDATE) }}/login">候補者の方はこちら</a></p>
    @else
        <p class="foot-link"><a href="{{ route('register') }}">アカウントをお持ちでない方は新規登録</a></p>
        <p class="foot-link"><a href="{{ \App\Support\Portal::baseUrl(\App\Support\Portal::ADMIN) }}/login">管理者の方はこちら</a></p>
    @endif
    <aside class="demo">
        <p>デモアカウント（パスワードは password）</p>
        @if (\App\Support\Portal::isAdmin())
            <p>管理者: admin@example.com</p>
        @else
            <p>候補者: user@example.com</p>
        @endif
    </aside>
@endsection
