@extends('layouts.user')

@section('title', '求人一覧')

@section('content')
    <header class="page-head">
        <h1>求人一覧</h1>
        <p class="lead">職種、勤務地、年収、キーワードで絞り込めます。</p>
    </header>

    <form method="GET" action="{{ route('user.jobs.index') }}" class="filters">
        <label>
            職種
            <select name="position">
                <option value="">すべて</option>
                @foreach ($options['positions'] as $position)
                    <option value="{{ $position }}" @selected(($filters['position'] ?? '') === $position)>{{ $position }}</option>
                @endforeach
            </select>
        </label>
        <label>
            勤務地
            <select name="location">
                <option value="">すべて</option>
                @foreach ($options['locations'] as $location)
                    <option value="{{ $location }}" @selected(($filters['location'] ?? '') === $location)>{{ $location }}</option>
                @endforeach
            </select>
        </label>
        <label>
            年収
            <select name="salary">
                <option value="">指定なし</option>
                @foreach ($salaryBands as $key => $band)
                    <option value="{{ $key }}" @selected(($filters['salary'] ?? '') === $key)>{{ $band['label'] }}</option>
                @endforeach
            </select>
        </label>
        <label>
            キーワード
            <input type="search" name="keyword" value="{{ $filters['keyword'] ?? '' }}" placeholder="タイトル、会社、仕事内容">
        </label>
        <div class="filter-actions">
            <button class="btn btn-primary" type="submit">絞り込む</button>
            <a class="btn btn-ghost" href="{{ route('user.jobs.index') }}">クリア</a>
        </div>
    </form>

    @if ($jobs->isEmpty())
        <p class="empty">条件に一致する求人がありません。</p>
    @else
        <div class="job-list">
            @foreach ($jobs as $job)
                <a class="job-card" href="{{ route('user.jobs.show', $job) }}">
                    <p class="company">{{ $job->company->name }}</p>
                    <h2>{{ $job->title }}</h2>
                    <p class="meta">
                        <span>{{ $job->position }}</span>
                        <span>{{ $job->location }}</span>
                        <span>{{ $job->employment_type }}</span>
                        <span>{{ $job->remote_policy->label() }}</span>
                    </p>
                    <p class="salary">{{ \App\Support\Format::salaryRange($job->salary_min, $job->salary_max) }}</p>
                </a>
            @endforeach
        </div>
        @include('partials.pager', ['paginator' => $jobs])
    @endif
@endsection
