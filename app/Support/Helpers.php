<?php

if (! function_exists('op_encrypt')) {
    function op_encrypt($str)
    {
        $key = config('hashing.encrypt_key');
        if (empty($key)) {
            return false;
        }

        $keyRaw = base64_decode($key);

        $iv_length = 16;
        $iv = openssl_random_pseudo_bytes($iv_length);

        try {
            $encrypted = openssl_encrypt($str, 'aes-256-cbc', $keyRaw, OPENSSL_RAW_DATA, $iv);
        } catch (Exception $e) {
            return false;
        }

        $output = base64_encode($iv.$encrypted);

        return $output;
    }
}

if (! function_exists('op_decrypt')) {
    function op_decrypt($str)
    {
        $key = config('hashing.encrypt_key');
        if (empty($key)) {
            return false;
        }

        $keyRaw = base64_decode($key);
        $mix = base64_decode($str, true);

        $iv_length = 16;
        if (! is_string($mix) || strlen($mix) <= $iv_length) {
            return false;
        }

        $iv = substr($mix, 0, $iv_length);
        $encrypted = substr($mix, $iv_length);

        try {
            $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $keyRaw, OPENSSL_RAW_DATA, $iv);

            return $decrypted;
        } catch (Exception $e) {
            return false;
        }
    }
}

if (! function_exists('op_env')) {
    function op_env(string $name, mixed $default = null): mixed
    {
        $value = env($name, $default);

        if (! is_string($value) || $value === '') {
            return $value;
        }

        $plain = op_decrypt($value);

        return is_string($plain) && $plain !== '' ? $plain : $value;
    }
}
