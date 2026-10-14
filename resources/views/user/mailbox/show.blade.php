@extends('layouts.user')

@section('title', $message->subject)

@section('content')
    <p class="crumb"><a href="{{ route('user.mailbox.index') }}">受信箱</a> / {{ $message->subject }}</p>
    <article class="card">
        <h1>{{ $message->subject }}</h1>
        <p class="hint">{{ $message->created_at->translatedFormat('Y年n月j日 H:i') }}</p>
        <div class="prose">{!! nl2br(e($message->body)) !!}</div>
    </article>
@endsection
