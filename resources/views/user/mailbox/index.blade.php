@extends('layouts.user')

@section('title', '受信箱')

@section('content')
    <header class="page-head">
        <h1>受信箱</h1>
        <p class="lead">応募受付、面接日程、選考結果が届きます。</p>
    </header>

    @if ($messages->isEmpty())
        <p class="empty">メッセージはまだありません。</p>
    @else
        <div class="mail-list">
            @foreach ($messages as $message)
                <a class="mail-row {{ $message->read_at ? '' : 'unread' }}" href="{{ route('user.mailbox.show', $message) }}">
                    <strong>{{ $message->subject }}</strong>
                    <span>{{ $message->created_at->translatedFormat('n月j日 H:i') }}</span>
                    <p>{{ \Illuminate\Support\Str::limit($message->body, 80) }}</p>
                </a>
            @endforeach
        </div>
        @include('partials.pager', ['paginator' => $messages])
    @endif
@endsection
