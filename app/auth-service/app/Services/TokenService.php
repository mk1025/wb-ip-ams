<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;

class TokenService
{
    public function __construct(private JWTAuth $jwt) {}

    public function createAccessToken(User $user, string $sessionId): string
    {

        return $this->jwt->claims(['session_id' => $sessionId])->fromUser($user);

    }

    public function createRefreshToken(User $user): RefreshToken
    {

        return DB::transaction(function () use ($user) {
            RefreshToken::where('user_id', $user->id)->delete();

            return RefreshToken::create([
                'user_id' => $user->id,
                'token' => Str::random(64),
                'expires_at' => now()->addDays(30),
            ]);
        });

    }

    public function getJWTSessionId(): ?string
    {
        try {
            $raw = $this->jwt->parseToken()->getPayload()->get('session_id');

            return is_string($raw) ? $raw : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function invalidateToken(): void
    {
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $guard->logout();
    }
}
