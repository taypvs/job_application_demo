<?php

namespace App\Services\Cv;

use App\Models\Cv;
use App\Models\User;
use App\Support\SkillMatcher;
use App\Services\Ai\OpenAiClient;
use App\Services\Ai\PromptLibrary;
use App\Services\Documents\TextExtractor;
use App\Services\Storage\S3StorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CvService
{
    public function __construct(
        private readonly S3StorageService $storage,
        private readonly TextExtractor $extractor,
        private readonly OpenAiClient $openai,
        private readonly PromptLibrary $prompts,
    ) {}

    public function store(User $user, UploadedFile $file): Cv
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $text = $this->extractor->fromFile($file->getRealPath(), $extension);
        $key = 'cvs/'.$user->id.'/'.Str::uuid().'.'.$extension;
        $this->storage->put($key, (string) file_get_contents($file->getRealPath()), $file->getMimeType());

        [$profile, $engine] = $this->structure($text);

        return DB::transaction(function () use ($user, $file, $key, $text, $profile, $engine) {
            $user->cvs()->where('is_active', true)->update(['is_active' => false]);

            return $user->cvs()->create([
                'original_name' => $file->getClientOriginalName(),
                's3_key' => $key,
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize() ?: 0,
                'extracted_text' => $text,
                'parsed_profile' => $profile,
                'parse_engine' => $engine,
                'is_active' => true,
            ]);
        });
    }

    public function download(User $user, Cv $cv): StreamedResponse
    {
        if (! $user->isAdmin() && $cv->user_id !== $user->id) {
            abort(403);
        }

        return $this->storage->download($cv->s3_key, $cv->original_name, $cv->mime_type);
    }

    /**
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function structure(string $text): array
    {
        if ($text === '') {
            return [$this->emptyProfile(), 'local'];
        }

        if (! $this->openai->configured()) {
            return [$this->localProfile($text), 'local'];
        }

        try {
            $raw = $this->openai->chat(
                $this->prompts->system('cv_structuring'),
                $this->prompts->user('cv_structuring', ['cv' => mb_substr($text, 0, 12000)]),
                true,
            );
            $decoded = json_decode($this->stripFence($raw), true);

            if (! is_array($decoded)) {
                return [$this->localProfile($text), 'local'];
            }

            return [$this->normalizeProfile($decoded, $text), 'openai'];
        } catch (\Throwable $e) {
            report($e);

            return [$this->localProfile($text), 'local'];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function localProfile(string $text): array
    {
        $skills = [];
        foreach (config('recruiting.known_skills') as $skill) {
            if (SkillMatcher::contains($text, $skill)) {
                $skills[] = ['name' => $skill, 'years' => null];
            }
        }

        $profile = $this->emptyProfile();
        $profile['summary'] = mb_substr(trim(preg_replace("/\s+/u", ' ', $text) ?? $text), 0, 280);
        $profile['skills'] = $skills;

        return $profile;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeProfile(array $data, string $text): array
    {
        $skills = [];
        foreach ($data['skills'] ?? [] as $skill) {
            if (is_string($skill) && $skill !== '') {
                $skills[] = ['name' => $skill, 'years' => null];
            } elseif (is_array($skill) && filled($skill['name'] ?? null)) {
                $skills[] = [
                    'name' => (string) $skill['name'],
                    'years' => isset($skill['years']) && is_numeric($skill['years']) ? (float) $skill['years'] : null,
                ];
            }
        }

        $profile = $this->emptyProfile();
        $profile['summary'] = (string) ($data['summary'] ?? '');
        $profile['skills'] = $skills;
        $profile['experiences'] = array_values(array_filter($data['experiences'] ?? [], 'is_array'));
        $profile['education'] = array_values(array_filter($data['education'] ?? [], 'is_array'));
        $profile['years_of_experience'] = is_numeric($data['years_of_experience'] ?? null)
            ? (float) $data['years_of_experience']
            : null;

        if ($profile['summary'] === '') {
            $profile['summary'] = $this->localProfile($text)['summary'];
        }

        if ($skills === []) {
            $profile['skills'] = $this->localProfile($text)['skills'];
        }

        return $profile;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyProfile(): array
    {
        return [
            'summary' => '',
            'skills' => [],
            'experiences' => [],
            'education' => [],
            'years_of_experience' => null,
        ];
    }

    private function stripFence(string $raw): string
    {
        $raw = trim($raw);
        $raw = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $raw) ?? $raw;

        return trim($raw);
    }
}
