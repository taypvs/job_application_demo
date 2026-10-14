@extends('layouts.guest')

@section('title', '新規登録')

@section('content')
    <h1>新規登録</h1>
    <p class="lead">候補者アカウントを作成します。</p>
    <form method="POST" action="{{ route('register.store') }}" class="stack">
        @csrf
        <label>
            氏名
            <input type="text" name="name" value="{{ old('name') }}" required>
        </label>
        <label>
            メールアドレス
            <input type="email" name="email" value="{{ old('email') }}" required>
        </label>
        <label>
            パスワード
            <input type="password" name="password" required>
        </label>
        <label>
            パスワード（確認）
            <input type="password" name="password_confirmation" required>
        </label>
        <button class="btn btn-primary" type="submit">登録してはじめる</button>
    </form>
    <p class="foot-link"><a href="{{ route('login') }}">すでにアカウントをお持ちの方はログイン</a></p>
@endsection
