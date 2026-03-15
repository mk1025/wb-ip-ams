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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthService
{
    use ApiResponseTrait;

    public function __construct(private UserSyncService $userSyncService, private TokenService $tokenService, private AuthAuditLogService $logService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function registerUser(array $data): JsonResponse
    {
        try {
            $request = request();

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
        } catch (\Throwable $th) {
            Log::error('Error registering user: '.$th->getMessage());

            throw $th;
        }
    }

    public function loginUser(User $user): JsonResponse
    {
        try {
            $request = request();

            $this->userSyncService->syncUserToIpService($user);

            $sessionId = (string) Str::uuid();

            $accessToken = $this->tokenService->createAccessToken($user, $sessionId);
            $refreshToken = $this->tokenService->createRefreshToken($user);

            $this->logService->logAuthEvent($user, AuthAuditLog::ACTION_LOGIN, $request, $sessionId);

            $resource = new AuthResource($user, $accessToken, $refreshToken->token);

            return $this->success($resource);
        } catch (\Throwable $th) {
            Log::error('Error logging in user: '.$th->getMessage());

            throw $th;
        }
    }

    public function logoutUser(): JsonResponse
    {
        try {
            $request = request();

            /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
            $guard = auth('api');
            $user = $guard->user();

            $sessionId = $this->tokenService->getJWTSessionId();

            RefreshToken::where('user_id', $user->id)->delete();

            $this->logService->logAuthEvent($user, AuthAuditLog::ACTION_LOGOUT, $request, $sessionId);

            $guard->logout();

            return $this->success(null, 'Successfully logged out');
        } catch (\Throwable $th) {
            Log::error('Error during logout: '.$th->getMessage());

            throw $th;
        }
    }

    public function refreshToken(User $user): JsonResponse
    {
        try {
            $request = request();

            $sessionId = (string) Str::uuid();

            $newAccessToken = $this->tokenService->createAccessToken($user, $sessionId);

            $this->logService->logAuthEvent($user, AuthAuditLog::ACTION_TOKEN_REFRESH, $request, $sessionId);

            $resource = new TokenResource($newAccessToken);

            return $this->success($resource);
        } catch (\Throwable $th) {
            Log::error('Error refreshing token: '.$th->getMessage());

            throw $th;
        }
    }
}
