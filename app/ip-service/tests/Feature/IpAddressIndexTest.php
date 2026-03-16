<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\IpAddress;
use Illuminate\Support\Facades\DB;

class IpAddressIndexTest extends IpFeatureTestCase
{
    public function test_returns_200_with_paginated_list(): void
    {
        $user = $this->user();

        IpAddress::create(['ip_address' => '10.0.0.1', 'label' => 'One', 'owner_id' => $user->id]);
        IpAddress::create(['ip_address' => '10.0.0.2', 'label' => 'Two', 'owner_id' => $user->id]);

        $response = $this->actingAs($user)->getJson(self::IP_ADDRESSES_URL);

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

    public function test_search_filters_by_ip_address(): void
    {
        $user = $this->user();

        IpAddress::create(['ip_address' => '192.168.1.100', 'label' => 'Alpha', 'owner_id' => $user->id]);
        IpAddress::create(['ip_address' => '10.1.0.1', 'label' => 'Beta', 'owner_id' => $user->id]);

        $items = $this->actingAs($user)->getJson(self::IP_ADDRESSES_URL.'?search=192.168')
            ->json('data.items.data');

        $this->assertCount(1, $items);

        $this->assertEquals('192.168.1.100', $items[0]['ip_address']);
    }

    public function test_search_filters_by_label(): void
    {
        $user = $this->user();

        IpAddress::create(['ip_address' => '10.1.1.1', 'label' => 'Production Server', 'owner_id' => $user->id]);
        IpAddress::create(['ip_address' => '10.1.1.2', 'label' => 'Dev Machine', 'owner_id' => $user->id]);

        $items = $this->actingAs($user)->getJson(self::IP_ADDRESSES_URL.'?search=Production')
            ->json('data.items.data');

        $this->assertCount(1, $items);

        $this->assertEquals('Production Server', $items[0]['label']);
    }

    public function test_ownership_mine_returns_only_own_ips(): void
    {
        $user = $this->user();

        $other = $this->user();

        IpAddress::create(['ip_address' => '10.10.1.1', 'label' => 'Mine', 'owner_id' => $user->id]);
        IpAddress::create(['ip_address' => '10.10.1.2', 'label' => 'Theirs', 'owner_id' => $other->id]);

        $items = $this->actingAs($user)->getJson(self::IP_ADDRESSES_URL.'?ownership=mine')
            ->json('data.items.data');

        $this->assertCount(1, $items);

        $this->assertEquals($user->id, $items[0]['owner_id']);
    }

    public function test_ownership_others_returns_only_other_users_ips(): void
    {
        $user = $this->user();

        $other = $this->user();

        IpAddress::create(['ip_address' => '10.20.1.1', 'label' => 'Mine', 'owner_id' => $user->id]);
        IpAddress::create(['ip_address' => '10.20.1.2', 'label' => 'Theirs', 'owner_id' => $other->id]);

        $items = $this->actingAs($user)->getJson(self::IP_ADDRESSES_URL.'?ownership=others')
            ->json('data.items.data');

        $this->assertCount(1, $items);

        $this->assertEquals($other->id, $items[0]['owner_id']);
    }

    public function test_owner_id_filter_takes_precedence_over_ownership(): void
    {
        $user = $this->user();

        $other = $this->user();

        IpAddress::create(['ip_address' => '10.30.1.1', 'label' => 'Mine', 'owner_id' => $user->id]);
        IpAddress::create(['ip_address' => '10.30.1.2', 'label' => 'Theirs', 'owner_id' => $other->id]);

        $items = $this->actingAs($user)
            ->getJson(self::IP_ADDRESSES_URL."?ownership=mine&owner_id={$other->id}")
            ->json('data.items.data');

        $this->assertCount(1, $items);

        $this->assertEquals($other->id, $items[0]['owner_id']);
    }

    public function test_date_from_filter(): void
    {
        $user = $this->user();

        $old = IpAddress::create(['ip_address' => '172.16.1.1', 'label' => 'Old', 'owner_id' => $user->id]);

        IpAddress::create(['ip_address' => '172.16.1.2', 'label' => 'New', 'owner_id' => $user->id]);

        DB::table('ip_addresses')->where('id', $old->id)->update(['created_at' => now()->subDays(10)->toDateTimeString()]);

        $items = $this->actingAs($user)
            ->getJson(self::IP_ADDRESSES_URL.'?date_from='.now()->subDay()->toDateString())
            ->json('data.items.data');

        $this->assertCount(1, $items);

        $this->assertEquals('New', $items[0]['label']);
    }

    public function test_date_to_filter(): void
    {
        $user = $this->user();

        $old = IpAddress::create(['ip_address' => '172.17.1.1', 'label' => 'Old', 'owner_id' => $user->id]);

        IpAddress::create(['ip_address' => '172.17.1.2', 'label' => 'New', 'owner_id' => $user->id]);

        DB::table('ip_addresses')->where('id', $old->id)->update(['created_at' => now()->subDays(10)->toDateTimeString()]);

        $items = $this->actingAs($user)
            ->getJson(self::IP_ADDRESSES_URL.'?date_to='.now()->subDays(5)->toDateString())
            ->json('data.items.data');

        $this->assertCount(1, $items);

        $this->assertEquals('Old', $items[0]['label']);
    }

    public function test_includes_filter_options_owners(): void
    {
        $user = $this->user();

        IpAddress::create(['ip_address' => '172.20.1.1', 'label' => 'Test', 'owner_id' => $user->id]);

        $response = $this->actingAs($user)->getJson(self::IP_ADDRESSES_URL);

        $owners = $response->assertStatus(200)->json('data.filter_options.owners');

        $this->assertNotEmpty($owners);

        $this->assertEquals($user->id, $owners[0]['id']);

        $this->assertEquals($user->email, $owners[0]['email']);
    }

    public function test_sorts_by_label_asc(): void
    {
        $user = $this->user();

        IpAddress::create(['ip_address' => '172.21.1.1', 'label' => 'Zebra', 'owner_id' => $user->id]);
        IpAddress::create(['ip_address' => '172.21.1.2', 'label' => 'Alpha', 'owner_id' => $user->id]);

        $labels = collect($this->actingAs($user)
            ->getJson(self::IP_ADDRESSES_URL.'?sort_by=label&sort_dir=asc')
            ->json('data.items.data'))->pluck('label')->all();

        $this->assertEquals(['Alpha', 'Zebra'], $labels);
    }

    public function test_defaults_to_created_at_desc(): void
    {
        $user = $this->user();

        $first = IpAddress::create(['ip_address' => '172.22.1.1', 'label' => 'First', 'owner_id' => $user->id]);
        IpAddress::create(['ip_address' => '172.22.1.2', 'label' => 'Second', 'owner_id' => $user->id]);

        DB::table('ip_addresses')->where('id', $first->id)->update(['created_at' => now()->subHour()->toDateTimeString()]);

        $labels = collect($this->actingAs($user)
            ->getJson(self::IP_ADDRESSES_URL)
            ->json('data.items.data'))->pluck('label')->all();

        $this->assertEquals(['Second', 'First'], $labels);
    }

    public function test_returns_422_for_invalid_date_from(): void
    {
        $this->actingAs($this->user())
            ->getJson(self::IP_ADDRESSES_URL.'?date_from=not-a-date')
            ->assertStatus(422);
    }

    public function test_returns_422_for_invalid_date_to(): void
    {
        $this->actingAs($this->user())
            ->getJson(self::IP_ADDRESSES_URL.'?date_to=2024-13-99')
            ->assertStatus(422);
    }

    public function test_search_wildcards_are_escaped(): void
    {
        $user = $this->user();

        IpAddress::create(['ip_address' => '10.0.0.1', 'label' => 'Anything', 'owner_id' => $user->id]);

        $items = $this->actingAs($user)
            ->getJson(self::IP_ADDRESSES_URL.'?search=%')
            ->assertStatus(200)
            ->json('data.items.data');

        $this->assertCount(0, $items);
    }

    public function test_returns_401_for_unauthenticated_request(): void
    {
        $this->getJson(self::IP_ADDRESSES_URL)->assertStatus(401);
    }
}
