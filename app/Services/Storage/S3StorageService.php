<?php

namespace App\Services\Storage;

use App\Services\Aws\Aws_S3;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class S3StorageService
{
    public function put(string $key, string $contents, ?string $mime = null): void
    {
        $temporary = tempnam(sys_get_temp_dir(), 's3');
        if ($temporary === false) {
            throw new RuntimeException('ファイルの保存に失敗しました。S3の設定を確認してください。');
        }

        try {
            file_put_contents($temporary, $contents);
            $result = Aws_S3::upload($temporary, $key, $mime ?: 'application/octet-stream', basename($key));
            if (is_array($result) && ($result['status'] ?? true) === false) {
                throw new RuntimeException((string) ($result['message'] ?? 's3 upload failed'));
            }
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            throw new RuntimeException('ファイルの保存に失敗しました。S3の設定を確認してください。');
        } finally {
            @unlink($temporary);
        }
    }

    public function delete(string $key): void
    {
        try {
            if (! Aws_S3::delete($key)) {
                throw new RuntimeException('ファイルの削除に失敗しました。S3の設定を確認してください。');
            }
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            throw new RuntimeException('ファイルの削除に失敗しました。S3の設定を確認してください。');
        }
    }

    public function get(string $key): string
    {
        try {
            $result = Aws_S3::download($key);
            if (! is_array($result) || ($result['status'] ?? false) !== true) {
                throw new RuntimeException((string) ($result['message'] ?? 's3 download failed'));
            }

            return (string) $result['Body'];
        } catch (RuntimeException $e) {
            throw $e;
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
