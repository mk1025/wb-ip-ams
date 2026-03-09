<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuthAuditLog;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Prevent real HTTP calls to ip-service during register/login
        Http::fake();
    }

    public function test_register_returns_201_with_user_and_tokens(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'email', 'role'],
                    'tokens' => ['access_token', 'refresh_token', 'token_type'],
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'newuser@example.com')
            ->assertJsonPath('data.user.role', 'user')
            ->assertJsonPath('data.tokens.token_type', 'bearer');

        $tokens = $response->json('data.tokens');
        $this->assertNotEmpty($tokens['access_token']);
        $this->assertNotEmpty($tokens['refresh_token']);
    }

    public function test_register_returns_422_for_missing_email(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)->assertJsonStructure(['message', 'errors']);
    }

    public function test_register_returns_422_for_missing_password(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'newuser@example.com',
        ]);

        $response->assertStatus(422)->assertJsonStructure(['errors']);
    }

    public function test_register_returns_422_for_invalid_email_format(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'not-a-valid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_returns_422_for_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_returns_422_for_password_too_short(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'newuser@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_creates_audit_log_entry(): void
    {
        $this->postJson('/api/auth/register', [
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('auth_audit_logs', ['action' => 'register']);
    }

    public function test_login_returns_200_with_tokens_on_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('secret1234'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'secret1234',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'email', 'role'],
                    'tokens' => ['access_token', 'refresh_token', 'token_type'],
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tokens.token_type', 'bearer');

        $tokens = $response->json('data.tokens');
        $this->assertNotEmpty($tokens['access_token']);
        $this->assertNotEmpty($tokens['refresh_token']);
    }

    public function test_login_returns_401_for_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_returns_401_for_nonexistent_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_creates_refresh_token_in_db(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret1234')]);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $this->assertDatabaseHas('refresh_tokens', ['user_id' => $user->id]);
    }

    public function test_login_creates_audit_log_entry(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret1234')]);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $this->assertDatabaseHas('auth_audit_logs', [
            'user_id' => $user->id,
            'action' => 'login',
        ]);
    }

    public function test_logout_returns_200_and_deletes_refresh_tokens(): void
    {
        $user = User::factory()->create();
        RefreshToken::create([
            'user_id' => $user->id,
            'token' => str_repeat('a', 64),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($user, 'api')->postJson('/api/auth/logout');

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertDatabaseMissing('refresh_tokens', ['user_id' => $user->id]);
    }

    public function test_logout_returns_401_for_unauthenticated_request(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    public function test_logout_creates_audit_log_entry(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')->postJson('/api/auth/logout');

        $this->assertDatabaseHas('auth_audit_logs', [
            'user_id' => $user->id,
            'action' => 'logout',
        ]);
    }

    public function test_me_returns_authenticated_user_data(): void
    {
        $user = User::factory()->create(['email' => 'iam@example.com', 'role' => 'user']);

        $response = $this->actingAs($user, 'api')->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'iam@example.com')
            ->assertJsonPath('data.role', 'user');
    }

    public function test_me_returns_401_for_unauthenticated_request(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    public function test_refresh_returns_new_access_token_for_valid_refresh_token(): void
    {
        $user = User::factory()->create();
        $refreshToken = RefreshToken::create([
            'user_id' => $user->id,
            'token' => str_repeat('v', 64),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['access_token', 'token_type']])
            ->assertJsonPath('success', true);
    }

    public function test_refresh_does_not_delete_the_refresh_token(): void
    {
        $user = User::factory()->create();
        $refreshToken = RefreshToken::create([
            'user_id' => $user->id,
            'token' => str_repeat('v', 64),
            'expires_at' => now()->addDays(30),
        ]);

        $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken->token,
        ]);

        $this->assertDatabaseHas('refresh_tokens', ['id' => $refreshToken->id]);
    }

    public function test_refresh_returns_401_for_invalid_token(): void
    {
        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => 'completely-bogus-token-that-does-not-exist',
        ]);

        $response->assertStatus(401);
    }

    public function test_refresh_returns_401_for_expired_token(): void
    {
        $user = User::factory()->create();
        $refreshToken = RefreshToken::create([
            'user_id' => $user->id,
            'token' => str_repeat('e', 64),
            'expires_at' => now()->subDay(), // expired yesterday
        ]);

        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken->token,
        ]);

        $response->assertStatus(401);
    }

    public function test_refresh_returns_422_for_missing_refresh_token_field(): void
    {
        $response = $this->postJson('/api/auth/refresh', []);

        $response->assertStatus(422);
    }

    public function test_audit_logs_returns_200_with_paginated_data_for_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'super-admin']);

        $response = $this->actingAs($admin, 'api')->getJson('/api/auth/audit-logs');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'logs' => ['data', 'current_page', 'total'],
                    'filter_options' => ['users', 'actions'],
                ],
            ]);
    }

    public function test_audit_logs_returns_403_for_regular_user(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user, 'api')->getJson('/api/auth/audit-logs');

        $response->assertStatus(403);
    }

    public function test_audit_logs_returns_401_for_unauthenticated_request(): void
    {
        $response = $this->getJson('/api/auth/audit-logs');

        $response->assertStatus(401);
    }

    public function test_audit_logs_filters_by_action(): void
    {
        $admin = User::factory()->create(['role' => 'super-admin']);
        $user = User::factory()->create();
        AuthAuditLog::create(['user_id' => $user->id, 'action' => 'login', 'created_at' => now()]);
        AuthAuditLog::create(['user_id' => $user->id, 'action' => 'logout', 'created_at' => now()]);

        $response = $this->actingAs($admin, 'api')
            ->getJson('/api/auth/audit-logs?action=login');

        $response->assertStatus(200);
        $logs = collect($response->json('data.logs.data'));
        $this->assertNotEmpty($logs);
        $this->assertTrue($logs->every(fn ($l) => $l['action'] === 'login'));
    }

    public function test_audit_logs_filters_by_user_id(): void
    {
        $admin = User::factory()->create(['role' => 'super-admin']);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        AuthAuditLog::create(['user_id' => $user1->id, 'action' => 'login', 'created_at' => now()]);
        AuthAuditLog::create(['user_id' => $user2->id, 'action' => 'login', 'created_at' => now()]);

        $response = $this->actingAs($admin, 'api')
            ->getJson("/api/auth/audit-logs?user_id={$user1->id}");

        $response->assertStatus(200);
        $logs = collect($response->json('data.logs.data'));
        $this->assertNotEmpty($logs);
        $this->assertTrue($logs->every(fn ($l) => $l['user_id'] === $user1->id));
    }

    public function test_audit_logs_defaults_to_created_at_desc(): void
    {
        $admin = User::factory()->create(['role' => 'super-admin']);
        $user = User::factory()->create();

        $log1 = AuthAuditLog::create(['user_id' => $user->id, 'action' => 'login', 'created_at' => now()->subHours(2)]);
        $log2 = AuthAuditLog::create(['user_id' => $user->id, 'action' => 'logout', 'created_at' => now()->subHour()]);
        $log3 = AuthAuditLog::create(['user_id' => $user->id, 'action' => 'register', 'created_at' => now()]);

        $response = $this->actingAs($admin, 'api')->getJson("/api/auth/audit-logs?user_id={$user->id}");

        $response->assertStatus(200);
        $ids = collect($response->json('data.logs.data'))->pluck('id')->all();

        $this->assertEquals([$log3->id, $log2->id, $log1->id], $ids);
    }

    public function test_audit_logs_sorts_by_action_asc(): void
    {
        $admin = User::factory()->create(['role' => 'super-admin']);
        $user = User::factory()->create();

        AuthAuditLog::create(['user_id' => $user->id, 'action' => 'logout', 'created_at' => now()]);
        AuthAuditLog::create(['user_id' => $user->id, 'action' => 'login', 'created_at' => now()]);

        $response = $this->actingAs($admin, 'api')
            ->getJson("/api/auth/audit-logs?sort_by=action&sort_dir=asc&user_id={$user->id}");

        $response->assertStatus(200);
        $actions = collect($response->json('data.logs.data'))->pluck('action')->all();
        $this->assertEquals(['login', 'logout'], $actions);
    }

    public function test_audit_logs_falls_back_to_default_sort_for_non_allowlisted_column(): void
    {
        $admin = User::factory()->create(['role' => 'super-admin']);
        $user = User::factory()->create();
        $log1 = AuthAuditLog::create(['user_id' => $user->id, 'action' => 'login', 'created_at' => now()->subHour()]);
        $log2 = AuthAuditLog::create(['user_id' => $user->id, 'action' => 'logout', 'created_at' => now()]);

        $response = $this->actingAs($admin, 'api')
            ->getJson("/api/auth/audit-logs?sort_by=invalid_column__injection&user_id={$user->id}");

        $response->assertStatus(200);
        $ids = collect($response->json('data.logs.data'))->pluck('id')->all();
        $this->assertEquals([$log2->id, $log1->id], $ids);
    }

    public function test_audit_logs_returns_422_for_invalid_date_from(): void
    {
        $admin = User::factory()->create(['role' => 'super-admin']);

        $response = $this->actingAs($admin, 'api')
            ->getJson('/api/auth/audit-logs?date_from=not-a-date');

        $response->assertStatus(422);
    }

    public function test_audit_logs_returns_422_for_invalid_date_to(): void
    {
        $admin = User::factory()->create(['role' => 'super-admin']);

        $response = $this->actingAs($admin, 'api')
            ->getJson('/api/auth/audit-logs?date_to=2024-13-99');

        $response->assertStatus(422);
    }
}
