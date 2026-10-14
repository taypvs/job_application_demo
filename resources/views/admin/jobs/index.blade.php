@extends('layouts.admin')

@section('title', '求人管理')

@section('content')
    <header class="page-head row">
        <div>
            <h1>求人管理</h1>
            <p class="lead">求人の作成、編集、公開状態の管理をします。</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.jobs.create') }}">新規作成</a>
    </header>

    <form method="GET" class="filters compact">
        <label>
            公開状態
            <select name="status" onchange="this.form.submit()">
                <option value="">すべて</option>
                @foreach (\App\Enums\JobStatus::cases() as $case)
                    <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </select>
        </label>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>タイトル</th>
                    <th>会社</th>
                    <th>職種</th>
                    <th>勤務地</th>
                    <th>年収</th>
                    <th>状態</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($jobs as $job)
                    <tr>
                        <td>{{ $job->title }}</td>
                        <td>{{ $job->company->name }}</td>
                        <td>{{ $job->position }}</td>
                        <td>{{ $job->location }}</td>
                        <td>{{ \App\Support\Format::salaryRange($job->salary_min, $job->salary_max) }}</td>
                        <td><span class="badge badge-{{ $job->status->value }}">{{ $job->status->label() }}</span></td>
                        <td><a href="{{ route('admin.jobs.edit', $job) }}">編集</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7">求人がありません。</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pager', ['paginator' => $jobs])
@endsection
