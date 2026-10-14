<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'industry',
    'description',
    'website',
    'employee_count',
    'founded_year',
    'headquarters',
])]
class Company extends Model
{
    public function jobPostings(): HasMany
    {
        return $this->hasMany(JobPosting::class);
    }
}
