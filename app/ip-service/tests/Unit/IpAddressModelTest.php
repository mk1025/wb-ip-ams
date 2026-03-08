<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\IpAddress;
use App\Models\IpAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IpAddressModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_relationship_returns_correct_user(): void
    {
        $user = User::factory()->create();
        $ip = IpAddress::create([
            'ip_address' => '10.0.0.1',
            'label' => 'Test',
            'owner_id' => $user->id,
        ]);

        $owner = $ip->owner;

        $this->assertInstanceOf(User::class, $owner);
        $this->assertEquals($user->id, $owner->id);
        $this->assertEquals($user->email, $owner->email);
    }

    public function test_audit_logs_relationship_scopes_to_entity_id(): void
    {
        $user = User::factory()->create();
        $ip1 = IpAddress::create(['ip_address' => '10.0.0.2', 'label' => 'One', 'owner_id' => $user->id]);
        $ip2 = IpAddress::create(['ip_address' => '10.0.0.3', 'label' => 'Two', 'owner_id' => $user->id]);

        IpAuditLog::create(['user_id' => $user->id, 'action' => 'create', 'entity_id' => $ip1->id, 'created_at' => now()]);
        IpAuditLog::create(['user_id' => $user->id, 'action' => 'create', 'entity_id' => $ip2->id, 'created_at' => now()]);

        $logs = $ip1->auditLogs()->get();

        $this->assertCount(1, $logs);
        $this->assertEquals($ip1->id, $logs->first()->entity_id);
    }
}
