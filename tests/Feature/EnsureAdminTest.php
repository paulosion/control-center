<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_env_is_always_the_login(): void
    {
        config(['app.admin.email' => ' Boss@Example.com ', 'app.admin.password' => 'first-pass']);
        $this->artisan('app:ensure-admin')->assertSuccessful();

        // Changing ADMIN_PASSWORD on a later deploy updates the account.
        config(['app.admin.password' => 'second-pass']);
        $this->artisan('app:ensure-admin')->assertSuccessful();

        $this->assertSame(1, User::count());
        $this->post('/login', ['email' => 'BOSS@example.com', 'password' => 'second-pass'])->assertRedirect('/');
        $this->assertAuthenticated();
    }
}
