<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveJobRequest;
use App\Models\JobPosting;
use App\Services\Jobs\JobPostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobController extends Controller
{
    public function __construct(private readonly JobPostingService $jobs) {}

    public function index(Request $request): View
    {
        return view('admin.jobs.index', [
            'jobs' => $this->jobs->paginate($request->query('status')),
            'status' => $request->query('status'),
        ]);
    }

    public function create(): View
    {
        return view('admin.jobs.form', array_merge($this->jobs->formOptions(), [
            'job' => null,
        ]));
    }

    public function store(SaveJobRequest $request): RedirectResponse
    {
        $job = $this->jobs->save(null, $request->validated());

        return redirect()
            ->route('admin.jobs.edit', $job)
            ->with('status', '求人を作成しました。');
    }

    public function edit(JobPosting $jobPosting): View
    {
        $jobPosting->load(['skills', 'company', 'department']);

        return view('admin.jobs.form', array_merge($this->jobs->formOptions(), [
            'job' => $jobPosting,
        ]));
    }

    public function update(SaveJobRequest $request, JobPosting $jobPosting): RedirectResponse
    {
        $this->jobs->save($jobPosting, $request->validated());

        return redirect()
            ->route('admin.jobs.edit', $jobPosting)
            ->with('status', '求人を更新しました。');
    }
}
