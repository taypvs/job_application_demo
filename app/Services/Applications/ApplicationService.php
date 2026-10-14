<?php

namespace App\Services\Applications;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\JobPosting;
use App\Models\User;
use App\Services\Mailbox\MailboxService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;

class ApplicationService
{
    public function __construct(private readonly MailboxService $mailbox) {}

    public function apply(User $user, JobPosting $job, ?string $message): Application
    {
        $cv = $user->currentCv();
        if (! $cv) {
            throw new RuntimeException('応募の前に職務経歴書をアップロードしてください。');
        }

        $exists = Application::query()
            ->where('user_id', $user->id)
            ->where('job_posting_id', $job->id)
            ->exists();

        if ($exists) {
            throw new RuntimeException('この求人には既に応募済みです。');
        }

        $application = Application::create([
            'user_id' => $user->id,
            'job_posting_id' => $job->id,
            'cv_id' => $cv->id,
            'status' => ApplicationStatus::Applied,
            'message' => $message,
            'applied_at' => now(),
        ]);

        $this->mailbox->notifyApplied($application);

        return $application;
    }

    public function forCandidate(User $user): LengthAwarePaginator
    {
        return Application::query()
            ->with(['jobPosting.company', 'interview.manager'])
            ->where('user_id', $user->id)
            ->latest('applied_at')
            ->paginate(12);
    }

    public function paginateForAdmin(): LengthAwarePaginator
    {
        return Application::query()
            ->with(['user', 'jobPosting.company', 'interview'])
            ->latest('id')
            ->paginate(15);
    }

    public function decide(Application $application, ApplicationStatus $status): void
    {
        if (! in_array($status, [ApplicationStatus::Passed, ApplicationStatus::Rejected], true)) {
            throw new RuntimeException('この操作では合否のみ更新できます。');
        }

        if (in_array($application->status, [ApplicationStatus::Passed, ApplicationStatus::Rejected], true)) {
            if ($application->status !== $status) {
                throw new RuntimeException('合否は確定済みのため、変更できません。');
            }

            return;
        }

        $application->update(['status' => $status]);
        $this->mailbox->notifyDecision($application->fresh(['user', 'jobPosting.company']));
    }
}
