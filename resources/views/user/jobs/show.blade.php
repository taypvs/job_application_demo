@extends('layouts.user')

@section('title', $job->title)

@section('content')
    <p class="crumb"><a href="{{ route('user.jobs.index') }}">求人一覧</a> / {{ $job->title }}</p>
    <header class="page-head">
        <p class="company">{{ $job->company->name }}</p>
        <h1>{{ $job->title }}</h1>
        <p class="meta">
            <span>{{ $job->position }}</span>
            <span>{{ $job->employment_type }}</span>
            <span>{{ $job->location }}</span>
            <span>{{ $job->remote_policy->label() }}</span>
        </p>
        <p class="salary">{{ \App\Support\Format::salaryRange($job->salary_min, $job->salary_max) }}</p>
    </header>

    <div class="detail-grid">
        <article class="card prose">
            <h2>概要</h2>
            <p>{!! nl2br(e($job->summary)) !!}</p>

            <h2>仕事内容</h2>
            <p>{!! nl2br(e($job->description)) !!}</p>

            @if ($job->background)
                <h2>募集背景</h2>
                <p>{!! nl2br(e($job->background)) !!}</p>
            @endif

            @if ($job->requirements)
                <h2>応募資格</h2>
                <p>{!! nl2br(e($job->requirements)) !!}</p>
            @endif

            <h2>必須スキル</h2>
            <ul class="chips">
                @forelse ($job->skills->where('category', \App\Enums\SkillCategory::Required) as $skill)
                    <li>{{ $skill->name }}</li>
                @empty
                    <li class="muted">指定なし</li>
                @endforelse
            </ul>

            <h2>歓迎スキル</h2>
            <ul class="chips chips-soft">
                @forelse ($job->skills->where('category', \App\Enums\SkillCategory::NiceToHave) as $skill)
                    <li>{{ $skill->name }}</li>
                @empty
                    <li class="muted">指定なし</li>
                @endforelse
            </ul>

            @if ($job->appeal)
                <h2>この仕事の魅力</h2>
                <p>{!! nl2br(e($job->appeal)) !!}</p>
            @endif

            <h2>勤務条件</h2>
            <dl class="facts">
                <div><dt>勤務時間</dt><dd>{{ $job->working_hours }}</dd></div>
                <div><dt>勤務地</dt><dd>{{ $job->location }}</dd></div>
                <div><dt>働き方</dt><dd>{{ $job->remote_policy->label() }}</dd></div>
                <div><dt>給与</dt><dd>{{ \App\Support\Format::salaryRange($job->salary_min, $job->salary_max) }}@if($job->salary_note)（{{ $job->salary_note }}）@endif</dd></div>
                <div><dt>休日・休暇</dt><dd>{!! nl2br(e($job->holidays)) !!}</dd></div>
                <div><dt>待遇</dt><dd>{!! nl2br(e($job->benefits)) !!}</dd></div>
                <div><dt>試用期間</dt><dd>{{ $job->probation }}</dd></div>
                <div><dt>担当部署</dt><dd>{{ $job->department->name }}</dd></div>
            </dl>

            @if ($job->selection_process)
                <h2>選考プロセス</h2>
                <p>{!! nl2br(e($job->selection_process)) !!}</p>
            @endif

            <h2>会社情報</h2>
            <p>{{ $job->company->name }} / {{ $job->company->industry }} / 設立 {{ $job->company->founded_year }}年 / 従業員 {{ number_format($job->company->employee_count) }}名</p>
            <p>本社: {{ $job->company->headquarters }}</p>
            @if ($job->company->website)
                <p><a href="{{ $job->company->website }}" target="_blank" rel="noopener">{{ $job->company->website }}</a></p>
            @endif
            <p>{!! nl2br(e($job->company_note ?: $job->company->description)) !!}</p>
        </article>

        <aside class="card apply-box">
            <h2>応募</h2>
            @if ($application)
                <p>ステータス: <span class="badge badge-{{ $application->status->value }}">{{ $application->status->label() }}</span></p>
                <p class="hint">応募日 {{ $application->applied_at->translatedFormat('Y年n月j日') }}</p>
            @else
                <form method="POST" action="{{ route('user.jobs.apply', $job) }}" class="stack">
                    @csrf
                    <label>
                        志望動機（任意）
                        <textarea name="message" rows="5">{{ old('message') }}</textarea>
                    </label>
                    <button class="btn btn-primary" type="submit">応募する</button>
                </form>
                @if (! $cv)
                    <p class="hint">応募には職務経歴書のアップロードが必要です。<a href="{{ route('user.cv.index') }}">アップロードへ</a></p>
                @endif
            @endif

            <form method="POST" action="{{ route('user.jobs.analyze', $job) }}">
                @csrf
                <button class="btn btn-ghost" type="submit" data-loading="分析中…">AIマッチング分析</button>
            </form>
            <p class="hint">アップロード済みの職務経歴書と、この求人の必須・歓迎スキルを比較します。</p>

            @if ($analysis)
                <div class="analysis">
                    <div class="score-ring" style="--score: {{ $analysis->score }}">
                        <div>
                            <strong>{{ $analysis->score }}%</strong>
                            <span>マッチ度</span>
                        </div>
                    </div>
                    <p class="hint">
                        {{ $analysis->engine === 'openai' ? 'OpenAIによる分析' : 'キーワード照合（OpenAI未設定）' }}
                        / {{ $analysis->created_at->translatedFormat('n月j日 H:i') }}
                    </p>
                    <p>{{ $analysis->summary }}</p>
                    <h3>強み</h3>
                    <ul>@foreach ($analysis->strengths as $item)<li>{{ $item }}</li>@endforeach</ul>
                    <h3>不足</h3>
                    <ul>@foreach ($analysis->weaknesses as $item)<li>{{ $item }}</li>@endforeach</ul>
                    <h3>改善するとよい点</h3>
                    <ul>@foreach ($analysis->improvements as $item)<li>{{ $item }}</li>@endforeach</ul>
                </div>
            @endif
        </aside>
    </div>
@endsection
