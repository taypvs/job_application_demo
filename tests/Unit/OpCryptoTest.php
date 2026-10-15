<?php

namespace Tests\Unit;

use Illuminate\Support\Env;
use Tests\TestCase;

class OpCryptoTest extends TestCase
{
    public function test_encrypted_env_decrypts_and_is_published_as_the_raw_value(): void
    {
        $plain = 'AKIATESTKEY123456789';
        $cipher = op_encrypt($plain);

        $this->assertIsString($cipher);
        $this->assertSame($plain, op_decrypt($cipher));

        Env::getRepository()->set('OP_ENV_TEST_KEY', $cipher);

        $this->assertSame($plain, op_env('OP_ENV_TEST_KEY'));
        $this->assertSame($plain, getenv('OP_ENV_TEST_KEY'));
    }

    public function test_raw_env_values_are_left_unchanged(): void
    {
        Env::getRepository()->set('OP_ENV_TEST_RAW', 'AKIARAWKEY1234567890');

        $this->assertSame('AKIARAWKEY1234567890', op_env('OP_ENV_TEST_RAW'));
    }
}
