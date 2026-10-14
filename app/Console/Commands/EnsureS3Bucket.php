<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class EnsureS3Bucket extends Command
{
    protected $signature = 's3:bucket';

    protected $description = 'Create the configured S3 bucket when it does not exist';

    public function handle(): int
    {
        $bucket = (string) config('filesystems.disks.s3.bucket');
        $client = Storage::disk('s3')->getClient();

        for ($attempt = 1; $attempt <= 30; $attempt++) {
            try {
                if (! $client->doesBucketExist($bucket)) {
                    $client->createBucket(['Bucket' => $bucket]);
                }

                $this->info("Bucket {$bucket} is ready.");

                return self::SUCCESS;
            } catch (\Throwable $e) {
                $this->warn("Waiting for S3 ({$attempt}/30): ".$e->getMessage());
                sleep(2);
            }
        }

        $this->error('Could not prepare the S3 bucket.');

        return self::FAILURE;
    }
}
