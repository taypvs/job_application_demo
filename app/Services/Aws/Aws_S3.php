<?php

namespace App\Services\Aws;

use Aws\CommandPool;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Exception;
use Illuminate\Support\Facades\Log;

class Aws_S3 {
    const VERSION = "latest";
    const REGION = "ap-northeast-1";
    const ACL_PUBLIC = "public-read";

    private function __construct() {}

    public static function client() {
        $config = [
            "version"     => self::VERSION,
            "region"      => self::region(),
            "credentials" => [
                "key"    => self::credential('key'),
                "secret" => self::credential('secret'),
            ],
        ];
        $endpoint = config('services.s3.endpoint');
        if (is_string($endpoint) && $endpoint !== '') {
            $config['endpoint'] = $endpoint;
            $config['use_path_style_endpoint'] = filter_var(
                config('services.s3.use_path_style_endpoint', false),
                FILTER_VALIDATE_BOOLEAN,
            );
        }

        return new S3Client($config);
    }

    private static function region(): string
    {
        $region = config('services.s3.region');

        return is_string($region) && $region !== '' ? $region : self::REGION;
    }

    private static function bucket(): string
    {
        return (string) config('services.s3.bucket', '');
    }

    private static function folder(): string
    {
        return (string) config('services.s3.folder', '');
    }

    private static function credential(string $key): string
    {
        $value = config('services.s3.'.$key);

        if (! is_string($value) || $value === '') {
            return '';
        }

        $decrypted = op_decrypt($value);

        return is_string($decrypted) && $decrypted !== '' ? $decrypted : $value;
    }

    public static function copy($from_path, $to_path) {
        $client = self::client();

        try {
            $result = $client->copyObject([
                "Bucket"     => self::bucket(),
                "Key"        => $from_path,
                "CopySource" => $to_path,
            ]);
            return $result["ObjectURL"];
        } catch (S3Exception $e) {
            $result["message"] = $e->getMessage() . "\n";
            $result["status"] = false;
        }
        return $result;
    }

    public static function createBucket($bucket_name) {
        $client = self::client();

        try {
            $data = [
                'ACL' => 'private',
                'Bucket' => $bucket_name,
                'CreateBucketConfiguration' => [
                    'LocationConstraint' => 'ap-northeast-1',
                ],
            ];
            $result = $client->createBucket($data);
            return $result["Location"];
        } catch (S3Exception $e) {
            $result["message"] = $e->getMessage() . "\n";
            $result["status"] = false;
        }
        return $result;
    }

    public static function deleteAllObjectsWithPath($bucket_name, $prefix) {
        $client = self::client();
        try {
            $client->deleteMatchingObjects($bucket_name, $prefix);
        } catch (S3Exception $e) {
            return false;
        }
        return true;
    }

    public static function copyObjectToS3Destination($from_path, $to_bucket, $to_path) {
        $client = self::client();

        try {
            $data = [
                'Bucket' => $to_bucket,
                'CopySource' => $from_path,
                'Key' => $to_path
            ];
            
            $result = $client->copyObject($data);
            return $result["ObjectURL"];
        } catch (S3Exception $e) {
            $result["message"] = $e->getMessage() . "\n";
            $result["status"] = false;
        }
        return $result;
    }

    public static function upload($resource, $path, $type, $download_file_name) {
        $client = self::client();

        //Change type to force download pdf
        if($type == 'pdf'){
            $type = 'application/octet-stream';
        }

        try {
            $result = $client->putObject([
                "Bucket"      => self::bucket(),
                "Key"         => $path,
                "SourceFile"  => $resource,
                "ContentType" => $type,
                // 'ACL'         => self::ACL_PUBLIC,
                'ContentDisposition' => 'attachment; filename='.$download_file_name,
            ]);
//            $url = $result["ObjectURL"];
//            dd($url);
//            return $url;
            return $result["ObjectURL"];
        } catch (S3Exception $e) {
            // echo $e->getMessage();
            $result["message"] = $e->getMessage() . "\n";
            $result["status"] = false;
        }
        return $result;
    }

    public static function uploadWithBucket($bucket, $resource, $path, $type, $download_file_name) {
        $client = self::client();

        //Change type to force download pdf
        if($type == 'pdf'){
            $type = 'application/octet-stream';
        }

        try {
            $result = $client->putObject([
                "Bucket"      => $bucket,
                "Key"         => $path,
                "SourceFile"  => $resource,
                "ContentType" => $type,
                // 'ACL'         => self::ACL_PUBLIC,
                'ContentDisposition' => 'attachment; filename='.$download_file_name,
            ]);
            return $result["ObjectURL"];
        } catch (S3Exception $e) {
            // echo $e->getMessage();
            $result["message"] = $e->getMessage() . "\n";
            $result["status"] = false;
        }
        return $result;
    }

    public static function uploadWithBody($body, $path, $type) {
        $client = self::client();

        try {
            $result = $client->putObject([
                "Bucket"      => self::bucket(),
                "Key"         => $path,
                "Body"        => $body,
                "ContentType" => $type,
                'ACL'         => self::ACL_PUBLIC,
                'ContentDisposition' => 'attachment',
            ]);
            return $result["ObjectURL"];
        } catch (S3Exception $e) {
            $result["message"] = $e->getMessage() . "\n";
            $result["status"] = false;
        }
        return $result;
    }

    public static function download($path) {
        $client = self::client();

        try {
            $result = $client->getObject(array(
                "Bucket" => self::bucket(),
                "Key"    => $path,
            ));
            $result["status"] = true;
        } catch (S3Exception $e) {
            $result["message"] = $e->getMessage() . "\n";
            $result["status"] = false;
        }
        return $result;
    }

    public static function getDownloadFileCommand($fileInfo, $file)
    {
        $client = self::client();
        try {
            $command = $client->getCommand(
                'GetObject',
                [
                    'Bucket' => $fileInfo["bucket"],
                    'Key' => $fileInfo["path"],
                ]
            );
        } catch (S3Exception $e) {
            throw new Exception($e->getMessage() . "\n");
        }
        return $command;
    }

    public static function excuteCommands($commands)
    {
        $client = self::client();
        return CommandPool::batch($client, $commands);
    }

    public static function presign($path) {
        //TODO: VanHT if debug = false, crash
        $client = self::client();

        try {
            $cmd = $client->getCommand('GetObject', [
                'Bucket' => self::bucket(),
                'Key'    => $path,
            ]);

            // return $client->getObjectUrl(self::bucket(), $path);
            $request = $client->createPresignedRequest($cmd, '+5 minutes');

            $signedUrl = null;
            if ($request) {
                $signedUrl = (string) $request->getUri();
            }
            return $signedUrl;
        } catch (S3Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public static function presignWithConfigFolder($path) {
        //TODO: VanHT if debug = false, crash
        $client = self::client();

        try {
            $cmd = $client->getCommand('GetObject', [
                'Bucket' => self::bucket(),
                'Key'    => self::folder() . $path,
            ]);

            // return $client->getObjectUrl(self::bucket(), $path);
            $request = $client->createPresignedRequest($cmd, '+5 minutes');

            $signedUrl = null;
            if ($request) {
                $signedUrl = (string) $request->getUri();
            }
            return $signedUrl;
        } catch (S3Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public static function presignWithBucket($bucket, $path) {
        //TODO: VanHT if debug = false, crash
        $client = self::client();

        try {
            $cmd = $client->getCommand('GetObject', [
                'Bucket' => $bucket,
                'Key'    => $path,
                'ResponseContentDisposition' => 'attachment; filename="' .$path.'"',
            ]);
            $signedUrl = null;
            if ($client->doesObjectExist ($bucket, $path)) {
                $request = $client->createPresignedRequest($cmd, '+20 minutes');
                if ($request) {
                    $signedUrl = (string) $request->getUri();
                }            
                return $signedUrl;
            }
            return "";
        } catch (S3Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public static function delete($path) {
        $client = self::client();

        try {
            $result = $client->deleteObject(array(
                "Bucket" => self::bucket(),
                "Key"    => $path,
            ));
        } catch (S3Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
        return true;
    }

    public static function deleteWithConfigFolder($path) {
        $client = self::client();

        try {
            $result = $client->deleteObject(array(
                "Bucket" => self::bucket(),
                "Key"    => self::folder() . $path,
            ));
        } catch (S3Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
        return true;
    }

    public static function deleteWithBucket($bucket, $path) {
        $client = self::client();

        try {
            $result = $client->deleteObject(array(
                "Bucket" => $bucket,
                "Key"    => $path,
            ));
        } catch (S3Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
        return true;
    }

    // List over 1000 records
    public static function paginatoryList($bucket, $path) {
        $client = self::client();

        try {
            $data = [
                "Bucket" => $bucket,
                "Prefix" => $path,
            ];

            $result = $client->getPaginator('ListObjects', $data);
            
            return $result;
        } catch (S3Exception $e) {
            $result["message"] = $e->getMessage() . "\n";
            $result["status"] = false;
            return $result;
        }
    }

    // Limit 1000 records
    public static function pathList($path) {
        $client = self::client();

        try {
            $result = $client->listObjects(array(
                "Bucket" => self::bucket(),
                "Prefix" => $path,
            ));
            $list = array();
            foreach ((array) $result["Contents"] as $object) {
                array_unshift($list, "{$object['Key']}");
            }
            return $list;
        } catch (S3Exception $e) {
            $result["message"] = $e->getMessage() . "\n";
            $result["status"] = false;
            return $result;
        }
    }

    public static function fileCheck($path) {        
        return self::fileCheckInBucket(self::bucket(), $path);
    }

    public static function fileCheckInBucket($bucket, $path) {
        $client = self::client();
        if ($client->doesObjectExist($bucket, $path)) {
            return true;
        } else {
            return false;
        }
    }

    public static function fileDownload($path, $file_name) {
        $client = self::client();
        ob_clean();
        try {
            $result = $client->getObject(array(
                "Bucket" => self::bucket(),
                "Key"    => $path,
            ));

            header("Content-Disposition: attachment; filename=" . $file_name);
            header('Content-Type: ' . $result['ContentType']);
            header('Content-Length: ' . strlen($result['Body']));
            echo $result['Body'];

            $result["status"] = true;
        } catch (S3Exception $e) {
            $result["message"] = $e->getMessage() . "\n";
            $result["status"] = false;
        }
        return $result;
    }

    public static function fileDownloadWithBucket($bucket, $path) {
        $client = self::client();
        ob_clean();
        try {
            $result = $client->getObject(array(
                "Bucket" => $bucket,
                "Key"    => $path,
            ));

            header("Content-Disposition: inline; filename=file");
            header('Content-Type: ' . $result['ContentType']);
            header('Content-Length: ' . strlen($result['Body']));
            return $result['Body'];

            // $result["status"] = true;
        } catch (S3Exception $e) {
            $result["message"] = $e->getMessage() . "\n";
            $result["status"] = false;
        }
        return $result;
    }

    public static function fileDisplay($path, $file_name) {
        $client = self::client();
        ob_clean();
        try {
            $result = $client->getObject(array(
                "Bucket" => self::bucket(),
                "Key"    => $path,
            ));
            $type = $result['ContentType'];
            switch ($type) {
            case 'jpg':
                $type = 'image/jpeg';
                break;
            case 'jpeg':
                $type = 'image/jpeg';
                break;
            case 'bmp':
                $type = 'image/bmp';
                break;
            case 'png':
                $type = 'image/png';
                break;
            case 'gif':
                $type = 'image/gif';
                break;
            case 'pdf':
                $type = 'application/pdf';
                break;
            default:
                break;
            }
            header('Content-Type:' . $type);
            header('Content-Disposition: inline; filename="' . $file_name . '"');
            header('Content-Length: ' . strlen($result['Body']));
            echo $result['Body'];
            $result["status"] = true;
        } catch (S3Exception $e) {
            $result["message"] = $e->getMessage() . "\n";
            $result["status"] = false;
        }
        return $result;
    }

    public static function fileDisplayWithBucket($bucket, $path, $file_name) {
        $client = self::client();
        if(ob_get_contents()){
            ob_clean();    
        }
        try {
            $result = $client->getObject(array(
                "Bucket" => $bucket,
                "Key"    => $path,
            ));
            $type = $result['ContentType'];
            switch ($type) {
            case 'jpg':
                $type = 'image/jpeg';
                break;
            case 'jpeg':
                $type = 'image/jpeg';
                break;
            case 'bmp':
                $type = 'image/bmp';
                break;
            case 'png':
                $type = 'image/png';
                break;
            case 'gif':
                $type = 'image/gif';
                break;
            case 'pdf':
                $type = 'application/pdf';
                break;
            case 'ico':
                $type = 'image/x-icon';
                break;
            default:
                break;
            }
            header('Content-Type:' . $type);
            header('Content-Disposition: inline; filename="' . $file_name . '"');
            header('Content-Length: ' . strlen($result['Body']));
            // echo $result['Body'];
            $result["status"] = true;
        } catch (S3Exception $e) {
            echo $e->getMessage();
            $result["message"] = $e->getMessage() . "\n";
            $result["status"] = false;
        }
        // var_dump($result);
        // exit;
        return $result;
    }

    public static function uploadMultiPart ($file, $bucket, $path) {
        $client = self::client();
        ini_set('memory_limit', '-1');
        try {
            $key = $path;
            // $file_name = $file_path;
            $result = $client->createMultipartUpload(array(
                'Bucket' => $bucket,
                'Key'    => $key,
            ));
    
            $upload_id = $result['UploadId'];
            $file = @fopen($file, 'r');
            $parts = array();
            $partNumber = 1;
            while (!feof($file)) {
                $result = $client->uploadPart(array(
                    'Bucket'     => $bucket,
                    'Key'        => $key,
                    'UploadId'   => $upload_id,
                    'PartNumber' => $partNumber,
                    'Body'       => @fread($file, 10 * 1024 * 1024),
                ));
                $parts[] = array(
                    'PartNumber' => $partNumber++,
                    'ETag'       => $result['ETag'],
                );
                gc_collect_cycles();
            }

            $result = $client->completeMultipartUpload(array(
                'Bucket'   => $bucket,
                'Key'      => $key,
                'UploadId' => $upload_id,
                'MultipartUpload' => [
                    'Parts'    => $parts,
                ]
            ));
            @fclose($file);

            return $result["Location"];
            
        } catch (Exception $e) {
            echo "Error: " .$e->getMessage();
            $result["message"] = $e->getMessage() . "\n";
            $result["status"] = false;
        }
        
        return $result;
    }

    public static function createMultiPartUpload ($file, $uploadId, $bucket, $path, $lastPart) {
        $client = self::client();
        $byteRead = 100 * 1024 * 1024; // 100MB / Part
        $key = $path;
        $returnResult = [];

        try {
            if ($uploadId == "null") {
                // $file_name = $file_path;
                $result = $client->createMultipartUpload(array(
                    'Bucket' => $bucket,
                    'Key'    => $key,
                ));

                // echo "uploadId result: " . $result['UploadId'];
                $uploadId = $result['UploadId'];
            }

            $partNumber = $lastPart;
            // echo "uploadId: " . $uploadId;
            // echo "File size :" . filesize($file);

            $file = @fopen($file, 'r');

            $result = $client->uploadPart(array(
                'Bucket'     => $bucket,
                'Key'        => $key,
                'UploadId'   => $uploadId,
                'PartNumber' => $partNumber,
                'Body'       => @fread($file, $byteRead),
            ));

            $returnResult['upload_id'] = $uploadId;
            $returnResult['part_number'] = $partNumber;
            $returnResult['ETag'] = $result['ETag'];
            return $returnResult;

        } catch (Exception $e) {
            echo "Error: " .$e->getMessage();
            $returnResult["message"] = $e->getMessage() . "\n";
            $returnResult["status"] = false;
        }

        return $returnResult;
    }

    public static function uploadCompleteMultiPart ($uploadId, $bucket, $path, $partNumbers, $eTags) {
        $client = self::client();

        $parts = [];
        
        try {
            for ($i = 0; $i < count($partNumbers); $i++) {
                $parts[] = array(
                    'PartNumber' => $partNumbers[$i],
                    'ETag'       => $eTags[$i],
                );
            }

            // echo "Part : " . sizeof($parts);
            $result = $client->completeMultipartUpload(array(
                'Bucket'   => $bucket,
                'Key'      => $path,
                'UploadId' => $uploadId,
                'MultipartUpload' => [
                    'Parts'    => $parts,
                ]
            ));
            return $result["Location"];
        } catch (Exception $e) {
            // echo "Error: " .$e->getMessage(). "\n";
            $result["message"] = $e->getMessage() ;
            $result["status"] = false;
        }

        return $result;
    }

    public static function abortMultiPartUpload ($uploadId, $bucket, $path) {
        $client = self::client();

        try {
            $result = $client->abortMultipartUpload(
                [
                    'Bucket'   => $bucket,
                    'Key'      => $path,
                    'UploadId' => $uploadId,
                ]);
        } catch (Exception $e) {
            // echo "Error: " .$e->getMessage(). "\n";
            return false;
        }
        return true;
    }

}
