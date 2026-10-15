<?php

namespace App\Services\Matching;

use App\Enums\JobStatus;
use App\Enums\SkillCategory;
use App\Models\Cv;
use App\Models\JobPosting;
use App\Models\JobSkill;
use App\Models\MatchAnalysis;
use App\Models\User;
use App\Services\Ai\OpenAiClient;
use App\Services\Ai\PromptLibrary;
use App\Support\SkillMatcher;
use Illuminate\Support\Collection;
use RuntimeException;

class JobMatchingService
{
    public function __construct(
        private readonly OpenAiClient $openai,
        private readonly PromptLibrary $prompts,
    ) {}

    public function analyze(User $user, JobPosting $job): MatchAnalysis
    {
        $cv = $user->currentCv();
        if (! $cv) {
            throw new RuntimeException(__('messages.matching.cv_required'));
        }

        if ($job->status !== JobStatus::Published) {
            throw new RuntimeException(__('messages.matching.unavailable'));
        }

        $job->loadMissing(['company', 'department', 'skills']);

        if ($this->openai->configured()) {
            try {
                $result = $this->withOpenAi($job, $cv);
            } catch (\Throwable $e) {
                report($e);
                throw new RuntimeException(__('messages.matching.ai_failed'));
            }
        } else {
            $result = $this->local($job, $cv);
        }

        return MatchAnalysis::create([
            'user_id' => $user->id,
            'job_posting_id' => $job->id,
            'cv_id' => $cv->id,
            'score' => $result['score'],
            'summary' => $result['summary'],
            'strengths' => $result['strengths'],
            'weaknesses' => $result['weaknesses'],
            'improvements' => $result['improvements'],
            'engine' => $result['engine'],
        ]);
    }

    public function latest(User $user, JobPosting $job): ?MatchAnalysis
    {
        return MatchAnalysis::query()
            ->where('user_id', $user->id)
            ->where('job_posting_id', $job->id)
            ->latest('id')
            ->first();
    }

    /**
     * @return array{score: int, summary: string, strengths: list<string>, weaknesses: list<string>, improvements: list<string>, engine: string}
     */
    private function withOpenAi(JobPosting $job, Cv $cv): array
    {
        $raw = $this->openai->chat(
            $this->prompts->system('job_matching'),
            $this->prompts->user('job_matching', [
                'job' => $this->describeJob($job),
                'cv' => $this->describeCv($cv),
            ]),
            true,
        );

        $data = json_decode($this->stripFence($raw), true);
        if (! is_array($data) || ! isset($data['score'])) {
            throw new RuntimeException(__('messages.matching.ai_unreadable'));
        }

        return [
            'score' => max(0, min(100, (int) $data['score'])),
            'summary' => (string) ($data['summary'] ?? ''),
            'strengths' => $this->lines($data['strengths'] ?? []),
            'weaknesses' => $this->lines($data['weaknesses'] ?? []),
            'improvements' => $this->lines($data['improvements'] ?? []),
            'engine' => 'openai',
        ];
    }

    /**
     * @return array{score: int, summary: string, strengths: list<string>, weaknesses: list<string>, improvements: list<string>, engine: string}
     */
    private function local(JobPosting $job, Cv $cv): array
    {
        if (trim((string) $cv->extracted_text) === '' && ($cv->parsed_profile['skills'] ?? []) === []) {
            return [
                'score' => 0,
                'summary' => __('messages.matching.empty_summary'),
                'strengths' => [],
                'weaknesses' => [__('messages.matching.empty_weakness')],
                'improvements' => [__('messages.matching.empty_improvement')],
                'engine' => 'local',
            ];
        }

        $haystack = mb_strtolower($cv->extracted_text."\n".json_encode($cv->parsed_profile, JSON_UNESCAPED_UNICODE));
        $required = $job->skills->where('category', SkillCategory::Required)->values();
        $nice = $job->skills->where('category', SkillCategory::NiceToHave)->values();

        [$hitRequired, $missRequired] = $this->split($required, $haystack);
        [$hitNice, $missNice] = $this->split($nice, $haystack);

        $requiredRatio = $required->isEmpty() ? 1 : count($hitRequired) / $required->count();
        $niceRatio = $nice->isEmpty() ? 1 : count($hitNice) / $nice->count();
        $score = (int) round(($requiredRatio * 75) + ($niceRatio * 25));

        $strengths = array_map(
            fn (string $skill) => __('messages.matching.skill_hit', ['skill' => $skill]),
            array_merge($hitRequired, $hitNice),
        );
        $weaknesses = array_map(
            fn (string $skill) => __('messages.matching.skill_miss', ['skill' => $skill]),
            $missRequired,
        );
        $improvements = array_map(
            fn (string $skill) => __('messages.matching.skill_improve', ['skill' => $skill]),
            array_merge($missRequired, $missNice),
        );

        if ($strengths === []) {
            $strengths[] = __('messages.matching.no_hits');
        }

        return [
            'score' => max(0, min(100, $score)),
            'summary' => __('messages.matching.local_summary', [
                'required_hit' => count($hitRequired),
                'required_total' => max($required->count(), 0),
                'nice_hit' => count($hitNice),
                'nice_total' => max($nice->count(), 0),
            ]),
            'strengths' => array_slice($strengths, 0, 6),
            'weaknesses' => array_slice($weaknesses, 0, 6),
            'improvements' => array_slice($improvements, 0, 6),
            'engine' => 'local',
        ];
    }

    /**
     * @param  Collection<int, JobSkill>  $skills
     * @return array{0: list<string>, 1: list<string>}
     */
    private function split(Collection $skills, string $haystack): array
    {
        $hit = [];
        $miss = [];

        foreach ($skills as $skill) {
            if (SkillMatcher::contains($haystack, $skill->name)) {
                $hit[] = $skill->name;
            } else {
                $miss[] = $skill->name;
            }
        }

        return [$hit, $miss];
    }

    private function describeJob(JobPosting $job): string
    {
        $required = $job->skills->where('category', SkillCategory::Required)->pluck('name')->implode('、');
        $nice = $job->skills->where('category', SkillCategory::NiceToHave)->pluck('name')->implode('、');

        return implode("\n", [
            '会社名: '.$job->company?->name,
            'タイトル: '.$job->title,
            '職種: '.$job->position,
            '雇用形態: '.$job->employment_type,
            '勤務地: '.$job->location,
            '働き方: '.$job->remote_policy->label(),
            '年収: '.$job->salary_min.'〜'.$job->salary_max.'円',
            '概要: '.$job->summary,
            '仕事内容: '.$job->description,
            '応募資格: '.$job->requirements,
            '必須スキル: '.$required,
            '歓迎スキル: '.$nice,
        ]);
    }

    private function describeCv(Cv $cv): string
    {
        $profile = json_encode($cv->parsed_profile, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return "抽出テキスト:\n".mb_substr((string) $cv->extracted_text, 0, 8000)."\n\n構造化プロフィール:\n".$profile;
    }

    /**
     * @return list<string>
     */
    private function lines(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($line) => is_string($line) ? trim($line) : '',
            $value,
        )));
    }

    private function stripFence(string $raw): string
    {
        $raw = trim($raw);

        return trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $raw) ?? $raw);
    }
}
