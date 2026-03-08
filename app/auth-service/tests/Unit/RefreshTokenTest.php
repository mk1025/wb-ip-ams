<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\RefreshToken;
use Tests\TestCase;

class RefreshTokenTest extends TestCase
{
    public function test_is_expired_returns_true_when_expires_at_is_in_the_past(): void
    {
        $token = new RefreshToken;
        $token->expires_at = now()->subSecond();

        $this->assertTrue($token->isExpired());
    }

    public function test_is_expired_returns_false_when_expires_at_is_in_the_future(): void
    {
        $token = new RefreshToken;
        $token->expires_at = now()->addDay();

        $this->assertFalse($token->isExpired());
    }

    public function test_is_valid_returns_true_when_token_has_not_expired(): void
    {
        $token = new RefreshToken;
        $token->expires_at = now()->addDay();

        $this->assertTrue($token->isValid());
    }

    public function test_is_valid_returns_false_when_token_has_expired(): void
    {
        $token = new RefreshToken;
        $token->expires_at = now()->subSecond();

        $this->assertFalse($token->isValid());
    }
}
