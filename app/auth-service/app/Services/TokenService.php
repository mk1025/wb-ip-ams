<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;

class TokenService
{
    public function __construct(private JWTAuth $jwt) {}

    public function createAccessToken(User $user, string $sessionId): string
    {
        try {
            return $this->jwt->claims(['session_id' => $sessionId])->fromUser($user);
        } catch (\Throwable $th) {
            Log::error('Error creating access token: '.$th->getMessage());

            throw $th;
        }
    }

    public function createRefreshToken(User $user): RefreshToken
    {
        try {
            return DB::transaction(function () use ($user) {
                RefreshToken::where('user_id', $user->id)->delete();

                return RefreshToken::create([
                    'user_id' => $user->id,
                    'token' => Str::random(64),
                    'expires_at' => now()->addDays(30),
                ]);
            });
        } catch (\Throwable $th) {
            Log::error('Error creating refresh token: '.$th->getMessage());

            throw $th;
        }
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
}
