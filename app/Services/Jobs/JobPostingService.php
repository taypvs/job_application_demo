<?php

namespace App\Services\Jobs;

use App\Enums\JobStatus;
use App\Enums\SkillCategory;
use App\Models\Company;
use App\Models\Department;
use App\Models\JobPosting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class JobPostingService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function save(?JobPosting $job, array $data): JobPosting
    {
        return DB::transaction(function () use ($job, $data) {
            $status = JobStatus::from($data['status']);
            $payload = [
                'company_id' => $data['company_id'],
                'department_id' => $data['department_id'],
                'title' => $data['title'],
                'position' => $data['position'],
                'employment_type' => $data['employment_type'],
                'summary' => $data['summary'],
                'description' => $data['description'],
                'background' => $data['background'] ?? null,
                'requirements' => $data['requirements'] ?? null,
                'appeal' => $data['appeal'] ?? null,
                'company_note' => $data['company_note'] ?? null,
                'working_hours' => $data['working_hours'],
                'location' => $data['location'],
                'remote_policy' => $data['remote_policy'],
                'salary_min' => ((int) $data['salary_min_man']) * 10000,
                'salary_max' => ((int) $data['salary_max_man']) * 10000,
                'salary_note' => $data['salary_note'] ?? null,
                'benefits' => $data['benefits'] ?? null,
                'holidays' => $data['holidays'] ?? null,
                'probation' => $data['probation'] ?? null,
                'selection_process' => $data['selection_process'] ?? null,
                'status' => $status,
                'published_at' => $status === JobStatus::Published
                    ? ($job?->published_at ?? now())
                    : $job?->published_at,
            ];

            if ($job) {
                $job->update($payload);
            } else {
                $job = JobPosting::create($payload);
            }

            $this->syncSkills($job, $data['required_skills'] ?? [], SkillCategory::Required);
            $this->syncSkills($job, $data['nice_to_have_skills'] ?? [], SkillCategory::NiceToHave, startAt: $job->skills()->count());

            return $job->load(['company', 'department', 'skills']);
        });
    }

    public function paginate(?string $status): LengthAwarePaginator
    {
        return JobPosting::query()
            ->with(['company', 'department'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(12)
            ->withQueryString();
    }

    public function findPublished(JobPosting $job): JobPosting
    {
        if ($job->status !== JobStatus::Published) {
            throw (new ModelNotFoundException)->setModel(JobPosting::class, [$job->id]);
        }

        return $job->load(['company', 'department', 'skills']);
    }

    /**
     * @return array<string, mixed>
     */
    public function formOptions(): array
    {
        return [
            'companies' => Company::query()->orderBy('name')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
            'employmentTypes' => config('recruiting.employment_types'),
        ];
    }

    /**
     * @param  list<string>  $names
     */
    private function syncSkills(JobPosting $job, array $names, SkillCategory $category, int $startAt = 0): void
    {
        if ($category === SkillCategory::Required) {
            $job->skills()->delete();
            $startAt = 0;
        }

        foreach (array_values($names) as $index => $name) {
            $job->skills()->create([
                'name' => $name,
                'category' => $category,
                'sort_order' => $startAt + $index,
            ]);
        }
    }
}
