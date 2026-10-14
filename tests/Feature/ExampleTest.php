<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_login_screen_is_available(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('ログイン');
    }
}
