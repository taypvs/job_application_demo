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
            '応募を受け付けました',
            "{$job->company?->name}の「{$job->title}」への応募を受け付けました。\n選考状況は「応募状況」から確認できます。",
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
            '面接日程のご案内',
            implode("\n", [
                "{$job->company?->name}の「{$job->title}」について、面接日程が確定しました。",
                '',
                "日時: {$when}",
                "担当: {$interview->manager->name}（{$interview->manager->department->name} / {$interview->manager->title}）",
                "形式: {$interview->format}",
                '',
                '当日の接続情報は、前日までにこの受信箱でご連絡します。',
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
            ApplicationStatus::Passed => "「{$job->title}」の選考の結果、次のステップへお進みいただくこととなりました。\n担当者より改めてご連絡します。",
            ApplicationStatus::Rejected => "「{$job->title}」の選考の結果、今回は見送りとさせていただきます。\nご応募ありがとうございました。",
            default => "「{$job->title}」の選考ステータスが「{$label}」に更新されました。",
        };

        $this->send($application->user, '選考結果のご連絡', $body, $application);
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
