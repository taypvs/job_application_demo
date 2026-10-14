<?php

namespace Tests\Feature;

use Tests\TestCase;

class PortalSessionTest extends TestCase
{
    public function test_each_portal_uses_its_own_session_cookie(): void
    {
        $candidate = $this->withServerVariables(['PORTAL' => 'candidate'])->get('/login');
        $admin = $this->withServerVariables(['PORTAL' => 'admin'])->get('/login');

        $candidate->assertOk();
        $admin->assertOk();
        $this->assertTrue($this->hasCookie($candidate, 'candidate_session'));
        $this->assertTrue($this->hasCookie($admin, 'admin_session'));
        $this->assertFalse($this->hasCookie($candidate, 'admin_session'));
        $this->assertFalse($this->hasCookie($admin, 'candidate_session'));
    }

    public function test_candidate_portal_sends_admin_pages_to_the_admin_port(): void
    {
        $this->withServerVariables(['PORTAL' => 'candidate'])
            ->get('/admin/jobs')
            ->assertRedirect(config('app.admin_url').'/admin/jobs');
    }

    public function test_admin_portal_sends_job_search_to_the_candidate_port(): void
    {
        $this->withServerVariables(['PORTAL' => 'admin'])
            ->get('/jobs')
            ->assertRedirect(config('app.candidate_url').'/jobs');
    }

    private function hasCookie($response, string $name): bool
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $name) {
                return true;
            }
        }

        return false;
    }
}
