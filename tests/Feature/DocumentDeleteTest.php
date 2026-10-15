<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\UserRole;
use App\Models\KnowledgeDocument;
use App\Models\User;
use App\Services\Storage\S3StorageService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DocumentDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_admin_delete_removes_the_file_from_s3_and_pinecone(): void
    {
        $this->mock(S3StorageService::class, function ($mock): void {
            $mock->shouldReceive('delete')->once()->with('documents/handbook.pdf');
        });
        config([
            'services.pinecone.key' => 'test-key',
            'services.pinecone.host' => 'https://pinecone.test',
            'services.pinecone.namespace' => 'jobapply',
        ]);
        Http::fake([
            'https://pinecone.test/vectors/delete' => Http::response([]),
        ]);

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $document = KnowledgeDocument::create([
            'uploaded_by' => $admin->id,
            'title' => '就業規則',
            'source_type' => 'pdf',
            'original_name' => 'handbook.pdf',
            's3_key' => 'documents/handbook.pdf',
            'status' => DocumentStatus::Indexed,
            'chunk_count' => 2,
        ]);
        $document->chunks()->create([
            'chunk_index' => 0,
            'content' => '有給休暇',
            'pinecone_id' => 'doc'.$document->id.'-c0',
        ]);
        $document->chunks()->create([
            'chunk_index' => 1,
            'content' => '勤務時間',
            'pinecone_id' => 'doc'.$document->id.'-c1',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.documents.destroy', $document))
            ->assertRedirect(route('admin.documents.index'));

        $this->assertDatabaseMissing('knowledge_documents', ['id' => $document->id]);
        $this->assertDatabaseCount('document_chunks', 0);
        Http::assertSent(function ($request) use ($document) {
            return $request->url() === 'https://pinecone.test/vectors/delete'
                && $request['namespace'] === 'jobapply'
                && $request['ids'] === ['doc'.$document->id.'-c0', 'doc'.$document->id.'-c1'];
        });
    }

    public function test_document_stays_when_pinecone_delete_fails(): void
    {
        $this->mock(S3StorageService::class, function ($mock): void {
            $mock->shouldNotReceive('delete');
        });
        config([
            'services.pinecone.key' => 'test-key',
            'services.pinecone.host' => 'https://pinecone.test',
            'services.pinecone.namespace' => 'jobapply',
        ]);
        Http::fake([
            'https://pinecone.test/vectors/delete' => Http::response(['message' => 'nope'], 500),
        ]);

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $document = KnowledgeDocument::create([
            'uploaded_by' => $admin->id,
            'title' => '就業規則',
            'source_type' => 'pdf',
            's3_key' => 'documents/handbook.pdf',
            'status' => DocumentStatus::Indexed,
            'chunk_count' => 1,
        ]);
        $document->chunks()->create([
            'chunk_index' => 0,
            'content' => '有給休暇',
            'pinecone_id' => 'doc'.$document->id.'-c0',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.documents.index'))
            ->delete(route('admin.documents.destroy', $document))
            ->assertRedirect(route('admin.documents.index'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseHas('knowledge_documents', ['id' => $document->id]);
    }
}
