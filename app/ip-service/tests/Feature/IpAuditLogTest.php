<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\IpAddress;
use App\Models\IpAuditLog;

class IpAuditLogTest extends IpFeatureTestCase
{
    public function test_returns_200_with_paginated_logs_for_super_admin(): void
    {
        $admin = $this->admin();
        $ip = IpAddress::create(['ip_address' => '198.51.100.80', 'label' => 'Audit', 'owner_id' => $admin->id]);
        IpAuditLog::create(['user_id' => $admin->id, 'action' => IpAuditLog::ACTION_CREATE, 'entity_id' => $ip->id, 'created_at' => now()]);

        $this->actingAs($admin)->getJson(self::AUDIT_LOGS_URL)
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'logs' => ['data', 'current_page', 'total'],
                    'filter_options' => ['users', 'actions'],
                ],
            ]);
    }

    public function test_returns_403_for_regular_user(): void
    {
        $this->actingAs($this->user())->getJson(self::AUDIT_LOGS_URL)->assertStatus(403);
    }

    public function test_returns_401_for_unauthenticated_request(): void
    {
        $this->getJson(self::AUDIT_LOGS_URL)->assertStatus(401);
    }

    public function test_filters_by_action(): void
    {
        $admin = $this->admin();
        $ip = IpAddress::create(['ip_address' => '198.51.100.90', 'label' => 'Filter', 'owner_id' => $admin->id]);
        IpAuditLog::create(['user_id' => $admin->id, 'action' => IpAuditLog::ACTION_CREATE, 'entity_id' => $ip->id, 'created_at' => now()]);
        IpAuditLog::create(['user_id' => $admin->id, 'action' => IpAuditLog::ACTION_UPDATE, 'entity_id' => $ip->id, 'created_at' => now()]);

        $logs = collect($this->actingAs($admin)
            ->getJson(self::AUDIT_LOGS_URL.'?action='.IpAuditLog::ACTION_CREATE)
            ->json('data.logs.data'));

        $this->assertNotEmpty($logs);
        $this->assertTrue($logs->every(fn ($l) => $l['action'] === IpAuditLog::ACTION_CREATE));
    }

    public function test_filters_by_user_id(): void
    {
        $admin = $this->admin();
        $user = $this->user();
        $ip = IpAddress::create(['ip_address' => '198.51.100.91', 'label' => 'User Filter', 'owner_id' => $admin->id]);
        IpAuditLog::create(['user_id' => $admin->id, 'action' => IpAuditLog::ACTION_CREATE, 'entity_id' => $ip->id, 'created_at' => now()]);
        IpAuditLog::create(['user_id' => $user->id, 'action' => IpAuditLog::ACTION_UPDATE, 'entity_id' => $ip->id, 'created_at' => now()]);

        $logs = collect($this->actingAs($admin)
            ->getJson(self::AUDIT_LOGS_URL."?user_id={$user->id}")
            ->json('data.logs.data'));

        $this->assertNotEmpty($logs);
        $this->assertTrue($logs->every(fn ($l) => $l['user_id'] === $user->id));
    }

    public function test_filters_by_entity_id(): void
    {
        $admin = $this->admin();
        $ip1 = IpAddress::create(['ip_address' => '198.51.100.92', 'label' => 'IP1', 'owner_id' => $admin->id]);
        $ip2 = IpAddress::create(['ip_address' => '198.51.100.93', 'label' => 'IP2', 'owner_id' => $admin->id]);
        IpAuditLog::create(['user_id' => $admin->id, 'action' => IpAuditLog::ACTION_CREATE, 'entity_id' => $ip1->id, 'created_at' => now()]);
        IpAuditLog::create(['user_id' => $admin->id, 'action' => IpAuditLog::ACTION_CREATE, 'entity_id' => $ip2->id, 'created_at' => now()]);

        $logs = collect($this->actingAs($admin)
            ->getJson(self::AUDIT_LOGS_URL."?entity_id={$ip1->id}")
            ->json('data.logs.data'));

        $this->assertNotEmpty($logs);
        $this->assertTrue($logs->every(fn ($l) => $l['entity_id'] === $ip1->id));
    }

    public function test_defaults_to_created_at_desc(): void
    {
        $admin = $this->admin();
        $ip = IpAddress::create(['ip_address' => '198.51.100.94', 'label' => 'Sort', 'owner_id' => $admin->id]);
        $log1 = IpAuditLog::create(['user_id' => $admin->id, 'action' => IpAuditLog::ACTION_CREATE, 'entity_id' => $ip->id, 'created_at' => now()->subHour()]);
        $log2 = IpAuditLog::create(['user_id' => $admin->id, 'action' => IpAuditLog::ACTION_UPDATE, 'entity_id' => $ip->id, 'created_at' => now()]);

        $ids = collect($this->actingAs($admin)
            ->getJson(self::AUDIT_LOGS_URL."?entity_id={$ip->id}")
            ->json('data.logs.data'))->pluck('id')->all();

        $this->assertEquals([$log2->id, $log1->id], $ids);
    }

    public function test_sorts_by_action_asc(): void
    {
        $admin = $this->admin();
        $ip = IpAddress::create(['ip_address' => '198.51.100.95', 'label' => 'Sort2', 'owner_id' => $admin->id]);
        IpAuditLog::create(['user_id' => $admin->id, 'action' => IpAuditLog::ACTION_UPDATE, 'entity_id' => $ip->id, 'created_at' => now()]);
        IpAuditLog::create(['user_id' => $admin->id, 'action' => IpAuditLog::ACTION_CREATE, 'entity_id' => $ip->id, 'created_at' => now()]);

        $actions = collect($this->actingAs($admin)
            ->getJson(self::AUDIT_LOGS_URL."?sort_by=action&sort_dir=asc&entity_id={$ip->id}")
            ->json('data.logs.data'))->pluck('action')->all();

        $this->assertEquals([IpAuditLog::ACTION_CREATE, IpAuditLog::ACTION_UPDATE], $actions);
    }

    public function test_returns_422_for_invalid_date_from(): void
    {
        $this->actingAs($this->admin())
            ->getJson(self::AUDIT_LOGS_URL.'?date_from=not-a-date')
            ->assertStatus(422);
    }

    public function test_returns_422_for_invalid_date_to(): void
    {
        $this->actingAs($this->admin())
            ->getJson(self::AUDIT_LOGS_URL.'?date_to=2024-13-99')
            ->assertStatus(422);
    }
}
