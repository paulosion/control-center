<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginWithoutDeployStepTest extends TestCase
{
    use RefreshDatabase;

    // Railway may skip the start command (e.g. a different builder), so the
    // ADMIN_* login must work without app:ensure-admin ever running.
    public function test_admin_env_login_works_without_ensure_admin(): void
    {
        config(['app.admin.email' => 'me@example.com', 'app.admin.password' => 'pass-123']);

        $this->post('/login', ['email' => 'Me@Example.com', 'password' => 'pass-123'])->assertRedirect('/');
        $this->assertAuthenticated();

        // Every page renders even with no watched databases configured.
        config(['database.connections.genone.url' => null, 'database.connections.genone.host' => null,
            'database.connections.paymaya.url' => null, 'database.connections.paymaya.host' => null,
            'systems.my-genone.url' => null, 'systems.201.url' => null, 'systems.paymaya.url' => null,
            'systems.ticketing.url' => null, 'systems.face-service.url' => null, 'systems.gen1.url' => null]);
        foreach (['/', '/rfid', '/health', '/apps', '/todo', '/not-recorded'] as $page) {
            $this->get($page)->assertOk();
        }
    }

    public function test_missing_admin_variables_are_named_on_the_sign_in_page(): void
    {
        config(['app.admin.email' => null, 'app.admin.password' => null]);

        $this->from('/login')->post('/login', ['email' => 'me@example.com', 'password' => 'x'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => "Sign-in isn't set up yet: ADMIN_EMAIL and ADMIN_PASSWORD aren't set on this Railway service."]);
    }
}
