<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\IpAddress;
use App\Models\IpAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IpAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sync_creates_new_user(): void
    {
        $response = $this->postJson('/api/internal/users/sync', [
            'id' => 9001,
            'email' => 'synced@example.com',
            'role' => 'user',
        ], ['X-Internal-Secret' => config('app.internal_secret')]);

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertDatabaseHas('users', ['email' => 'synced@example.com', 'role' => 'user']);
    }

    public function test_user_sync_updates_existing_user(): void
    {
        $existing = User::factory()->create(['email' => 'before@example.com', 'role' => 'user']);

        $response = $this->postJson('/api/internal/users/sync', [
            'id' => $existing->id,
            'email' => 'after@example.com',
            'role' => 'super-admin',
        ], ['X-Internal-Secret' => config('app.internal_secret')]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $existing->id, 'email' => 'after@example.com', 'role' => 'super-admin']);
        $this->assertDatabaseMissing('users', ['email' => 'before@example.com']);
    }

    public function test_user_sync_returns_401_without_secret_header(): void
    {
        $response = $this->postJson('/api/internal/users/sync', [
            'id' => 1, 'email' => 'x@example.com', 'role' => 'user',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_sync_returns_401_with_wrong_secret(): void
    {
        $response = $this->postJson('/api/internal/users/sync', [
            'id' => 1, 'email' => 'x@example.com', 'role' => 'user',
        ], ['X-Internal-Secret' => 'wrong-secret']);

        $response->assertStatus(401);
    }

    public function test_index_returns_200_with_paginated_list(): void
    {
        $user = User::factory()->create();
        IpAddress::create(['ip_address' => '10.0.0.1', 'label' => 'One', 'owner_id' => $user->id]);
        IpAddress::create(['ip_address' => '10.0.0.2', 'label' => 'Two', 'owner_id' => $user->id]);

        $response = $this->actingAs($user, 'api')->getJson('/api/ip-addresses');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'items' => ['data', 'current_page', 'total'],
                    'filter_options' => ['owners'],
                ],
            ]);
        $this->assertCount(2, $response->json('data.items.data'));
    }

    public function test_index_search_filters_by_ip_address(): void
    {
        $user = User::factory()->create();
        IpAddress::create(['ip_address' => '192.168.1.100', 'label' => 'Alpha', 'owner_id' => $user->id]);
        IpAddress::create(['ip_address' => '10.1.0.1', 'label' => 'Beta', 'owner_id' => $user->id]);

        $response = $this->actingAs($user, 'api')->getJson('/api/ip-addresses?search=192.168');

        $items = $response->json('data.items.data');
        $this->assertCount(1, $items);
        $this->assertEquals('192.168.1.100', $items[0]['ip_address']);
    }

    public function test_index_search_filters_by_label(): void
    {
        $user = User::factory()->create();
        IpAddress::create(['ip_address' => '10.1.1.1', 'label' => 'Production Server', 'owner_id' => $user->id]);
        IpAddress::create(['ip_address' => '10.1.1.2', 'label' => 'Dev Machine', 'owner_id' => $user->id]);

        $response = $this->actingAs($user, 'api')->getJson('/api/ip-addresses?search=Production');

        $items = $response->json('data.items.data');
        $this->assertCount(1, $items);
        $this->assertEquals('Production Server', $items[0]['label']);
    }

    public function test_index_ownership_mine_returns_only_own_ips(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        IpAddress::create(['ip_address' => '10.10.1.1', 'label' => 'Mine', 'owner_id' => $user->id]);
        IpAddress::create(['ip_address' => '10.10.1.2', 'label' => 'Theirs', 'owner_id' => $other->id]);

        $response = $this->actingAs($user, 'api')->getJson('/api/ip-addresses?ownership=mine');

        $items = $response->json('data.items.data');
        $this->assertCount(1, $items);
        $this->assertEquals($user->id, $items[0]['owner_id']);
    }

    public function test_index_ownership_others_returns_only_other_users_ips(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        IpAddress::create(['ip_address' => '10.20.1.1', 'label' => 'Mine', 'owner_id' => $user->id]);
        IpAddress::create(['ip_address' => '10.20.1.2', 'label' => 'Theirs', 'owner_id' => $other->id]);

        $response = $this->actingAs($user, 'api')->getJson('/api/ip-addresses?ownership=others');

        $items = $response->json('data.items.data');
        $this->assertCount(1, $items);
        $this->assertEquals($other->id, $items[0]['owner_id']);
    }

    public function test_index_owner_id_filter_takes_precedence_over_ownership(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        IpAddress::create(['ip_address' => '10.30.1.1', 'label' => 'Mine', 'owner_id' => $user->id]);
        IpAddress::create(['ip_address' => '10.30.1.2', 'label' => 'Theirs', 'owner_id' => $other->id]);

        // ownership=mine would normally exclude $other's IPs, but explicit owner_id overrides it
        $response = $this->actingAs($user, 'api')
            ->getJson("/api/ip-addresses?ownership=mine&owner_id={$other->id}");

        $items = $response->json('data.items.data');
        $this->assertCount(1, $items);
        $this->assertEquals($other->id, $items[0]['owner_id']);
    }

    public function test_index_date_from_filter(): void
    {
        $user = User::factory()->create();
        $old = IpAddress::create(['ip_address' => '172.16.1.1', 'label' => 'Old', 'owner_id' => $user->id]);
        IpAddress::create(['ip_address' => '172.16.1.2', 'label' => 'New', 'owner_id' => $user->id]);
        DB::table('ip_addresses')->where('id', $old->id)->update(['created_at' => now()->subDays(10)->toDateTimeString()]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/ip-addresses?date_from='.now()->subDay()->toDateString());

        $items = $response->json('data.items.data');
        $this->assertCount(1, $items);
        $this->assertEquals('New', $items[0]['label']);
    }

    public function test_index_date_to_filter(): void
    {
        $user = User::factory()->create();
        $old = IpAddress::create(['ip_address' => '172.17.1.1', 'label' => 'Old', 'owner_id' => $user->id]);
        IpAddress::create(['ip_address' => '172.17.1.2', 'label' => 'New', 'owner_id' => $user->id]);
        DB::table('ip_addresses')->where('id', $old->id)->update(['created_at' => now()->subDays(10)->toDateTimeString()]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/ip-addresses?date_to='.now()->subDays(5)->toDateString());

        $items = $response->json('data.items.data');
        $this->assertCount(1, $items);
        $this->assertEquals('Old', $items[0]['label']);
    }

    public function test_index_includes_filter_options_owners(): void
    {
        $user = User::factory()->create();
        IpAddress::create(['ip_address' => '172.20.1.1', 'label' => 'Test', 'owner_id' => $user->id]);

        $response = $this->actingAs($user, 'api')->getJson('/api/ip-addresses');

        $response->assertStatus(200);
        $owners = $response->json('data.filter_options.owners');
        $this->assertNotEmpty($owners);
        $this->assertEquals($user->id, $owners[0]['id']);
        $this->assertEquals($user->email, $owners[0]['email']);
    }

    public function test_index_sorts_by_label_asc(): void
    {
        $user = User::factory()->create();
        IpAddress::create(['ip_address' => '172.21.1.1', 'label' => 'Zebra', 'owner_id' => $user->id]);
        IpAddress::create(['ip_address' => '172.21.1.2', 'label' => 'Alpha', 'owner_id' => $user->id]);

        $response = $this->actingAs($user, 'api')->getJson('/api/ip-addresses?sort_by=label&sort_dir=asc');

        $labels = collect($response->json('data.items.data'))->pluck('label')->all();
        $this->assertEquals(['Alpha', 'Zebra'], $labels);
    }

    public function test_index_defaults_to_created_at_desc(): void
    {
        $user = User::factory()->create();
        $first = IpAddress::create(['ip_address' => '172.22.1.1', 'label' => 'First', 'owner_id' => $user->id]);
        IpAddress::create(['ip_address' => '172.22.1.2', 'label' => 'Second', 'owner_id' => $user->id]);
        DB::table('ip_addresses')->where('id', $first->id)->update(['created_at' => now()->subHour()->toDateTimeString()]);

        $response = $this->actingAs($user, 'api')->getJson('/api/ip-addresses');

        $labels = collect($response->json('data.items.data'))->pluck('label')->all();
        $this->assertEquals(['Second', 'First'], $labels);
    }

    public function test_index_returns_401_for_unauthenticated_request(): void
    {
        $response = $this->getJson('/api/ip-addresses');

        $response->assertStatus(401);
    }

    public function test_store_creates_ip_address_and_returns_201(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/ip-addresses', [
            'ip_address' => '203.0.113.1',
            'label' => 'Test Server',
            'comment' => 'A test IP',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['id', 'ip_address', 'label', 'comment', 'owner_id', 'created_at'],
            ])
            ->assertJsonPath('data.ip_address', '203.0.113.1');
        $this->assertDatabaseHas('ip_addresses', ['ip_address' => '203.0.113.1']);
    }

    public function test_store_sets_owner_id_to_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')->postJson('/api/ip-addresses', [
            'ip_address' => '203.0.113.2',
            'label' => 'Owned IP',
        ]);

        $this->assertDatabaseHas('ip_addresses', ['ip_address' => '203.0.113.2', 'owner_id' => $user->id]);
    }

    public function test_store_accepts_valid_ipv4(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/ip-addresses', [
            'ip_address' => '198.51.100.5',
            'label' => 'IPv4 Test',
        ]);

        $response->assertStatus(201);
    }

    public function test_store_accepts_valid_ipv6(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/ip-addresses', [
            'ip_address' => '2001:db8::1',
            'label' => 'IPv6 Test',
        ]);

        $response->assertStatus(201);
    }

    public function test_store_returns_422_for_invalid_ip_format(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/ip-addresses', [
            'ip_address' => 'not-an-ip-address',
            'label' => 'Bad IP',
        ]);

        $response->assertStatus(422);
    }

    public function test_store_returns_422_for_missing_ip_address_field(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/ip-addresses', [
            'label' => 'No IP Supplied',
        ]);

        $response->assertStatus(422);
    }

    public function test_store_creates_audit_log_with_create_action(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')->postJson('/api/ip-addresses', [
            'ip_address' => '203.0.113.10',
            'label' => 'Audit Test',
        ]);

        $ip = IpAddress::where('ip_address', '203.0.113.10')->first();
        $this->assertDatabaseHas('ip_audit_logs', [
            'user_id' => $user->id,
            'action' => 'create',
            'entity_id' => $ip->id,
        ]);
    }

    public function test_store_returns_401_for_unauthenticated_request(): void
    {
        $response = $this->postJson('/api/ip-addresses', [
            'ip_address' => '203.0.113.99',
            'label' => 'Should Fail',
        ]);

        $response->assertStatus(401);
    }

    public function test_show_returns_200_with_ip_resource(): void
    {
        $user = User::factory()->create();
        $ip = IpAddress::create(['ip_address' => '198.51.100.1', 'label' => 'Show Test', 'owner_id' => $user->id]);

        $response = $this->actingAs($user, 'api')->getJson("/api/ip-addresses/{$ip->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.ip_address', '198.51.100.1')
            ->assertJsonPath('data.id', $ip->id);
    }

    public function test_show_returns_404_for_nonexistent_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->getJson('/api/ip-addresses/9999');

        $response->assertStatus(404);
    }

    public function test_show_returns_401_for_unauthenticated_request(): void
    {
        $response = $this->getJson('/api/ip-addresses/1');

        $response->assertStatus(401);
    }

    public function test_update_owner_can_update_own_ip(): void
    {
        $user = User::factory()->create();
        $ip = IpAddress::create(['ip_address' => '198.51.100.10', 'label' => 'Old Label', 'owner_id' => $user->id]);

        $response = $this->actingAs($user, 'api')->putJson("/api/ip-addresses/{$ip->id}", [
            'label' => 'New Label',
        ]);

        $response->assertStatus(200)->assertJsonPath('data.label', 'New Label');
        $this->assertDatabaseHas('ip_addresses', ['id' => $ip->id, 'label' => 'New Label']);
    }

    public function test_update_super_admin_can_update_any_ip(): void
    {
        $admin = User::factory()->create(['role' => 'super-admin']);
        $owner = User::factory()->create();
        $ip = IpAddress::create(['ip_address' => '198.51.100.20', 'label' => 'Old Label', 'owner_id' => $owner->id]);

        $response = $this->actingAs($admin, 'api')->putJson("/api/ip-addresses/{$ip->id}", [
            'label' => 'Admin Updated',
        ]);

        $response->assertStatus(200)->assertJsonPath('data.label', 'Admin Updated');
    }

    public function test_update_regular_user_cannot_update_another_users_ip(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $ip = IpAddress::create(['ip_address' => '198.51.100.30', 'label' => 'Not Mine', 'owner_id' => $other->id]);

        $response = $this->actingAs($user, 'api')->putJson("/api/ip-addresses/{$ip->id}", [
            'label' => 'Stolen Label',
        ]);

        $response->assertStatus(403);
    }

    public function test_update_returns_404_for_nonexistent_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->putJson('/api/ip-addresses/9999', [
            'label' => 'Ghost',
        ]);

        $response->assertStatus(404);
    }

    public function test_update_creates_audit_log_with_old_and_new_values(): void
    {
        $user = User::factory()->create();
        $ip = IpAddress::create(['ip_address' => '198.51.100.40', 'label' => 'Before', 'owner_id' => $user->id]);

        $this->actingAs($user, 'api')->putJson("/api/ip-addresses/{$ip->id}", [
            'label' => 'After',
        ]);

        $log = IpAuditLog::where('action', 'update')->where('entity_id', $ip->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('Before', $log->old_value['label']);
        $this->assertEquals('After', $log->new_value['label']);
        $this->assertEquals(array_keys($log->old_value), array_keys($log->new_value));
    }

    public function test_update_returns_401_for_unauthenticated_request(): void
    {
        $response = $this->putJson('/api/ip-addresses/1', ['label' => 'Test']);

        $response->assertStatus(401);
    }

    public function test_destroy_super_admin_can_delete_any_ip(): void
    {
        $admin = User::factory()->create(['role' => 'super-admin']);
        $owner = User::factory()->create();
        $ip = IpAddress::create(['ip_address' => '198.51.100.50', 'label' => 'To Delete', 'owner_id' => $owner->id]);

        $response = $this->actingAs($admin, 'api')->deleteJson("/api/ip-addresses/{$ip->id}");

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertDatabaseMissing('ip_addresses', ['id' => $ip->id]);
    }

    public function test_destroy_regular_user_cannot_delete(): void
    {
        $user = User::factory()->create();
        $ip = IpAddress::create(['ip_address' => '198.51.100.60', 'label' => 'Protected', 'owner_id' => $user->id]);

        $response = $this->actingAs($user, 'api')->deleteJson("/api/ip-addresses/{$ip->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('ip_addresses', ['id' => $ip->id]);
    }

    public function test_destroy_returns_404_for_nonexistent_id(): void
    {
        $admin = User::factory()->create(['role' => 'super-admin']);

        $response = $this->actingAs($admin, 'api')->deleteJson('/api/ip-addresses/9999');

        $response->assertStatus(404);
    }

    public function test_destroy_creates_audit_log_with_delete_action(): void
    {
        $admin = User::factory()->create(['role' => 'super-admin']);
        $ip = IpAddress::create(['ip_address' => '198.51.100.70', 'label' => 'Log Test', 'owner_id' => $admin->id]);

        $this->actingAs($admin, 'api')->deleteJson("/api/ip-addresses/{$ip->id}");

        $this->assertDatabaseHas('ip_audit_logs', [
            'user_id' => $admin->id,
            'action' => 'delete',
            'entity_id' => $ip->id,
        ]);
    }

    public function test_destroy_returns_401_for_unauthenticated_request(): void
    {
        $response = $this->deleteJson('/api/ip-addresses/1');

        $response->assertStatus(401);
    }

    public function test_audit_logs_returns_200_with_paginated_logs_for_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'super-admin']);
        $ip = IpAddress::create(['ip_address' => '198.51.100.80', 'label' => 'Audit', 'owner_id' => $admin->id]);
        IpAuditLog::create(['user_id' => $admin->id, 'action' => 'create', 'entity_id' => $ip->id, 'created_at' => now()]);

        $response = $this->actingAs($admin, 'api')->getJson('/api/ip-addresses/audit-logs');

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

        $response = $this->actingAs($user, 'api')->getJson('/api/ip-addresses/audit-logs');

        $response->assertStatus(403);
    }

    public function test_audit_logs_returns_401_for_unauthenticated_request(): void
    {
        $response = $this->getJson('/api/ip-addresses/audit-logs');

        $response->assertStatus(401);
    }

    public function test_audit_logs_filters_by_action(): void
    {
        $admin = User::factory()->create(['role' => 'super-admin']);
        $ip = IpAddress::create(['ip_address' => '198.51.100.90', 'label' => 'Filter', 'owner_id' => $admin->id]);
        IpAuditLog::create(['user_id' => $admin->id, 'action' => 'create', 'entity_id' => $ip->id, 'created_at' => now()]);
        IpAuditLog::create(['user_id' => $admin->id, 'action' => 'update', 'entity_id' => $ip->id, 'created_at' => now()]);

        $response = $this->actingAs($admin, 'api')->getJson('/api/ip-addresses/audit-logs?action=create');

        $logs = collect($response->json('data.logs.data'));
        $this->assertNotEmpty($logs);
        $this->assertTrue($logs->every(fn ($l) => $l['action'] === 'create'));
    }

    public function test_audit_logs_filters_by_user_id(): void
    {
        $admin = User::factory()->create(['role' => 'super-admin']);
        $user = User::factory()->create();
        $ip = IpAddress::create(['ip_address' => '198.51.100.91', 'label' => 'User Filter', 'owner_id' => $admin->id]);
        IpAuditLog::create(['user_id' => $admin->id, 'action' => 'create', 'entity_id' => $ip->id, 'created_at' => now()]);
        IpAuditLog::create(['user_id' => $user->id, 'action' => 'update', 'entity_id' => $ip->id, 'created_at' => now()]);

        $response = $this->actingAs($admin, 'api')->getJson("/api/ip-addresses/audit-logs?user_id={$user->id}");

        $logs = collect($response->json('data.logs.data'));
        $this->assertNotEmpty($logs);
        $this->assertTrue($logs->every(fn ($l) => $l['user_id'] === $user->id));
    }

    public function test_audit_logs_filters_by_entity_id(): void
    {
        $admin = User::factory()->create(['role' => 'super-admin']);
        $ip1 = IpAddress::create(['ip_address' => '198.51.100.92', 'label' => 'IP1', 'owner_id' => $admin->id]);
        $ip2 = IpAddress::create(['ip_address' => '198.51.100.93', 'label' => 'IP2', 'owner_id' => $admin->id]);
        IpAuditLog::create(['user_id' => $admin->id, 'action' => 'create', 'entity_id' => $ip1->id, 'created_at' => now()]);
        IpAuditLog::create(['user_id' => $admin->id, 'action' => 'create', 'entity_id' => $ip2->id, 'created_at' => now()]);

        $response = $this->actingAs($admin, 'api')->getJson("/api/ip-addresses/audit-logs?entity_id={$ip1->id}");

        $logs = collect($response->json('data.logs.data'));
        $this->assertNotEmpty($logs);
        $this->assertTrue($logs->every(fn ($l) => $l['entity_id'] === $ip1->id));
    }

    public function test_audit_logs_defaults_to_created_at_desc(): void
    {
        $admin = User::factory()->create(['role' => 'super-admin']);
        $ip = IpAddress::create(['ip_address' => '198.51.100.94', 'label' => 'Sort', 'owner_id' => $admin->id]);
        $log1 = IpAuditLog::create(['user_id' => $admin->id, 'action' => 'create', 'entity_id' => $ip->id, 'created_at' => now()->subHour()]);
        $log2 = IpAuditLog::create(['user_id' => $admin->id, 'action' => 'update', 'entity_id' => $ip->id, 'created_at' => now()]);

        $response = $this->actingAs($admin, 'api')->getJson("/api/ip-addresses/audit-logs?entity_id={$ip->id}");

        $ids = collect($response->json('data.logs.data'))->pluck('id')->all();
        $this->assertEquals([$log2->id, $log1->id], $ids);
    }

    public function test_audit_logs_sorts_by_action_asc(): void
    {
        $admin = User::factory()->create(['role' => 'super-admin']);
        $ip = IpAddress::create(['ip_address' => '198.51.100.95', 'label' => 'Sort2', 'owner_id' => $admin->id]);
        IpAuditLog::create(['user_id' => $admin->id, 'action' => 'update', 'entity_id' => $ip->id, 'created_at' => now()]);
        IpAuditLog::create(['user_id' => $admin->id, 'action' => 'create', 'entity_id' => $ip->id, 'created_at' => now()]);

        $response = $this->actingAs($admin, 'api')
            ->getJson("/api/ip-addresses/audit-logs?sortBy=action&sortDir=asc&entity_id={$ip->id}");

        $actions = collect($response->json('data.logs.data'))->pluck('action')->all();
        $this->assertEquals(['create', 'update'], $actions);
    }

    public function test_audit_logs_returns_422_for_invalid_date_from(): void
    {
        $admin = User::factory()->create(['role' => 'super-admin']);

        $response = $this->actingAs($admin, 'api')
            ->getJson('/api/ip-addresses/audit-logs?date_from=not-a-date');

        $response->assertStatus(422);
    }

    public function test_audit_logs_returns_422_for_invalid_date_to(): void
    {
        $admin = User::factory()->create(['role' => 'super-admin']);

        $response = $this->actingAs($admin, 'api')
            ->getJson('/api/ip-addresses/audit-logs?date_to=2024-13-99');

        $response->assertStatus(422);
    }

    public function test_stats_returns_correct_counts(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        IpAddress::create(['ip_address' => '198.51.100.100', 'label' => 'Mine', 'owner_id' => $user->id]);
        IpAddress::create(['ip_address' => '198.51.100.101', 'label' => 'Mine2', 'owner_id' => $user->id]);
        IpAddress::create(['ip_address' => '198.51.100.102', 'label' => 'Theirs', 'owner_id' => $other->id]);

        $response = $this->actingAs($user, 'api')->getJson('/api/ip-addresses/stats');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 3)
            ->assertJsonPath('data.mine', 2)
            ->assertJsonPath('data.others', 1);
    }

    public function test_stats_returns_401_for_unauthenticated_request(): void
    {
        $response = $this->getJson('/api/ip-addresses/stats');

        $response->assertStatus(401);
    }
}
