<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\IpAddress;

class IpAddressShowTest extends IpFeatureTestCase
{
    public function test_returns_200_with_ip_resource(): void
    {
        $user = $this->user();
        $ip = IpAddress::create(['ip_address' => '198.51.100.1', 'label' => 'Show Test', 'owner_id' => $user->id]);

        $this->actingAs($user)->getJson(self::IP_ADDRESSES_URL."/{$ip->id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.ip_address', '198.51.100.1')
            ->assertJsonPath('data.id', $ip->id);
    }

    public function test_returns_404_for_nonexistent_id(): void
    {
        $this->actingAs($this->user())->getJson(self::IP_ADDRESSES_URL.'/9999')
            ->assertStatus(404);
    }

    public function test_returns_401_for_unauthenticated_request(): void
    {
        $this->getJson(self::IP_ADDRESSES_URL.'/1')->assertStatus(401);
    }
}
