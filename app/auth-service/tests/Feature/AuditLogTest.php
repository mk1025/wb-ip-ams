<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuthAuditLog;
use App\Models\User;
use App\Services\AuthAuditLogService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AuditLogTest extends AuthFeatureTestCase
{
    public function test_register_creates_audit_log_entry(): void
    {
        $this->postJson(self::REGISTER_URL, [
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas(self::AUDIT_LOGS_TABLE, ['action' => AuthAuditLog::ACTION_REGISTER]);
    }

    public function test_login_creates_audit_log_entry(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret1234')]);

        $this->postJson(self::LOGIN_URL, [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $this->assertDatabaseHas(self::AUDIT_LOGS_TABLE, [
            'user_id' => $user->id,
            'action' => AuthAuditLog::ACTION_LOGIN,
        ]);
    }

    public function test_logout_creates_audit_log_entry(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')->postJson(self::LOGOUT_URL);

        $this->assertDatabaseHas(self::AUDIT_LOGS_TABLE, [
            'user_id' => $user->id,
            'action' => AuthAuditLog::ACTION_LOGOUT,
        ]);
    }

    public function test_audit_logs_returns_200_with_paginated_data_for_super_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $response = $this->actingAs($admin, 'api')->getJson(self::AUDIT_LOGS_URL);

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
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user, 'api')->getJson(self::AUDIT_LOGS_URL);

        $response->assertStatus(403);
    }

    public function test_audit_logs_returns_401_for_unauthenticated_request(): void
    {
        $response = $this->getJson(self::AUDIT_LOGS_URL);

        $response->assertStatus(401);
    }

    public function test_audit_logs_filters_by_action(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $user = User::factory()->create();
        AuthAuditLog::create(['user_id' => $user->id, 'action' => AuthAuditLog::ACTION_LOGIN, 'created_at' => now()]);
        AuthAuditLog::create(['user_id' => $user->id, 'action' => AuthAuditLog::ACTION_LOGOUT, 'created_at' => now()]);

        $response = $this->actingAs($admin, 'api')
            ->getJson(self::AUDIT_LOGS_URL.'?action=login');

        $response->assertStatus(200);
        $logs = collect($response->json('data.logs.data'));
        $this->assertNotEmpty($logs);
        $this->assertTrue($logs->every(fn ($l) => $l['action'] === AuthAuditLog::ACTION_LOGIN));
    }

    public function test_audit_logs_filters_by_user_id(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        AuthAuditLog::create(['user_id' => $user1->id, 'action' => AuthAuditLog::ACTION_LOGIN, 'created_at' => now()]);
        AuthAuditLog::create(['user_id' => $user2->id, 'action' => AuthAuditLog::ACTION_LOGIN, 'created_at' => now()]);

        $response = $this->actingAs($admin, 'api')
            ->getJson(self::AUDIT_LOGS_URL."?user_id={$user1->id}");

        $response->assertStatus(200);
        $logs = collect($response->json('data.logs.data'));
        $this->assertNotEmpty($logs);
        $this->assertTrue($logs->every(fn ($l) => $l['user_id'] === $user1->id));
    }

    public function test_audit_logs_defaults_to_created_at_desc(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $user = User::factory()->create();

        $log1 = AuthAuditLog::create(['user_id' => $user->id, 'action' => AuthAuditLog::ACTION_LOGIN, 'created_at' => now()->subHours(2)]);
        $log2 = AuthAuditLog::create(['user_id' => $user->id, 'action' => AuthAuditLog::ACTION_LOGOUT, 'created_at' => now()->subHour()]);
        $log3 = AuthAuditLog::create(['user_id' => $user->id, 'action' => AuthAuditLog::ACTION_REGISTER, 'created_at' => now()]);

        $response = $this->actingAs($admin, 'api')->getJson(self::AUDIT_LOGS_URL."?user_id={$user->id}");

        $response->assertStatus(200);
        $ids = collect($response->json('data.logs.data'))->pluck('id')->all();

        $this->assertEquals([$log3->id, $log2->id, $log1->id], $ids);
    }

    public function test_audit_logs_sorts_by_action_asc(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $user = User::factory()->create();

        AuthAuditLog::create(['user_id' => $user->id, 'action' => AuthAuditLog::ACTION_LOGOUT, 'created_at' => now()]);
        AuthAuditLog::create(['user_id' => $user->id, 'action' => AuthAuditLog::ACTION_LOGIN, 'created_at' => now()]);

        $response = $this->actingAs($admin, 'api')
            ->getJson(self::AUDIT_LOGS_URL."?sort_by=action&sort_dir=asc&user_id={$user->id}");

        $response->assertStatus(200);
        $actions = collect($response->json('data.logs.data'))->pluck('action')->all();
        $this->assertEquals([AuthAuditLog::ACTION_LOGIN, AuthAuditLog::ACTION_LOGOUT], $actions);
    }

    public function test_audit_logs_falls_back_to_default_sort_for_non_allowlisted_column(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $user = User::factory()->create();
        $log1 = AuthAuditLog::create(['user_id' => $user->id, 'action' => AuthAuditLog::ACTION_LOGIN, 'created_at' => now()->subHour()]);
        $log2 = AuthAuditLog::create(['user_id' => $user->id, 'action' => AuthAuditLog::ACTION_LOGOUT, 'created_at' => now()]);

        $response = $this->actingAs($admin, 'api')
            ->getJson(self::AUDIT_LOGS_URL."?sort_by=invalid_column__injection&user_id={$user->id}");

        $response->assertStatus(200);
        $ids = collect($response->json('data.logs.data'))->pluck('id')->all();
        $this->assertEquals([$log2->id, $log1->id], $ids);
    }

    public function test_audit_logs_returns_422_for_invalid_date_from(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $response = $this->actingAs($admin, 'api')
            ->getJson(self::AUDIT_LOGS_URL.'?date_from=not-a-date');

        $response->assertStatus(422);
    }

    public function test_audit_logs_returns_422_for_invalid_date_to(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $response = $this->actingAs($admin, 'api')
            ->getJson(self::AUDIT_LOGS_URL.'?date_to=2024-13-99');

        $response->assertStatus(422);
    }

    public function test_login_invalidates_audit_filter_caches(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($admin, 'api')->getJson(self::AUDIT_LOGS_URL);
        $this->assertTrue(Cache::has(AuthAuditLogService::CACHE_KEY_USER_OPTIONS));

        Http::fake(['*/api/internal/users/sync' => Http::response([], 200)]);

        $this->postJson(self::LOGIN_URL, ['email' => $user->email, 'password' => 'password']);

        $this->assertFalse(Cache::has(AuthAuditLogService::CACHE_KEY_USER_OPTIONS));
        $this->assertFalse(Cache::has(AuthAuditLogService::CACHE_KEY_ACTION_OPTIONS));
    }
}
