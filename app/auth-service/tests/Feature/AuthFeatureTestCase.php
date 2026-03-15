<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

abstract class AuthFeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected const API_PREFIX = '/api/auth';

    protected const LOGIN_URL = self::API_PREFIX.'/login';

    protected const REGISTER_URL = self::API_PREFIX.'/register';

    protected const LOGOUT_URL = self::API_PREFIX.'/logout';

    protected const ME_URL = self::API_PREFIX.'/me';

    protected const REFRESH_URL = self::API_PREFIX.'/refresh';

    protected const AUDIT_LOGS_URL = self::API_PREFIX.'/audit-logs';

    protected const AUDIT_LOGS_TABLE = 'auth_audit_logs';

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
    }
}
