@extends('layouts.user')

@section('title', '職務経歴書')

@section('content')
    <header class="page-head">
        <h1>職務経歴書</h1>
        <p class="lead">PDF、Word（.docx）、Excel（.xlsx / .xls）、テキストをS3へ保存します。Excelはすべてのシートを読み、本文を抽出してスキルを整理し、求人とのマッチングに使います。</p>
    </header>

    <section class="card">
        <form method="POST" action="{{ route('user.cv.store') }}" enctype="multipart/form-data" class="stack">
            @csrf
            <label>
                ファイル
                <input type="file" name="file" accept=".pdf,.docx,.txt,.xlsx,.xls,application/pdf,text/plain,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
            </label>
            <button class="btn btn-primary" type="submit" data-loading="アップロード中…">アップロード</button>
        </form>
    </section>

    @if ($cv)
        <section class="card">
            <h2>現在のファイル</h2>
            <p><a href="{{ route('cvs.download', $cv) }}">{{ $cv->original_name }}</a></p>
            <p class="hint">アップロード {{ $cv->created_at->translatedFormat('Y年n月j日 H:i') }} / 解析: {{ $cv->parse_engine === 'openai' ? 'OpenAI' : 'キーワード抽出' }}</p>
            @if ($cv->parsed_profile['summary'] ?? null)
                <h3>要約</h3>
                <p>{{ $cv->parsed_profile['summary'] }}</p>
            @endif
            <h3>抽出スキル</h3>
            <ul class="chips">
                @forelse ($cv->parsed_profile['skills'] ?? [] as $skill)
                    <li>{{ $skill['name'] }}@if(!empty($skill['years']))（{{ $skill['years'] }}年）@endif</li>
                @empty
                    <li class="muted">スキルを検出できませんでした</li>
                @endforelse
            </ul>
            @if (! $cv->extracted_text)
                <p class="hint">本文を抽出できませんでした。画像だけのPDFは解析できません。</p>
            @endif
        </section>
    @else
        <p class="empty">まだ職務経歴書がありません。</p>
    @endif
@endsection
