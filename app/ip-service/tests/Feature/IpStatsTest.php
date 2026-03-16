<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\IpAddress;

class IpStatsTest extends IpFeatureTestCase
{
    public function test_returns_correct_counts(): void
    {
        $user = $this->user();
        $other = $this->user();
        IpAddress::create(['ip_address' => '198.51.100.100', 'label' => 'Mine', 'owner_id' => $user->id]);
        IpAddress::create(['ip_address' => '198.51.100.101', 'label' => 'Mine2', 'owner_id' => $user->id]);
        IpAddress::create(['ip_address' => '198.51.100.102', 'label' => 'Theirs', 'owner_id' => $other->id]);

        $this->actingAs($user)->getJson(self::STATS_URL)
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 3)
            ->assertJsonPath('data.mine', 2)
            ->assertJsonPath('data.others', 1);
    }

    public function test_returns_401_for_unauthenticated_request(): void
    {
        $this->getJson(self::STATS_URL)->assertStatus(401);
    }
}
