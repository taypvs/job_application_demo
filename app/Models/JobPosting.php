<?php

namespace App\Models;

use App\Enums\JobStatus;
use App\Enums\RemotePolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'department_id',
    'title',
    'position',
    'employment_type',
    'summary',
    'description',
    'background',
    'requirements',
    'appeal',
    'company_note',
    'working_hours',
    'location',
    'remote_policy',
    'salary_min',
    'salary_max',
    'salary_note',
    'benefits',
    'holidays',
    'probation',
    'selection_process',
    'status',
    'published_at',
])]
class JobPosting extends Model
{
    protected function casts(): array
    {
        return [
            'remote_policy' => RemotePolicy::class,
            'status' => JobStatus::class,
            'published_at' => 'datetime',
            'salary_min' => 'integer',
            'salary_max' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(JobSkill::class)->orderBy('sort_order');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', JobStatus::Published);
    }
}
