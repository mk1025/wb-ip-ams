<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\IpAddress;
use App\Models\IpAuditLog;

class IpAddressStoreTest extends IpFeatureTestCase
{
    public function test_creates_ip_address_and_returns_201(): void
    {
        $user = $this->user();

        $response = $this->actingAs($user)->postJson(self::IP_ADDRESSES_URL, [
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

    public function test_sets_owner_id_to_authenticated_user(): void
    {
        $user = $this->user();

        $this->actingAs($user)->postJson(self::IP_ADDRESSES_URL, [
            'ip_address' => '203.0.113.2',
            'label' => 'Owned IP',
        ]);

        $this->assertDatabaseHas('ip_addresses', ['ip_address' => '203.0.113.2', 'owner_id' => $user->id]);
    }

    public function test_accepts_valid_ipv4(): void
    {
        $this->actingAs($this->user())->postJson(self::IP_ADDRESSES_URL, [
            'ip_address' => '198.51.100.5',
            'label' => 'IPv4 Test',
        ])->assertStatus(201);
    }

    public function test_accepts_valid_ipv6(): void
    {
        $this->actingAs($this->user())->postJson(self::IP_ADDRESSES_URL, [
            'ip_address' => '2001:db8::1',
            'label' => 'IPv6 Test',
        ])->assertStatus(201);
    }

    public function test_returns_422_for_invalid_ip_format(): void
    {
        $this->actingAs($this->user())->postJson(self::IP_ADDRESSES_URL, [
            'ip_address' => 'not-an-ip-address',
            'label' => 'Bad IP',
        ])->assertStatus(422);
    }

    public function test_returns_422_for_missing_ip_address_field(): void
    {
        $this->actingAs($this->user())->postJson(self::IP_ADDRESSES_URL, [
            'label' => 'No IP Supplied',
        ])->assertStatus(422);
    }

    public function test_returns_422_for_comment_exceeding_max_length(): void
    {
        $this->actingAs($this->user())->postJson(self::IP_ADDRESSES_URL, [
            'ip_address' => '203.0.113.50',
            'label' => 'Max Comment',
            'comment' => str_repeat('a', 1001),
        ])->assertStatus(422);
    }

    public function test_creates_audit_log_with_create_action(): void
    {
        $user = $this->user();

        $this->actingAs($user)->postJson(self::IP_ADDRESSES_URL, [
            'ip_address' => '203.0.113.10',
            'label' => 'Audit Test',
        ]);

        $ip = IpAddress::where('ip_address', '203.0.113.10')->first();
        $this->assertDatabaseHas('ip_audit_logs', [
            'user_id' => $user->id,
            'action' => IpAuditLog::ACTION_CREATE,
            'entity_id' => $ip->id,
        ]);
    }

    public function test_audit_snapshot_contains_only_expected_keys(): void
    {
        $user = $this->user();

        $this->actingAs($user)->postJson(self::IP_ADDRESSES_URL, [
            'ip_address' => '192.0.2.55',
            'label' => 'Snapshot Test',
        ]);

        $ip = IpAddress::where('ip_address', '192.0.2.55')->first();
        $log = IpAuditLog::where('action', IpAuditLog::ACTION_CREATE)->where('entity_id', $ip->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals(['ip_address', 'label', 'comment', 'owner_id'], array_keys($log->new_value));
    }

    public function test_returns_401_for_unauthenticated_request(): void
    {
        $this->postJson(self::IP_ADDRESSES_URL, [
            'ip_address' => '203.0.113.99',
            'label' => 'Should Fail',
        ])->assertStatus(401);
    }
}
