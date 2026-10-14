<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cvs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('s3_key');
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('size_bytes')->default(0);
            $table->longText('extracted_text')->nullable();
            $table->json('parsed_profile')->nullable();
            $table->string('parse_engine')->default('local');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_posting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cv_id')->nullable()->constrained('cvs')->nullOnDelete();
            $table->string('status')->default('applied')->index();
            $table->text('message')->nullable();
            $table->timestamp('applied_at');
            $table->timestamps();

            $table->unique(['user_id', 'job_posting_id']);
        });

        Schema::create('match_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_posting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cv_id')->nullable()->constrained('cvs')->nullOnDelete();
            $table->unsignedTinyInteger('score');
            $table->text('summary');
            $table->json('strengths');
            $table->json('weaknesses');
            $table->json('improvements');
            $table->string('engine');
            $table->timestamps();

            $table->index(['user_id', 'job_posting_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_analyses');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('cvs');
    }
};
