<?php

if (! function_exists('op_cipher_key')) {
    function op_cipher_key(): string|false
    {
        $key = config('hashing.encrypt_key');
        if (! is_string($key) || $key === '') {
            return false;
        }

        $raw = base64_decode($key, true);
        if (! is_string($raw) || $raw === '') {
            return false;
        }

        return str_pad(substr($raw, 0, 32), 32, "\0");
    }
}

if (! function_exists('op_encrypt')) {
    function op_encrypt($str)
    {
        $keyRaw = op_cipher_key();
        if ($keyRaw === false) {
            return false;
        }

        $iv = openssl_random_pseudo_bytes(16);

        try {
            $encrypted = openssl_encrypt($str, 'aes-256-cbc', $keyRaw, OPENSSL_RAW_DATA, $iv);
        } catch (Exception $e) {
            return false;
        }

        if (! is_string($encrypted) || $encrypted === '') {
            return false;
        }

        return base64_encode($iv.$encrypted);
    }
}

if (! function_exists('op_decrypt')) {
    function op_decrypt($str)
    {
        $keyRaw = op_cipher_key();
        if ($keyRaw === false || ! is_string($str) || $str === '') {
            return false;
        }

        $mix = base64_decode(str_replace(' ', '+', trim($str)), true);
        if (! is_string($mix) || strlen($mix) <= 16 || (strlen($mix) % 16) !== 0) {
            return false;
        }

        $iv = substr($mix, 0, 16);
        $encrypted = substr($mix, 16);

        try {
            $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $keyRaw, OPENSSL_RAW_DATA, $iv);
        } catch (Exception $e) {
            return false;
        }

        return is_string($decrypted) && $decrypted !== '' ? $decrypted : false;
    }
}

if (! function_exists('op_env')) {
    function op_env(string $name, mixed $default = null): mixed
    {
        $value = env($name, $default);

        if (! is_string($value) || $value === '') {
            $fromProcess = getenv($name);
            $value = is_string($fromProcess) && $fromProcess !== '' ? $fromProcess : $value;
        }

        if (! is_string($value) || $value === '') {
            return $value;
        }

        $plain = op_decrypt($value);
        if (! is_string($plain) || $plain === '') {
            return $value;
        }

        // AWS SDK đọc thẳng getenv(), nên key gốc phải có trong process.
        putenv($name.'='.$plain);
        $_ENV[$name] = $plain;
        $_SERVER[$name] = $plain;

        return $plain;
    }
}

if (! function_exists('op_publish_secrets')) {
    function op_publish_secrets(): void
    {
        $values = [
            'services.ses.key' => op_env('AWS_ACCESS_KEY_ID'),
            'services.ses.secret' => op_env('AWS_SECRET_ACCESS_KEY'),
            'services.s3.key' => op_env('AWS_ACCESS_KEY_ID'),
            'services.s3.secret' => op_env('AWS_SECRET_ACCESS_KEY'),
            'filesystems.disks.s3.key' => op_env('AWS_ACCESS_KEY_ID'),
            'filesystems.disks.s3.secret' => op_env('AWS_SECRET_ACCESS_KEY'),
            'queue.connections.sqs.key' => op_env('AWS_ACCESS_KEY_ID'),
            'queue.connections.sqs.secret' => op_env('AWS_SECRET_ACCESS_KEY'),
            'cache.stores.dynamodb.key' => op_env('AWS_ACCESS_KEY_ID'),
            'cache.stores.dynamodb.secret' => op_env('AWS_SECRET_ACCESS_KEY'),
            'services.openai.key' => op_env('OPENAI_API_KEY'),
            'services.pinecone.key' => op_env('PINECONE_API_KEY'),
        ];

        config(array_filter($values, fn (mixed $value): bool => is_string($value) && $value !== ''));
    }
}
