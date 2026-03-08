<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RefreshTokenRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\AuthResource;
use App\Http\Resources\TokenResource;
use App\Http\Resources\UserResource;
use App\Models\AuthAuditLog;
use App\Models\RefreshToken;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use ApiResponseTrait;

    // Register new user
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user', // Default role
        ]);

        // Sync user to IP service
        $this->syncUserToIpService($user);

        $accessToken = JWTAuth::fromUser($user);
        $refreshToken = $this->createRefreshToken($user);

        $this->logAuthEvent($user, 'register', $request);

        $resource = new AuthResource($user, $accessToken, $refreshToken->token);

        return $this->created($resource);
    }

    // Login user
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        // For some reason, it doesn't recognize the guard when using auth()->attempt(), so we need to specify it explicitly
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = auth('api');

        if (! $token = $guard->attempt($credentials)) {
            return $this->unauthorized('Invalid credentials');
        }

        $user = $guard->user();

        // Sync user to IP service on login
        $this->syncUserToIpService($user);

        $refreshToken = $this->createRefreshToken($user);

        // Log the login
        $this->logAuthEvent($user, 'login', $request);

        $resource = new AuthResource($user, $token, $refreshToken->token);

        return $this->success($resource);
    }

    // Logout user
    public function logout(Request $request)
    {
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $user = $guard->user();

        RefreshToken::where('user_id', $user->id)->delete();

        $this->logAuthEvent($user, 'logout', $request);

        $guard->logout();

        return $this->success(null, 'Successfully logged out');
    }

    // Get authenticated user
    public function me()
    {
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = auth('api');

        return $this->success(new UserResource($guard->user()));
    }

    // Refresh access token using refresh token
    public function refresh(RefreshTokenRequest $request)
    {
        $refreshToken = RefreshToken::where('token', $request->refresh_token)->first();

        if (! $refreshToken || $refreshToken->isExpired()) {
            return $this->unauthorized('Invalid or expired refresh token');
        }

        $user = $refreshToken->user;

        $newAccessToken = JWTAuth::fromUser($user);

        $this->logAuthEvent($user, 'token_refresh', $request);

        $resource = new TokenResource($newAccessToken);

        return $this->success($resource);
    }

    // PRIVATES

    // Create new refresh token for user and delete old ones
    private function createRefreshToken(User $user): RefreshToken
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

    // Log authentication events for auditing
    private function logAuthEvent(User $user, string $action, Request $request): void
    {
        AuthAuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => null,
            'created_at' => now(),
        ]);
    }

    private function syncUserToIpService($user)
    {
        try {
            $url = config('services.ip.url', default: 'http://localhost:8001');
            $secret = config('app.internal_secret');

            Http::timeout(5)
                ->withHeaders(['X-Internal-Secret' => $secret])
                ->post("{$url}/api/internal/users/sync", [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                ]);
        } catch (\Exception $e) {

            \Log::warning('Failed to sync user to IP service: '.$e->getMessage());
        }
    }
}
