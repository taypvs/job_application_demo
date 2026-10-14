<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DecideApplicationRequest;
use App\Models\Application;
use App\Services\Applications\ApplicationService;
use App\Services\Interviews\InterviewSchedulerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;

class ApplicationController extends Controller
{
    public function __construct(
        private readonly ApplicationService $applications,
        private readonly InterviewSchedulerService $scheduler,
    ) {}

    public function index(): View
    {
        return view('admin.applications.index', [
            'applications' => $this->applications->paginateForAdmin(),
        ]);
    }

    public function show(Application $application): View
    {
        $application->load([
            'user',
            'cv',
            'jobPosting.company',
            'jobPosting.department',
            'jobPosting.skills',
            'interview.manager.department',
        ]);

        return view('admin.applications.show', [
            'application' => $application,
        ]);
    }

    public function schedule(Application $application): RedirectResponse
    {
        try {
            $interview = $this->scheduler->assign($application);
        } catch (RuntimeException $e) {
            return back()->withErrors(['schedule' => $e->getMessage()]);
        }

        return back()->with(
            'status',
            '面接を設定し、候補者の受信箱へ通知しました。'.$interview->starts_at->translatedFormat('n月j日 H:i').' / '.$interview->manager->name,
        );
    }

    public function decide(DecideApplicationRequest $request, Application $application): RedirectResponse
    {
        $status = ApplicationStatus::from($request->validated('status'));
        $alreadyDecided = $application->status === $status;

        try {
            $this->applications->decide($application, $status);
        } catch (RuntimeException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with(
            'status',
            $alreadyDecided
                ? '合否は確定済みです。'
                : '選考ステータスを更新し、候補者へ通知しました。',
        );
    }
}
