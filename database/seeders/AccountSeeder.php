<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\InterviewStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Interview;
use App\Models\JobPosting;
use App\Models\MailboxMessage;
use App\Models\Manager;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@example.com'], [
            'name' => '管理者1',
            'password' => 'password',
            'role' => UserRole::Admin,
        ]);

        User::updateOrCreate(['email' => 'user@example.com'], [
            'name' => 'ユーザー1',
            'password' => 'password',
            'role' => UserRole::Candidate,
        ]);

        $candidate = User::updateOrCreate(['email' => 'yamada@example.com'], [
            'name' => 'ユーザー2',
            'password' => 'password',
            'role' => UserRole::Candidate,
        ]);

        $job = JobPosting::where('title', 'Webシステムエンジニア')->firstOrFail();
        $manager = Manager::where('email', 'sato.kenichi@canal.example.jp')->firstOrFail();

        $application = Application::firstOrCreate(
            ['user_id' => $candidate->id, 'job_posting_id' => $job->id],
            [
                'status' => ApplicationStatus::Interview,
                'message' => 'Laravelでの業務システム開発を5年行ってきました。貴社のプロダクト開発に参加したいです。',
                'applied_at' => now()->subDays(2),
            ],
        );

        $start = now()->addDay()->startOfDay();
        while ($start->isWeekend()) {
            $start->addDay();
        }
        $start->setTime(10, 0);

        $interview = Interview::firstOrCreate(
            ['application_id' => $application->id],
            [
                'manager_id' => $manager->id,
                'starts_at' => $start,
                'ends_at' => $start->copy()->addHour(),
                'format' => 'オンライン',
                'status' => InterviewStatus::Scheduled,
            ],
        );

        if ($application->status !== ApplicationStatus::Interview) {
            $application->update(['status' => ApplicationStatus::Interview]);
        }

        MailboxMessage::firstOrCreate(
            ['user_id' => $candidate->id, 'application_id' => $application->id, 'subject' => '面接日程のご案内'],
            [
                'body' => implode("\n", [
                    '株式会社カナルの「Webシステムエンジニア」について、面接日程が確定しました。',
                    '',
                    '日時: '.$interview->starts_at->translatedFormat('Y年n月j日（D）H:i').'〜'.$interview->ends_at->format('H:i'),
                    '担当: '.$manager->name.'（開発部 / エンジニアリングマネージャー）',
                    '形式: オンライン',
                ]),
            ],
        );
    }
}
