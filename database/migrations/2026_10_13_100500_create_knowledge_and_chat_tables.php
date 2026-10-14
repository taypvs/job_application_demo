<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->string('source_type');
            $table->string('original_name')->nullable();
            $table->string('s3_key')->nullable();
            $table->text('source_url')->nullable();
            $table->longText('extracted_text')->nullable();
            $table->string('status')->default('pending');
            $table->text('note')->nullable();
            $table->unsignedInteger('chunk_count')->default(0);
            $table->timestamps();
        });

        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->text('content');
            $table->string('pinecone_id')->nullable();
            $table->timestamps();

            $table->unique(['knowledge_document_id', 'chunk_index']);
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->text('content');
            $table->timestamps();

            $table->index(['user_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('document_chunks');
        Schema::dropIfExists('knowledge_documents');
    }
};
