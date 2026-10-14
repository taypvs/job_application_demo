<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('position');
            $table->string('employment_type');
            $table->text('summary');
            $table->text('description');
            $table->text('background')->nullable();
            $table->text('requirements')->nullable();
            $table->text('appeal')->nullable();
            $table->text('company_note')->nullable();
            $table->string('working_hours');
            $table->string('location');
            $table->string('remote_policy');
            $table->unsignedInteger('salary_min');
            $table->unsignedInteger('salary_max');
            $table->string('salary_note')->nullable();
            $table->text('benefits')->nullable();
            $table->text('holidays')->nullable();
            $table->string('probation')->nullable();
            $table->text('selection_process')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('position');
            $table->index('location');
            $table->index(['salary_min', 'salary_max']);
        });

        Schema::create('job_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['job_posting_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_skills');
        Schema::dropIfExists('job_postings');
    }
};
