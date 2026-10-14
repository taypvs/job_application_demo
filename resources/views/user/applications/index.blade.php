@extends('layouts.user')

@section('title', '応募状況')

@section('content')
    <header class="page-head">
        <h1>応募状況</h1>
        <p class="lead">応募済み、面接予定、不合格、合格を確認できます。</p>
    </header>

    @if ($applications->isEmpty())
        <p class="empty">まだ応募はありません。<a href="{{ route('user.jobs.index') }}">求人一覧</a>から応募できます。</p>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>求人</th>
                        <th>会社</th>
                        <th>ステータス</th>
                        <th>応募日</th>
                        <th>面接</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($applications as $application)
                        <tr>
                            <td><a href="{{ route('user.jobs.show', $application->jobPosting) }}">{{ $application->jobPosting->title }}</a></td>
                            <td>{{ $application->jobPosting->company->name }}</td>
                            <td><span class="badge badge-{{ $application->status->value }}">{{ $application->status->label() }}</span></td>
                            <td>{{ $application->applied_at->translatedFormat('Y年n月j日') }}</td>
                            <td>
                                @if ($application->interview)
                                    {{ $application->interview->starts_at->translatedFormat('n月j日 H:i') }}
                                    {{ $application->interview->manager->name }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('partials.pager', ['paginator' => $applications])
    @endif
@endsection
