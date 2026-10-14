@extends('layouts.admin')

@section('title', '応募者')

@section('content')
    <header class="page-head">
        <h1>応募者</h1>
        <p class="lead">応募内容を確認し、面接日程の確定や合否連絡ができます。</p>
    </header>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>候補者</th>
                    <th>求人</th>
                    <th>会社</th>
                    <th>ステータス</th>
                    <th>応募日</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($applications as $application)
                    <tr>
                        <td>{{ $application->user->name }}<br><span class="hint">{{ $application->user->email }}</span></td>
                        <td>{{ $application->jobPosting->title }}</td>
                        <td>{{ $application->jobPosting->company->name }}</td>
                        <td><span class="badge badge-{{ $application->status->value }}">{{ $application->status->label() }}</span></td>
                        <td>{{ $application->applied_at->translatedFormat('Y年n月j日') }}</td>
                        <td><a href="{{ route('admin.applications.show', $application) }}">詳細</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6">応募はまだありません。</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pager', ['paginator' => $applications])
@endsection
