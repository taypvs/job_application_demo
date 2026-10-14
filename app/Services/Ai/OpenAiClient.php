<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class OpenAiClient
{
    public function configured(): bool
    {
        return filled(config('services.openai.key'));
    }

    public function chat(string $system, string $user, bool $json = false, float $temperature = 0.2): string
    {
        try {
            return $this->requestChat($system, $user, $json, $temperature);
        } catch (RequestException $e) {
            if ($json && $e->response?->status() === 400) {
                return $this->requestChat($system, $user, false, $temperature);
            }

            throw $e;
        }
    }

    /**
     * @param  list<string>  $inputs
     * @return list<list<float>>
     */
    public function embedMany(array $inputs): array
    {
        $vectors = [];

        foreach (array_chunk($inputs, 16) as $batch) {
            $response = Http::withToken((string) config('services.openai.key'))
                ->timeout(90)
                ->post('https://api.openai.com/v1/embeddings', [
                    'model' => config('services.openai.embedding_model'),
                    'input' => array_map(fn (string $text) => mb_substr($text, 0, 8000), $batch),
                ])
                ->throw();

            $rows = $response->json('data') ?? [];
            usort($rows, fn (array $a, array $b) => ($a['index'] ?? 0) <=> ($b['index'] ?? 0));

            foreach ($rows as $row) {
                $vectors[] = $row['embedding'];
            }
        }

        return $vectors;
    }

    private function requestChat(string $system, string $user, bool $json, float $temperature): string
    {
        $payload = [
            'model' => config('services.openai.chat_model'),
            'temperature' => $temperature,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
        ];

        if ($json) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $response = Http::withToken((string) config('services.openai.key'))
            ->timeout(90)
            ->post('https://api.openai.com/v1/chat/completions', $payload)
            ->throw();

        return (string) $response->json('choices.0.message.content');
    }
}
