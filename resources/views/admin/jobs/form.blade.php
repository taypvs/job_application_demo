@extends('layouts.admin')

@section('title', $job ? '求人を編集' : '求人を作成')

@php
    $requiredText = old('required_skills_text', $job
        ? $job->skills->where('category', \App\Enums\SkillCategory::Required)->pluck('name')->implode("\n")
        : '');
    $niceText = old('nice_to_have_skills_text', $job
        ? $job->skills->where('category', \App\Enums\SkillCategory::NiceToHave)->pluck('name')->implode("\n")
        : '');
@endphp

@section('content')
    <p class="crumb"><a href="{{ route('admin.jobs.index') }}">求人管理</a> / {{ $job ? '編集' : '新規作成' }}</p>
    <header class="page-head">
        <h1>{{ $job ? '求人を編集' : '求人を作成' }}</h1>
    </header>

    <form method="POST" action="{{ $job ? route('admin.jobs.update', $job) : route('admin.jobs.store') }}" class="card form-grid">
        @csrf
        @if ($job) @method('PUT') @endif

        <label>会社
            <select name="company_id" required>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) old('company_id', $job?->company_id) === (string) $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
        </label>
        <label>担当部署
            <select name="department_id" required>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) old('department_id', $job?->department_id) === (string) $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
        </label>
        <label>募集タイトル
            <input type="text" name="title" value="{{ old('title', $job?->title) }}" required>
        </label>
        <label>職種
            <input type="text" name="position" value="{{ old('position', $job?->position) }}" required>
        </label>
        <label>雇用形態
            <select name="employment_type" required>
                @foreach ($employmentTypes as $type)
                    <option value="{{ $type }}" @selected(old('employment_type', $job?->employment_type) === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </label>
        <label>働き方
            <select name="remote_policy" required>
                @foreach (\App\Enums\RemotePolicy::cases() as $policy)
                    <option value="{{ $policy->value }}" @selected(old('remote_policy', $job?->remote_policy?->value ?? 'hybrid') === $policy->value)>{{ $policy->label() }}</option>
                @endforeach
            </select>
        </label>
        <label>勤務地
            <input type="text" name="location" value="{{ old('location', $job?->location) }}" required>
        </label>
        <label>勤務時間
            <input type="text" name="working_hours" value="{{ old('working_hours', $job?->working_hours) }}" required>
        </label>
        <label>年収下限（万円）
            <input type="number" name="salary_min_man" min="0" value="{{ old('salary_min_man', $job ? (int) round($job->salary_min / 10000) : '') }}" required>
        </label>
        <label>年収上限（万円）
            <input type="number" name="salary_max_man" min="0" value="{{ old('salary_max_man', $job ? (int) round($job->salary_max / 10000) : '') }}" required>
        </label>
        <label>給与補足
            <input type="text" name="salary_note" value="{{ old('salary_note', $job?->salary_note) }}">
        </label>
        <label>公開状態
            <select name="status" required>
                @foreach (\App\Enums\JobStatus::cases() as $case)
                    <option value="{{ $case->value }}" @selected(old('status', $job?->status?->value ?? 'published') === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </select>
        </label>
        <label class="wide">概要
            <textarea name="summary" rows="3" required>{{ old('summary', $job?->summary) }}</textarea>
        </label>
        <label class="wide">仕事内容
            <textarea name="description" rows="6" required>{{ old('description', $job?->description) }}</textarea>
        </label>
        <label class="wide">募集背景
            <textarea name="background" rows="3">{{ old('background', $job?->background) }}</textarea>
        </label>
        <label class="wide">応募資格
            <textarea name="requirements" rows="4">{{ old('requirements', $job?->requirements) }}</textarea>
        </label>
        <label>必須スキル（1行に1つ）
            <textarea name="required_skills_text" rows="6">{{ $requiredText }}</textarea>
        </label>
        <label>歓迎スキル（1行に1つ）
            <textarea name="nice_to_have_skills_text" rows="6">{{ $niceText }}</textarea>
        </label>
        <label class="wide">この仕事の魅力
            <textarea name="appeal" rows="3">{{ old('appeal', $job?->appeal) }}</textarea>
        </label>
        <label class="wide">会社情報（空なら会社マスタの説明を表示）
            <textarea name="company_note" rows="3">{{ old('company_note', $job?->company_note) }}</textarea>
        </label>
        <label class="wide">待遇・福利厚生
            <textarea name="benefits" rows="3">{{ old('benefits', $job?->benefits) }}</textarea>
        </label>
        <label class="wide">休日・休暇
            <textarea name="holidays" rows="2">{{ old('holidays', $job?->holidays) }}</textarea>
        </label>
        <label>試用期間
            <input type="text" name="probation" value="{{ old('probation', $job?->probation) }}">
        </label>
        <label class="wide">選考プロセス
            <textarea name="selection_process" rows="3">{{ old('selection_process', $job?->selection_process) }}</textarea>
        </label>
        <div class="wide">
            <button class="btn btn-primary" type="submit">保存</button>
        </div>
    </form>
@endsection
