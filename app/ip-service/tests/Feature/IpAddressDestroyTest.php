<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\IpAddress;
use App\Models\IpAuditLog;

class IpAddressDestroyTest extends IpFeatureTestCase
{
    public function test_super_admin_can_delete_any_ip(): void
    {
        $ip = IpAddress::create(['ip_address' => '198.51.100.50', 'label' => 'To Delete', 'owner_id' => $this->user()->id]);

        $this->actingAs($this->admin())->deleteJson(self::IP_ADDRESSES_URL."/{$ip->id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('ip_addresses', ['id' => $ip->id]);
    }

    public function test_regular_user_cannot_delete(): void
    {
        $user = $this->user();

        $ip = IpAddress::create(['ip_address' => '198.51.100.60', 'label' => 'Protected', 'owner_id' => $user->id]);

        $this->actingAs($user)->deleteJson(self::IP_ADDRESSES_URL."/{$ip->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('ip_addresses', ['id' => $ip->id]);
    }

    public function test_returns_404_for_nonexistent_id(): void
    {
        $this->actingAs($this->admin())->deleteJson(self::IP_ADDRESSES_URL.'/9999')
            ->assertStatus(404);
    }

    public function test_creates_audit_log_with_delete_action(): void
    {
        $admin = $this->admin();

        $ip = IpAddress::create(['ip_address' => '198.51.100.70', 'label' => 'Log Test', 'owner_id' => $admin->id]);

        $this->actingAs($admin)->deleteJson(self::IP_ADDRESSES_URL."/{$ip->id}");

        $this->assertDatabaseHas('ip_audit_logs', [
            'user_id' => $admin->id,
            'action' => IpAuditLog::ACTION_DELETE,
            'entity_id' => $ip->id,
        ]);
    }

    public function test_audit_snapshot_contains_only_expected_keys(): void
    {
        $admin = $this->admin();

        $ip = IpAddress::create(['ip_address' => '192.0.2.66', 'label' => 'Destroy Snap', 'owner_id' => $admin->id]);

        $this->actingAs($admin)->deleteJson(self::IP_ADDRESSES_URL."/{$ip->id}");

        $log = IpAuditLog::where('action', IpAuditLog::ACTION_DELETE)->where('entity_id', $ip->id)->first();

        $this->assertNotNull($log);

        $this->assertEquals(['ip_address', 'label', 'comment', 'owner_id'], array_keys($log->old_value));
    }

    public function test_returns_401_for_unauthenticated_request(): void
    {
        $this->deleteJson(self::IP_ADDRESSES_URL.'/1')->assertStatus(401);
    }
}
