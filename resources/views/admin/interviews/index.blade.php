@extends('layouts.admin')

@section('title', '面接カレンダー')

@section('content')
    <header class="page-head row">
        <div>
            <h1>面接カレンダー</h1>
            <p class="lead">{{ $month->translatedFormat('Y年n月') }}</p>
        </div>
        <div class="filter-actions">
            <a class="btn btn-ghost" href="{{ route('admin.interviews.index', ['month' => $prev]) }}">前月</a>
            <a class="btn btn-ghost" href="{{ route('admin.interviews.index', ['month' => $next]) }}">次月</a>
        </div>
    </header>

    <div class="calendar">
        <div class="cal-head">
            @foreach (['日', '月', '火', '水', '木', '金', '土'] as $label)
                <span>{{ $label }}</span>
            @endforeach
        </div>
        @foreach ($weeks as $week)
            <div class="cal-week">
                @foreach ($week as $day)
                    <div @class(['cal-day', 'out' => ! $day['in_month']])>
                        <strong>{{ $day['date']->day }}</strong>
                        @foreach ($day['interviews'] as $interview)
                            <a href="{{ route('admin.applications.show', $interview->application) }}">
                                {{ $interview->starts_at->format('H:i') }}
                                {{ $interview->application->user->name }}
                                <small>{{ $interview->application->jobPosting->title }}</small>
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
@endsection
