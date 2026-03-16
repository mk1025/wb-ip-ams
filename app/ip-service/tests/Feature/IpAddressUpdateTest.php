<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\IpAddress;
use App\Models\IpAuditLog;

class IpAddressUpdateTest extends IpFeatureTestCase
{
    public function test_owner_can_update_own_ip(): void
    {
        $user = $this->user();
        $ip = IpAddress::create(['ip_address' => '198.51.100.10', 'label' => 'Old Label', 'owner_id' => $user->id]);

        $this->actingAs($user)->putJson(self::IP_ADDRESSES_URL."/{$ip->id}", ['label' => 'New Label'])
            ->assertStatus(200)
            ->assertJsonPath('data.label', 'New Label');
        $this->assertDatabaseHas('ip_addresses', ['id' => $ip->id, 'label' => 'New Label']);
    }

    public function test_super_admin_can_update_any_ip(): void
    {
        $owner = $this->user();
        $ip = IpAddress::create(['ip_address' => '198.51.100.20', 'label' => 'Old Label', 'owner_id' => $owner->id]);

        $this->actingAs($this->admin())->putJson(self::IP_ADDRESSES_URL."/{$ip->id}", ['label' => 'Admin Updated'])
            ->assertStatus(200)
            ->assertJsonPath('data.label', 'Admin Updated');
    }

    public function test_regular_user_cannot_update_another_users_ip(): void
    {
        $other = $this->user();
        $ip = IpAddress::create(['ip_address' => '198.51.100.30', 'label' => 'Not Mine', 'owner_id' => $other->id]);

        $this->actingAs($this->user())->putJson(self::IP_ADDRESSES_URL."/{$ip->id}", ['label' => 'Stolen Label'])
            ->assertStatus(403);
    }

    public function test_returns_404_for_nonexistent_id(): void
    {
        $this->actingAs($this->user())->putJson(self::IP_ADDRESSES_URL.'/9999', ['label' => 'Ghost'])
            ->assertStatus(404);
    }

    public function test_returns_422_for_comment_exceeding_max_length(): void
    {
        $user = $this->user();
        $ip = IpAddress::create(['ip_address' => '203.0.113.51', 'label' => 'Base', 'owner_id' => $user->id]);

        $this->actingAs($user)->putJson(self::IP_ADDRESSES_URL."/{$ip->id}", [
            'label' => 'Updated',
            'comment' => str_repeat('a', 1001),
        ])->assertStatus(422);
    }

    public function test_creates_audit_log_with_old_and_new_values(): void
    {
        $user = $this->user();
        $ip = IpAddress::create(['ip_address' => '198.51.100.40', 'label' => 'Before', 'owner_id' => $user->id]);

        $this->actingAs($user)->putJson(self::IP_ADDRESSES_URL."/{$ip->id}", ['label' => 'After']);

        $log = IpAuditLog::where('action', IpAuditLog::ACTION_UPDATE)->where('entity_id', $ip->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('Before', $log->old_value['label']);
        $this->assertEquals('After', $log->new_value['label']);
        $this->assertEquals(array_keys($log->old_value), array_keys($log->new_value));
    }

    public function test_returns_401_for_unauthenticated_request(): void
    {
        $this->putJson(self::IP_ADDRESSES_URL.'/1', ['label' => 'Test'])->assertStatus(401);
    }
}
