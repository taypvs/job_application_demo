<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ページが見つかりません — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="guest-body">
    <main class="guest">
        <section class="card auth-card">
            <h1>ページが見つかりません</h1>
            <p class="lead">アドレスをご確認ください。</p>
            <a class="btn btn-primary" href="{{ route('home') }}">ホームへ戻る</a>
        </section>
    </main>
</body>
</html>
