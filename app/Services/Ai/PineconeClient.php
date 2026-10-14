<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

class PineconeClient
{
    public function configured(): bool
    {
        return filled(config('services.pinecone.key')) && filled(config('services.pinecone.host'));
    }

    /**
     * @param  list<array{id: string, values: list<float>, metadata: array<string, mixed>}>  $vectors
     */
    public function upsert(array $vectors): void
    {
        foreach (array_chunk($vectors, 50) as $batch) {
            Http::withHeaders(['Api-Key' => (string) config('services.pinecone.key')])
                ->timeout(90)
                ->post($this->endpoint('/vectors/upsert'), [
                    'vectors' => $batch,
                    'namespace' => config('services.pinecone.namespace'),
                ])
                ->throw();
        }
    }

    /**
     * @param  list<string>  $ids
     */
    public function delete(array $ids): void
    {
        foreach (array_chunk($ids, 1000) as $batch) {
            Http::withHeaders(['Api-Key' => (string) config('services.pinecone.key')])
                ->timeout(90)
                ->post($this->endpoint('/vectors/delete'), [
                    'ids' => array_values($batch),
                    'namespace' => config('services.pinecone.namespace'),
                ])
                ->throw();
        }
    }

    /**
     * @param  list<float>  $vector
     * @return list<array<string, mixed>>
     */
    public function query(array $vector, int $topK = 5): array
    {
        $response = Http::withHeaders(['Api-Key' => (string) config('services.pinecone.key')])
            ->timeout(30)
            ->post($this->endpoint('/query'), [
                'namespace' => config('services.pinecone.namespace'),
                'vector' => $vector,
                'topK' => $topK,
                'includeMetadata' => true,
            ])
            ->throw();

        return $response->json('matches') ?? [];
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('services.pinecone.host'), '/').$path;
    }
}
