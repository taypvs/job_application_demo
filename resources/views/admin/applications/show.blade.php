@extends('layouts.admin')

@section('title', $application->user->name)

@section('content')
    <p class="crumb"><a href="{{ route('admin.applications.index') }}">応募者</a> / {{ $application->user->name }}</p>
    <header class="page-head">
        <h1>{{ $application->user->name }}</h1>
        <p class="lead">{{ $application->user->email }} / {{ $application->jobPosting->title }}</p>
        <p>ステータス: <span class="badge badge-{{ $application->status->value }}">{{ $application->status->label() }}</span></p>
    </header>

    <div class="detail-grid">
        <article class="card prose">
            <h2>応募内容</h2>
            <dl class="facts">
                <div><dt>求人</dt><dd>{{ $application->jobPosting->title }}（{{ $application->jobPosting->position }}）</dd></div>
                <div><dt>会社</dt><dd>{{ $application->jobPosting->company->name }}</dd></div>
                <div><dt>担当部署</dt><dd>{{ $application->jobPosting->department->name }}</dd></div>
                <div><dt>応募日</dt><dd>{{ $application->applied_at->translatedFormat('Y年n月j日 H:i') }}</dd></div>
            </dl>
            <h2>志望動機</h2>
            <p>{{ $application->message ?: '未記入' }}</p>

            <h2>職務経歴書</h2>
            @if ($application->cv)
                <p><a href="{{ route('cvs.download', $application->cv) }}">{{ $application->cv->original_name }}</a></p>
                @if ($application->cv->parsed_profile['summary'] ?? null)
                    <p>{{ $application->cv->parsed_profile['summary'] }}</p>
                @endif
                <ul class="chips">
                    @foreach ($application->cv->parsed_profile['skills'] ?? [] as $skill)
                        <li>{{ $skill['name'] }}</li>
                    @endforeach
                </ul>
            @else
                <p>職務経歴書は未登録です。</p>
            @endif
        </article>

        <aside class="card stack">
            <h2>面接設定</h2>
            @if ($application->interview)
                <p>{{ $application->interview->starts_at->translatedFormat('Y年n月j日（D）H:i') }}〜{{ $application->interview->ends_at->format('H:i') }}</p>
                <p>{{ $application->interview->manager->name }}（{{ $application->interview->manager->department->name }}）</p>
                <p class="hint">形式: {{ $application->interview->format }}</p>
            @elseif (! in_array($application->status, [\App\Enums\ApplicationStatus::Passed, \App\Enums\ApplicationStatus::Rejected], true))
                <form method="POST" action="{{ route('admin.applications.schedule', $application) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">面接日程を確定する</button>
                </form>
                <p class="hint">担当部署のマネージャーの勤務時間から、既存の予定を除いた最も早い枠を割り当て、候補者の受信箱へ通知します。</p>
            @else
                <p class="hint">選考が終了しているため、面接は設定できません。</p>
            @endif

            <h2>合否</h2>
            @php
                $decisionLocked = in_array($application->status, [\App\Enums\ApplicationStatus::Passed, \App\Enums\ApplicationStatus::Rejected], true);
            @endphp
            <form method="POST" action="{{ route('admin.applications.decide', $application) }}">
                @csrf
                <input type="hidden" name="status" value="passed">
                <button class="btn btn-primary" type="submit" @disabled($decisionLocked && $application->status !== \App\Enums\ApplicationStatus::Passed)>合格にする</button>
            </form>
            <form method="POST" action="{{ route('admin.applications.decide', $application) }}">
                @csrf
                <input type="hidden" name="status" value="rejected">
                <button class="btn btn-danger" type="submit" @disabled($decisionLocked && $application->status !== \App\Enums\ApplicationStatus::Rejected)>不合格にする</button>
            </form>
            @if ($decisionLocked)
                <p class="hint">合否は確定しているため、変更できません。</p>
            @endif
        </aside>
    </div>
@endsection
