<?php

namespace App\Services\Mailbox;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Interview;
use App\Models\MailboxMessage;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MailboxService
{
    public function unreadCount(?User $user): int
    {
        if (! $user || ! $user->isCandidate()) {
            return 0;
        }

        return MailboxMessage::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    public function paginate(User $user): LengthAwarePaginator
    {
        return MailboxMessage::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->paginate(15);
    }

    public function open(User $user, MailboxMessage $message): MailboxMessage
    {
        if ($message->user_id !== $user->id) {
            abort(404);
        }

        if ($message->read_at === null) {
            $message->update(['read_at' => now()]);
        }

        return $message;
    }

    public function notifyApplied(Application $application): void
    {
        $application->loadMissing(['user', 'jobPosting.company']);
        $job = $application->jobPosting;

        $this->send(
            $application->user,
            __('messages.mailbox.applied_subject'),
            __('messages.mailbox.applied_body', [
                'company' => $job->company?->name,
                'title' => $job->title,
            ]),
            $application,
        );
    }

    public function notifyInterview(Application $application, Interview $interview): void
    {
        $application->loadMissing(['user', 'jobPosting.company', 'jobPosting.department']);
        $interview->loadMissing('manager.department');
        $job = $application->jobPosting;
        $when = $interview->starts_at->translatedFormat('Y年n月j日（D）H:i')
            .'〜'.$interview->ends_at->format('H:i');

        $this->send(
            $application->user,
            __('messages.mailbox.interview_subject'),
            __('messages.mailbox.interview_body', [
                'company' => $job->company?->name,
                'title' => $job->title,
                'when' => $when,
                'manager' => $interview->manager->name,
                'department' => $interview->manager->department->name,
                'role' => $interview->manager->title,
                'format' => $interview->format,
            ]),
            $application,
        );
    }

    public function notifyDecision(Application $application): void
    {
        $application->loadMissing(['user', 'jobPosting.company']);
        $job = $application->jobPosting;
        $label = $application->status->label();

        $body = match ($application->status) {
            ApplicationStatus::Passed => __('messages.mailbox.passed_body', ['title' => $job->title]),
            ApplicationStatus::Rejected => __('messages.mailbox.rejected_body', ['title' => $job->title]),
            default => __('messages.mailbox.updated_body', [
                'title' => $job->title,
                'status' => $label,
            ]),
        };

        $this->send($application->user, __('messages.mailbox.decision_subject'), $body, $application);
    }

    private function send(User $user, string $subject, string $body, ?Application $application = null): MailboxMessage
    {
        return MailboxMessage::create([
            'user_id' => $user->id,
            'application_id' => $application?->id,
            'subject' => $subject,
            'body' => $body,
        ]);
    }
}
