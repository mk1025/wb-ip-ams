<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\RefreshToken;
use App\Models\User;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private AuthService $authService) {}

    public function store(RegisterRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            return $this->authService->registerUser($validated, $request);
        } catch (\Throwable $th) {
            Log::error('Error registering user: '.$th->getMessage());

            return $this->error('Error registering user', 500);
        }

    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check((string) $request->password, $user->password)) {
                return $this->unauthorized('Invalid credentials');
            }

            return $this->authService->loginUser($user, $request);
        } catch (\Throwable $th) {
            Log::error('Error logging in user: '.$th->getMessage());

            return $this->error('Error logging in user', 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            return $this->authService->logoutUser($user, $request);
        } catch (\Throwable $th) {
            Log::error('Error during logout: '.$th->getMessage());

            return $this->error('Error during logout', 500);
        }
    }

    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $resource = new UserResource($user);

            return $this->success($resource);
        } catch (\Throwable $th) {
            Log::error('Error fetching authenticated user: '.$th->getMessage());

            return $this->error('Error fetching authenticated user', 500);
        }

    }

    public function refresh(Request $request): JsonResponse
    {
        try {
            $token = $request->cookie('refresh_token');

            if (! $token) {
                return $this->unauthorized('Missing refresh token');
            }

            $hashedToken = hash('sha256', $token);

            $refreshToken = RefreshToken::where('token', $hashedToken)->first();

            if (! $refreshToken || $refreshToken->isExpired()) {
                return $this->unauthorized('Invalid or expired refresh token');
            }

            $user = $refreshToken->user;

            if (! $user) {
                return $this->notFound('User not found');
            }

            return $this->authService->refreshToken($user, $request);
        } catch (\Throwable $th) {
            Log::error('Error refreshing token: '.$th->getMessage());

            return $this->error('Error refreshing token', 500);
        }

    }
}
