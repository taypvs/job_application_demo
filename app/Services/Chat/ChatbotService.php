<?php

namespace App\Services\Chat;

use App\Models\ChatMessage;
use App\Models\DocumentChunk;
use App\Models\User;
use App\Services\Ai\OpenAiClient;
use App\Services\Ai\PineconeClient;
use App\Services\Ai\PromptLibrary;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    public function __construct(
        private readonly OpenAiClient $openai,
        private readonly PineconeClient $pinecone,
        private readonly PromptLibrary $prompts,
    ) {}

    /**
     * @return list<array{role: string, content: string}>
     */
    public function history(User $user): array
    {
        return $user->chatMessages()
            ->latest('id')
            ->limit(30)
            ->get()
            ->sortBy('id')
            ->map(fn (ChatMessage $message) => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->values()
            ->all();
    }

    public function reply(User $user, string $message): string
    {
        $this->store($user, 'user', $message);

        try {
            $reply = $this->answer($user, $message);
        } catch (\Throwable $e) {
            report($e);
            $reply = __('messages.chat.failed');
        }

        $this->store($user, 'assistant', $reply);

        return $reply;
    }

    private function answer(User $user, string $message): string
    {
        $greeting = $this->greetingReply($message);
        if ($greeting !== null) {
            return $greeting;
        }

        if (DocumentChunk::query()->doesntExist()) {
            return __('messages.chat.no_documents');
        }

        if ($this->openai->configured() && $this->pinecone->configured()) {
            return $this->answerWithPinecone($user, $message);
        }

        $ranked = $this->rankLocal($message);
        if ($this->openai->configured()) {
            $context = $this->contextFromChunks($ranked->take(4));
            if ($context === '') {
                return __('messages.chat.no_match');
            }

            return $this->answerWithContext($user, $message, $context);
        }

        return $this->extractive($ranked);
    }

    private function answerWithPinecone(User $user, string $message): string
    {
        $log = Log::channel('daily');
        $vectors = $this->openai->embedMany([$message]);
        $vector = $vectors[0] ?? [];
        $log->info('chatbot.pinecone.embed', [
            'user_id' => $user->id,
            'question' => $message,
            'vector_count' => count($vectors),
            'dimensions' => count($vector),
        ]);

        $topK = max(1, (int) config('chatbot.top_k', 5));
        $minScore = (float) config('chatbot.min_score', 0);
        $matches = $this->pinecone->query($vector, $topK);
        $log->info('chatbot.pinecone.matches', [
            'user_id' => $user->id,
            'top_k' => $topK,
            'min_score' => $minScore,
            'count' => count($matches),
            'matches' => array_map(fn (array $match): array => [
                'id' => $match['id'] ?? null,
                'score' => $match['score'] ?? null,
                'kept' => (float) ($match['score'] ?? 0) >= $minScore,
                'title' => $match['metadata']['title'] ?? null,
                'text' => (string) ($match['metadata']['text'] ?? ''),
            ], $matches),
        ]);

        $blocks = [];

        foreach ($matches as $match) {
            if ((float) ($match['score'] ?? 0) < $minScore) {
                continue;
            }

            $text = (string) ($match['metadata']['text'] ?? '');
            $title = (string) ($match['metadata']['title'] ?? '資料');
            if ($text !== '') {
                $blocks[] = "[{$title}]\n{$text}";
            }
        }

        if ($blocks === []) {
            $fallback = $this->contextFromChunks($this->rankLocal($message)->take(3));
            if ($fallback === '') {
                return __('messages.chat.no_match');
            }

            $blocks[] = $fallback;
            $log->info('chatbot.pinecone.fallback', [
                'user_id' => $user->id,
                'context' => $fallback,
            ]);
        }

        $context = implode("\n\n", $blocks);
        $log->info('chatbot.pinecone.context', [
            'user_id' => $user->id,
            'block_count' => count($blocks),
            'context' => $context,
        ]);

        $reply = $this->answerWithContext($user, $message, $context);
        $log->info('chatbot.pinecone.reply', [
            'user_id' => $user->id,
            'reply' => $reply,
        ]);

        return $reply;
    }

    private function answerWithContext(User $user, string $message, string $context): string
    {
        return $this->openai->chat(
            $this->prompts->system('chatbot'),
            $this->prompts->user('chatbot', [
                'context' => mb_substr($context !== '' ? $context : '（該当資料なし）', 0, 6000),
                'history' => $this->historyText($user),
                'question' => $message,
            ]),
            false,
            0.3,
        );
    }

    /**
     * @param  Collection<int, DocumentChunk>  $ranked
     */
    private function extractive(Collection $ranked): string
    {
        $top = $ranked->filter(fn (DocumentChunk $chunk) => ($chunk->rank_score ?? 0) > 0)->take(3)->values();
        if ($top->isEmpty()) {
            return __('messages.chat.no_match');
        }

        $parts = [__('messages.chat.extractive_intro')];
        foreach ($top as $chunk) {
            $parts[] = "■ {$chunk->document?->title}\n{$chunk->content}";
        }

        return implode("\n\n", $parts);
    }

    /**
     * @return Collection<int, DocumentChunk>
     */
    private function rankLocal(string $query): Collection
    {
        $terms = collect(preg_split('/[\s　、。,.!?？]+/u', mb_strtolower($query)) ?: [])
            ->filter(fn ($term) => mb_strlen((string) $term) >= 2)
            ->values();

        return DocumentChunk::query()
            ->with('document')
            ->latest('id')
            ->limit(200)
            ->get()
            ->map(function (DocumentChunk $chunk) use ($terms) {
                $score = 0;
                $haystack = mb_strtolower($chunk->content);
                foreach ($terms as $term) {
                    if (mb_stripos($haystack, (string) $term) !== false) {
                        $score++;
                    }
                }
                $chunk->rank_score = $score;

                return $chunk;
            })
            ->sortByDesc('rank_score')
            ->values();
    }

    /**
     * @param  Collection<int, DocumentChunk>  $chunks
     */
    private function contextFromChunks(Collection $chunks): string
    {
        return $chunks
            ->filter(fn (DocumentChunk $chunk) => ($chunk->rank_score ?? 0) > 0)
            ->map(fn (DocumentChunk $chunk) => "[{$chunk->document?->title}]\n{$chunk->content}")
            ->implode("\n\n");
    }

    private function greetingReply(string $message): ?string
    {
        $normalized = mb_strtolower(trim($message));
        if ($normalized === '' || preg_match('/[?？]/u', $normalized) === 1) {
            return null;
        }

        $tokens = preg_split('/[^\p{L}\p{N}]+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($tokens === [] || count($tokens) > 4) {
            return null;
        }

        $replies = [
            'hi' => 'messages.chat.greeting',
            'hello' => 'messages.chat.greeting',
            'hey' => 'messages.chat.greeting',
            'yo' => 'messages.chat.greeting',
            'goodafternoon' => 'messages.chat.greeting',
            'こんにちは' => 'messages.chat.greeting',
            'こんにちわ' => 'messages.chat.greeting',
            'やあ' => 'messages.chat.greeting',
            'どうも' => 'messages.chat.greeting',
            'はじめまして' => 'messages.chat.greeting',
            '初めまして' => 'messages.chat.greeting',
            'よろしく' => 'messages.chat.greeting',
            'よろしくお願いします' => 'messages.chat.greeting',
            'goodmorning' => 'messages.chat.greeting_morning',
            'おはよう' => 'messages.chat.greeting_morning',
            'おはようございます' => 'messages.chat.greeting_morning',
            'goodevening' => 'messages.chat.greeting_evening',
            'こんばんは' => 'messages.chat.greeting_evening',
            'お疲れ様' => 'messages.chat.greeting',
            'お疲れさま' => 'messages.chat.greeting',
            'お疲れ様です' => 'messages.chat.greeting',
            'お疲れさまです' => 'messages.chat.greeting',
        ];

        $key = $replies[implode('', $tokens)] ?? null;

        return $key === null ? null : __($key);
    }

    private function historyText(User $user): string
    {
        $messages = $user->chatMessages()->latest('id')->limit(8)->get()->sortBy('id')->values();
        if ($messages->count() <= 1) {
            return '（なし）';
        }

        return $messages
            ->slice(0, -1)
            ->map(fn (ChatMessage $message) => ($message->role === 'assistant' ? 'アシスタント' : 'ユーザー').': '.$message->content)
            ->implode("\n");
    }

    private function store(User $user, string $role, string $content): void
    {
        $user->chatMessages()->create([
            'role' => $role,
            'content' => $content,
        ]);
    }
}
