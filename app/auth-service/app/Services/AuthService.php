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

        $resource = new AuthResource($user, $accessToken, $refreshToken->token);

        return $this->created($resource);

    }

    public function loginUser(User $user, Request $request): JsonResponse
    {
        $this->userSyncService->syncUserToIpService($user);

        $sessionId = (string) Str::uuid();

        $accessToken = $this->tokenService->createAccessToken($user, $sessionId);
        $refreshToken = $this->tokenService->createRefreshToken($user);

        $this->logService->logAuthEvent($user, AuthAuditLog::ACTION_LOGIN, $request, $sessionId);

        $resource = new AuthResource($user, $accessToken, $refreshToken->token);

        return $this->success($resource);

    }

    public function logoutUser(Request $request): JsonResponse
    {
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $user = $guard->user();

        $sessionId = $this->tokenService->getJWTSessionId();

        RefreshToken::where('user_id', $user->id)->delete();

        $this->logService->logAuthEvent($user, AuthAuditLog::ACTION_LOGOUT, $request, $sessionId);

        $guard->logout();

        return $this->success(null, 'Successfully logged out');

    }

    public function refreshToken(User $user, Request $request): JsonResponse
    {
        $sessionId = (string) Str::uuid();

        $newAccessToken = $this->tokenService->createAccessToken($user, $sessionId);

        $this->logService->logAuthEvent($user, AuthAuditLog::ACTION_TOKEN_REFRESH, $request, $sessionId);

        $resource = new TokenResource($newAccessToken);

        return $this->success($resource);

    }
}
