<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\InterviewStatus;
use App\Enums\JobStatus;
use App\Enums\RemotePolicy;
use App\Enums\SkillCategory;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Company;
use App\Models\Cv;
use App\Models\Department;
use App\Models\Interview;
use App\Models\JobPosting;
use App\Models\Manager;
use App\Models\ManagerAvailability;
use App\Models\MatchAnalysis;
use App\Models\User;
use App\Services\Interviews\InterviewSchedulerService;
use App\Services\Matching\JobMatchingService;
use App\Support\SkillMatcher;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruitingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_scheduler_skips_a_busy_slot_and_notifies_the_candidate(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-14 09:00:00', 'Asia/Tokyo'));

        $department = Department::create(['code' => 'DEV', 'name' => '開発部']);
        $company = Company::create(['name' => '株式会社カナル']);
        $manager = Manager::create([
            'department_id' => $department->id,
            'name' => '佐藤 健一',
            'email' => 'sato@example.com',
            'title' => 'マネージャー',
        ]);
        ManagerAvailability::create([
            'manager_id' => $manager->id,
            'weekday' => 4,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        $job = $this->job($company, $department);
        $busyUser = User::factory()->create();
        $target = User::factory()->create();

        $busy = Application::create([
            'user_id' => $busyUser->id,
            'job_posting_id' => $job->id,
            'status' => ApplicationStatus::Interview,
            'applied_at' => now(),
        ]);
        Interview::create([
            'application_id' => $busy->id,
            'manager_id' => $manager->id,
            'starts_at' => '2026-10-15 10:00:00',
            'ends_at' => '2026-10-15 11:00:00',
            'status' => InterviewStatus::Scheduled,
        ]);

        $application = Application::create([
            'user_id' => $target->id,
            'job_posting_id' => $job->id,
            'status' => ApplicationStatus::Applied,
            'applied_at' => now(),
        ]);

        $interview = app(InterviewSchedulerService::class)->assign($application);

        $this->assertSame('2026-10-15 11:00:00', $interview->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame($manager->id, $interview->manager_id);
        $this->assertSame(ApplicationStatus::Interview, $application->fresh()->status);
        $this->assertDatabaseHas('mailbox_messages', [
            'user_id' => $target->id,
            'subject' => '面接日程のご案内',
        ]);
    }

    public function test_short_skill_names_do_not_match_inside_longer_words(): void
    {
        $this->assertFalse(SkillMatcher::contains('MySQLの設計を担当', 'SQL'));
        $this->assertFalse(SkillMatcher::contains('JavaScriptで画面を実装', 'Java'));
        $this->assertTrue(SkillMatcher::contains('SQLで集計しました', 'SQL'));
    }

    public function test_local_matching_uses_skill_overlap_without_openai(): void
    {
        config(['services.openai.key' => null]);

        $department = Department::create(['code' => 'DEV', 'name' => '開発部']);
        $company = Company::create(['name' => '株式会社カナル']);
        $job = $this->job($company, $department);
        $job->skills()->create(['name' => 'PHP', 'category' => SkillCategory::Required, 'sort_order' => 1]);
        $job->skills()->create(['name' => 'AWS', 'category' => SkillCategory::Required, 'sort_order' => 2]);
        $job->skills()->create(['name' => 'Docker', 'category' => SkillCategory::NiceToHave, 'sort_order' => 3]);

        $user = User::factory()->create(['role' => UserRole::Candidate]);
        Cv::create([
            'user_id' => $user->id,
            'original_name' => 'cv.txt',
            's3_key' => 'cvs/test.txt',
            'extracted_text' => 'PHPとLaravel、Dockerを使ったWeb開発を担当しました。',
            'parsed_profile' => ['summary' => 'PHP開発', 'skills' => [['name' => 'PHP']]],
            'parse_engine' => 'local',
            'is_active' => true,
        ]);

        $analysis = app(JobMatchingService::class)->analyze($user, $job->fresh('skills'));

        $this->assertSame('local', $analysis->engine);
        $this->assertSame(63, $analysis->score);
        $this->assertTrue(collect($analysis->strengths)->contains(fn (string $line) => str_contains($line, 'PHP')));
        $this->assertTrue(collect($analysis->weaknesses)->contains(fn (string $line) => str_contains($line, 'AWS')));
    }

    public function test_job_page_shows_analysis_only_immediately_after_the_click(): void
    {
        config(['services.openai.key' => null]);
        $this->withoutMiddleware(PreventRequestForgery::class);

        $department = Department::create(['code' => 'DEV', 'name' => '開発部']);
        $company = Company::create(['name' => '株式会社カナル']);
        $job = $this->job($company, $department);
        $job->skills()->create(['name' => 'PHP', 'category' => SkillCategory::Required, 'sort_order' => 1]);

        $user = User::factory()->create(['role' => UserRole::Candidate]);
        $cv = Cv::create([
            'user_id' => $user->id,
            'original_name' => 'cv.txt',
            's3_key' => 'cvs/test.txt',
            'extracted_text' => 'PHPでWeb開発を担当しました。',
            'parsed_profile' => ['summary' => 'PHP開発', 'skills' => [['name' => 'PHP']]],
            'parse_engine' => 'local',
            'is_active' => true,
        ]);
        MatchAnalysis::create([
            'user_id' => $user->id,
            'job_posting_id' => $job->id,
            'cv_id' => $cv->id,
            'score' => 10,
            'summary' => '前回の分析結果',
            'strengths' => ['古い強み'],
            'weaknesses' => [],
            'improvements' => [],
            'engine' => 'local',
        ]);

        $this->actingAs($user)
            ->get(route('user.jobs.show', $job))
            ->assertOk()
            ->assertDontSee('前回の分析結果');

        $this->actingAs($user)
            ->post(route('user.jobs.analyze', $job))
            ->assertRedirect(route('user.jobs.show', $job));

        $this->actingAs($user)
            ->get(route('user.jobs.show', $job))
            ->assertOk()
            ->assertSee('キーワード照合による参考値')
            ->assertDontSee('前回の分析結果');

        $this->actingAs($user)
            ->get(route('user.jobs.show', $job))
            ->assertOk()
            ->assertDontSee('キーワード照合による参考値')
            ->assertDontSee('前回の分析結果');
    }

    public function test_admin_decision_stays_fixed_and_the_page_hides_matching_analysis(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $department = Department::create(['code' => 'DEV', 'name' => '開発部']);
        $company = Company::create(['name' => '株式会社カナル']);
        $job = $this->job($company, $department);
        $candidate = User::factory()->create(['role' => UserRole::Candidate]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $application = Application::create([
            'user_id' => $candidate->id,
            'job_posting_id' => $job->id,
            'status' => ApplicationStatus::Interview,
            'applied_at' => now(),
        ]);
        MatchAnalysis::create([
            'user_id' => $candidate->id,
            'job_posting_id' => $job->id,
            'score' => 80,
            'summary' => '管理画面に出してはいけない分析',
            'strengths' => [],
            'weaknesses' => [],
            'improvements' => [],
            'engine' => 'local',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.applications.show', $application))
            ->assertOk()
            ->assertDontSee('マッチング分析')
            ->assertDontSee('管理画面に出してはいけない分析')
            ->assertSee('class="btn btn-primary" type="submit"', false)
            ->assertSee('class="btn btn-danger" type="submit"', false);

        $this->actingAs($admin)
            ->post(route('admin.applications.decide', $application), ['status' => 'passed'])
            ->assertRedirect();

        $passed = $this->actingAs($admin)->get(route('admin.applications.show', $application));
        $passed->assertOk()
            ->assertSee('合格')
            ->assertSee('class="btn btn-danger" type="submit" disabled', false)
            ->assertDontSee('class="btn btn-primary" type="submit" disabled', false)
            ->assertDontSee('マッチング分析');

        $this->actingAs($admin)
            ->from(route('admin.applications.show', $application))
            ->post(route('admin.applications.decide', $application), ['status' => 'rejected'])
            ->assertRedirect()
            ->assertSessionHasErrors('status');

        $this->assertSame(ApplicationStatus::Passed, $application->fresh()->status);
    }

    private function job(Company $company, Department $department): JobPosting
    {
        return JobPosting::create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'title' => 'テスト求人',
            'position' => 'エンジニア',
            'employment_type' => '正社員',
            'summary' => '概要',
            'description' => '内容',
            'working_hours' => '9:30〜18:30',
            'location' => '東京都港区',
            'remote_policy' => RemotePolicy::Hybrid,
            'salary_min' => 5000000,
            'salary_max' => 8000000,
            'status' => JobStatus::Published,
            'published_at' => now(),
        ]);
    }
}
