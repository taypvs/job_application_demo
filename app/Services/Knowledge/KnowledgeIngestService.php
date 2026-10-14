<?php

namespace App\Services\Knowledge;

use App\Enums\DocumentStatus;
use App\Models\KnowledgeDocument;
use App\Models\User;
use App\Services\Ai\OpenAiClient;
use App\Services\Ai\PineconeClient;
use App\Services\Documents\TextChunker;
use App\Services\Documents\TextExtractor;
use App\Services\Storage\S3StorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class KnowledgeIngestService
{
    public function __construct(
        private readonly S3StorageService $storage,
        private readonly TextExtractor $extractor,
        private readonly TextChunker $chunker,
        private readonly OpenAiClient $openai,
        private readonly PineconeClient $pinecone,
    ) {}

    /**
     * @param  array{title: string, source_type: string, url?: string|null}  $input
     */
    public function ingest(User $user, array $input, ?UploadedFile $file): KnowledgeDocument
    {
        [$text, $key, $sourceType, $originalName, $sourceUrl] = $this->capture($input, $file);

        if ($text === '') {
            throw new RuntimeException('本文を抽出できませんでした。別のファイル、またはテキストが含まれるURLを指定してください。');
        }

        $document = KnowledgeDocument::create([
            'uploaded_by' => $user->id,
            'title' => $input['title'],
            'source_type' => $sourceType,
            'original_name' => $originalName,
            's3_key' => $key,
            'source_url' => $sourceUrl,
            'extracted_text' => $text,
            'status' => DocumentStatus::Pending,
        ]);

        $chunks = $this->chunker->chunk($text, max(1, (int) config('chatbot.chunk_size', 300)));
        DB::transaction(function () use ($document, $chunks): void {
            foreach ($chunks as $index => $content) {
                $document->chunks()->create([
                    'chunk_index' => $index,
                    'content' => $content,
                ]);
            }
            $document->update(['chunk_count' => count($chunks)]);
        });

        if (! $this->openai->configured() || ! $this->pinecone->configured()) {
            $document->update([
                'status' => DocumentStatus::Stored,
                'note' => 'OpenAIまたはPineconeが未設定のため、本文はデータベースに保存しました。チャットは本文検索で回答します。',
            ]);

            return $document->fresh();
        }

        try {
            $this->index($document->fresh('chunks'));
            $document->update([
                'status' => DocumentStatus::Indexed,
                'note' => null,
            ]);
        } catch (\Throwable $e) {
            report($e);
            $document->update([
                'status' => DocumentStatus::Failed,
                'note' => 'Pineconeへの登録に失敗しました。本文はデータベースに残しています。',
            ]);
        }

        return $document->fresh();
    }

    public function destroy(KnowledgeDocument $document): void
    {
        $ids = $document->chunks()
            ->whereNotNull('pinecone_id')
            ->pluck('pinecone_id')
            ->filter(fn ($id) => is_string($id) && $id !== '')
            ->values()
            ->all();

        if ($ids !== []) {
            if (! $this->pinecone->configured()) {
                throw new RuntimeException('Pineconeが未設定のため、ベクトルを削除できません。');
            }

            try {
                $this->pinecone->delete($ids);
            } catch (\Throwable $e) {
                report($e);
                throw new RuntimeException('Pineconeからの削除に失敗しました。資料は残しています。');
            }
        }

        if (filled($document->s3_key)) {
            $this->storage->delete($document->s3_key);
        }

        $document->delete();
    }

    /**
     * @param  array{title: string, source_type: string, url?: string|null}  $input
     * @return array{0: string, 1: string, 2: string, 3: string, 4: ?string}
     */
    private function capture(array $input, ?UploadedFile $file): array
    {
        if ($input['source_type'] === 'url') {
            $url = (string) ($input['url'] ?? '');
            $text = $this->extractor->fromUrl($url);
            $key = 'documents/'.Str::uuid().'.txt';
            $this->storage->put($key, $text, 'text/plain; charset=UTF-8');

            return [$text, $key, 'url', $url, $url];
        }

        if (! $file) {
            throw new RuntimeException('ファイルを選択してください。');
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $text = $this->extractor->fromFile($file->getRealPath(), $extension);
        $key = 'documents/'.Str::uuid().'.'.$extension;
        $this->storage->put($key, (string) file_get_contents($file->getRealPath()), $file->getMimeType());

        return [$text, $key, $extension, $file->getClientOriginalName(), null];
    }

    private function index(KnowledgeDocument $document): void
    {
        $chunks = $document->chunks;
        if ($chunks->isEmpty()) {
            return;
        }

        $vectors = $this->openai->embedMany($chunks->pluck('content')->all());
        if (count($vectors) !== $chunks->count()) {
            throw new RuntimeException('埋め込み件数と分割数が一致しません。');
        }

        $payload = [];
        foreach ($chunks->values() as $index => $chunk) {
            $pineconeId = 'doc'.$document->id.'-c'.$chunk->chunk_index;
            $chunk->update(['pinecone_id' => $pineconeId]);
            $payload[] = [
                'id' => $pineconeId,
                'values' => $vectors[$index],
                'metadata' => [
                    'document_id' => $document->id,
                    'title' => $document->title,
                    'chunk_index' => $chunk->chunk_index,
                    'text' => $chunk->content,
                ],
            ];
        }

        $this->pinecone->upsert($payload);
    }
}
