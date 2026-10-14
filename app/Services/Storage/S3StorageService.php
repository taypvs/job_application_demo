<?php

namespace App\Services\Storage;

use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class S3StorageService
{
    public function put(string $key, string $contents, ?string $mime = null): void
    {
        $options = [];
        if ($mime) {
            $options['mimetype'] = $mime;
        }

        try {
            Storage::disk('s3')->put($key, $contents, $options);
        } catch (\Throwable $e) {
            report($e);
            throw new RuntimeException('ファイルの保存に失敗しました。S3の設定を確認してください。');
        }
    }

    public function delete(string $key): void
    {
        try {
            Storage::disk('s3')->delete($key);
        } catch (\Throwable $e) {
            report($e);
            throw new RuntimeException('ファイルの削除に失敗しました。S3の設定を確認してください。');
        }
    }

    public function get(string $key): string
    {
        try {
            return Storage::disk('s3')->get($key);
        } catch (\Throwable $e) {
            report($e);
            throw new RuntimeException('ファイルの取得に失敗しました。');
        }
    }

    public function download(string $key, string $filename, ?string $mime): StreamedResponse
    {
        $contents = $this->get($key);
        $extension = pathinfo($filename, PATHINFO_EXTENSION) ?: 'bin';
        $fallback = 'download.'.$extension;
        $disposition = 'attachment; filename="'.$fallback.'"; filename*=UTF-8\'\''.rawurlencode($filename);

        return response()->streamDownload(function () use ($contents): void {
            echo $contents;
        }, $fallback, [
            'Content-Type' => $mime ?: 'application/octet-stream',
            'Content-Disposition' => $disposition,
        ]);
    }
}
