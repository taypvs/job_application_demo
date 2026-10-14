<?php

namespace App\Services\Jobs;

use App\Enums\JobStatus;
use App\Models\JobPosting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class JobSearchService
{
    /**
     * @param  array{position?: string|null, location?: string|null, salary?: string|null, keyword?: string|null}  $filters
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $keyword = $this->escapeLike($filters['keyword'] ?? null);

        return JobPosting::query()
            ->with('company')
            ->published()
            ->when($filters['position'] ?? null, fn ($query, $position) => $query->where('position', $position))
            ->when($filters['location'] ?? null, fn ($query, $location) => $query->where('location', $location))
            ->when($keyword, function ($query, $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('title', 'like', "%{$keyword}%")
                        ->orWhere('summary', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhere('position', 'like', "%{$keyword}%")
                        ->orWhereHas('company', fn ($company) => $company->where('name', 'like', "%{$keyword}%"));
                });
            })
            ->when($filters['salary'] ?? null, function ($query, $bandKey) {
                $band = config("recruiting.salary_bands.{$bandKey}");
                if (! is_array($band)) {
                    return;
                }
                $query->where('salary_max', '>=', $band['min'])
                    ->where('salary_min', '<=', $band['max']);
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(8)
            ->withQueryString();
    }

    /**
     * @return array{positions: \Illuminate\Support\Collection, locations: \Illuminate\Support\Collection}
     */
    public function options(): array
    {
        $base = JobPosting::query()->where('status', JobStatus::Published);

        return [
            'positions' => (clone $base)->distinct()->orderBy('position')->pluck('position'),
            'locations' => (clone $base)->distinct()->orderBy('location')->pluck('location'),
        ];
    }

    private function escapeLike(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
