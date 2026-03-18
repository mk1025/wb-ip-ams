<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Resources\AuthResource;
use App\Http\Resources\TokenResource;
use App\Models\AuthAuditLog;
use App\Models\RefreshToken;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class AuthService
{
    use ApiResponseTrait;

    public function __construct(private UserSyncService $userSyncService, private TokenService $tokenService, private AuthAuditLogService $logService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function registerUser(array $data, Request $request): JsonResponse
    {

        $user = User::create([
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => User::ROLE_USER,
        ]);

        $this->userSyncService->syncUserToIpService($user);

        $sessionId = (string) Str::uuid();

        $accessToken = $this->tokenService->createAccessToken($user, $sessionId);
        $refreshToken = $this->tokenService->createRefreshToken($user);

        $this->logService->logAuthEvent($user, AuthAuditLog::ACTION_REGISTER, $request, $sessionId);

        $resource = new AuthResource($user, $accessToken);

        $cookie = $this->refreshTokenCookie($refreshToken->token);

        return $this->created($resource)->cookie($cookie);

    }

    public function loginUser(User $user, Request $request): JsonResponse
    {
        $this->userSyncService->syncUserToIpService($user);

        $sessionId = (string) Str::uuid();

        $accessToken = $this->tokenService->createAccessToken($user, $sessionId);
        $refreshToken = $this->tokenService->createRefreshToken($user);

        $this->logService->logAuthEvent($user, AuthAuditLog::ACTION_LOGIN, $request, $sessionId);

        $resource = new AuthResource($user, $accessToken);

        $cookie = $this->refreshTokenCookie($refreshToken->token);

        return $this->success($resource)->cookie($cookie);

    }

    public function logoutUser(User $user, Request $request): JsonResponse
    {
        $sessionId = $this->tokenService->getJWTSessionId();

        RefreshToken::where('user_id', $user->id)->delete();

        $this->logService->logAuthEvent($user, AuthAuditLog::ACTION_LOGOUT, $request, $sessionId);

        $this->tokenService->invalidateToken();

        $cookie = $this->refreshTokenCookie('', -1);

        return $this->success(null, 'Successfully logged out')->cookie($cookie);

    }

    public function refreshToken(User $user, Request $request): JsonResponse
    {
        $sessionId = (string) Str::uuid();

        $newAccessToken = $this->tokenService->createAccessToken($user, $sessionId);

        $this->logService->logAuthEvent($user, AuthAuditLog::ACTION_TOKEN_REFRESH, $request, $sessionId);

        $resource = new TokenResource($newAccessToken);

        return $this->success($resource);

    }

    private function refreshTokenCookie(string $token, ?int $minutes = null): Cookie
    {
        return cookie(
            'refresh_token',
            $token,
            $minutes ?? config('jwt.refresh_ttl'),
            '/api/auth/refresh',
            null,
            app()->environment('production'),
            true,
            false,
            'Lax',
        );
    }
}
