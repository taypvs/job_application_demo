<?php

namespace App\Http\Controllers\User;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\ApplyJobRequest;
use App\Http\Requests\User\JobFilterRequest;
use App\Models\Application;
use App\Models\JobPosting;
use App\Models\MatchAnalysis;
use App\Models\User;
use App\Services\Applications\ApplicationService;
use App\Services\Jobs\JobPostingService;
use App\Services\Jobs\JobSearchService;
use App\Services\Matching\JobMatchingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;

class JobController extends Controller
{
    public function __construct(
        private readonly JobSearchService $search,
        private readonly JobPostingService $jobs,
        private readonly ApplicationService $applications,
        private readonly JobMatchingService $matching,
    ) {}

    public function index(JobFilterRequest $request): View
    {
        $filters = $request->validated();

        return view('user.jobs.index', [
            'jobs' => $this->search->search($filters),
            'filters' => $filters,
            'options' => $this->search->options(),
            'salaryBands' => config('recruiting.salary_bands'),
        ]);
    }

    public function show(JobPosting $jobPosting): View
    {
        $user = request()->user();
        $application = Application::query()
            ->where('user_id', $user->id)
            ->where('job_posting_id', $jobPosting->id)
            ->first();

        if ($jobPosting->status !== JobStatus::Published && $application === null) {
            abort(404);
        }

        $jobPosting->load(['company', 'department', 'skills']);

        return view('user.jobs.show', [
            'job' => $jobPosting,
            'cv' => $user->currentCv(),
            'application' => $application,
            'analysis' => $this->justAnalyzed($user, $jobPosting),
        ]);
    }

    public function apply(ApplyJobRequest $request, JobPosting $jobPosting): RedirectResponse
    {
        try {
            $this->applications->apply(
                $request->user(),
                $this->jobs->findPublished($jobPosting),
                $request->validated('message'),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['apply' => $e->getMessage()])->withInput();
        }

        return back()->with('status', '応募を受け付けました。受信箱にも控えを送っています。');
    }

    public function analyze(JobPosting $jobPosting): RedirectResponse
    {
        try {
            $analysis = $this->matching->analyze(request()->user(), $this->jobs->findPublished($jobPosting));
        } catch (RuntimeException $e) {
            return back()->withErrors(['analyze' => $e->getMessage()]);
        }

        return back()
            ->with('status', 'マッチング分析が完了しました。')
            ->with('match_analysis_id', $analysis->id);
    }

    private function justAnalyzed(User $user, JobPosting $job): ?MatchAnalysis
    {
        $id = session('match_analysis_id');
        if (! $id) {
            return null;
        }

        return MatchAnalysis::query()
            ->whereKey($id)
            ->where('user_id', $user->id)
            ->where('job_posting_id', $job->id)
            ->first();
    }
}
