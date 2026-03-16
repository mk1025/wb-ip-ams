<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class IpFeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected const IP_ADDRESSES_URL = '/api/ip-addresses';

    protected const AUDIT_LOGS_URL = self::IP_ADDRESSES_URL.'/audit-logs';

    protected const STATS_URL = self::IP_ADDRESSES_URL.'/stats';

    protected const SYNC_URL = '/api/internal/users/sync';

    protected function user(string $role = User::ROLE_USER): User
    {
        return User::factory()->create(['role' => $role]);
    }

    protected function admin(): User
    {
        return $this->user(User::ROLE_SUPER_ADMIN);
    }
}
