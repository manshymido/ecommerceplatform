<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\CreatesAdminUser;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use CreatesAdminUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureRolesExist();
    }

    public function test_login_returns_token_and_user(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('admin');

        $response = $this->postJson('/api/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user'])
            ->assertJsonPath('user.email', 'admin@test.com');
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'wrong@test.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_protected_user_endpoint_requires_auth(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_protected_user_endpoint_returns_user_with_token(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/user', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_admin_dashboard_requires_admin_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/admin/dashboard', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_dashboard_works_for_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/admin/dashboard', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Admin Dashboard');
    }
}
