@extends('layouts.admin')

@section('title', '資料アップロード')

@section('content')
    <header class="page-head">
        <h1>資料アップロード</h1>
        <p class="lead">PDF、Word（.docx）、テキスト、またはURLを登録します。ファイルはS3へ保存し、本文を分割してPineconeへ送ります。候補者のチャットは、この資料を根拠に回答します。</p>
    </header>

    <form method="POST" action="{{ route('admin.documents.store') }}" enctype="multipart/form-data" class="card form-grid">
        @csrf
        <label>資料名
            <input type="text" name="title" value="{{ old('title') }}" required>
        </label>
        <label>種別
            <select name="source_type" required>
                <option value="file" @selected(old('source_type', 'file') === 'file')>ファイル</option>
                <option value="url" @selected(old('source_type') === 'url')>URL</option>
            </select>
        </label>
        <label>ファイル
            <input type="file" name="file" accept=".pdf,.docx,.txt,application/pdf,text/plain">
        </label>
        <label class="wide">URL
            <input type="url" name="url" value="{{ old('url') }}" placeholder="https://">
        </label>
        <div class="wide">
            <button class="btn btn-primary" type="submit" data-loading="登録中…">登録する</button>
        </div>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>資料名</th>
                    <th>種別</th>
                    <th>状態</th>
                    <th>分割数</th>
                    <th>登録日</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documents as $document)
                    <tr>
                        <td>
                            {{ $document->title }}
                            @if ($document->note)
                                <br><span class="hint">{{ $document->note }}</span>
                            @endif
                        </td>
                        <td>{{ $document->source_type }}</td>
                        <td>{{ $document->status->label() }}</td>
                        <td>{{ $document->chunk_count }}</td>
                        <td>{{ $document->created_at->translatedFormat('n月j日 H:i') }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.documents.destroy', $document) }}" onsubmit="return confirm('この資料をS3とPineconeから削除します。よろしいですか？');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" type="submit">削除</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">資料はまだありません。</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pager', ['paginator' => $documents])
@endsection
