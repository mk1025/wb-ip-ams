<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\AuthAuditLog;
use App\Models\RefreshToken;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use ApiResponseTrait;

    // Register new user
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

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

        return $this->created([
            'user' => new UserResource($user),
            'tokens' => [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken->token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ],
        ]);
    }

    // Login user
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

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

        return $this->success([
            'user' => new UserResource($user),
            'tokens' => [
                'access_token' => $token,
                'refresh_token' => $refreshToken->token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ],
        ]);
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
    public function refresh(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $refreshToken = RefreshToken::where('token', $request->refresh_token)->first();

        if (! $refreshToken || $refreshToken->isExpired()) {
            return $this->unauthorized('Invalid or expired refresh token');
        }

        $user = $refreshToken->user;

        $newAccessToken = JWTAuth::fromUser($user);

        $this->logAuthEvent($user, 'token_refresh', $request);

        return $this->success([
            'access_token' => $newAccessToken,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ]);
    }

    // PRIVATES

    // Create new refresh token for user and delete old ones
    private function createRefreshToken(User $user): RefreshToken
    {
        RefreshToken::where('user_id', $user->id)->delete();

        return RefreshToken::create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(30), // Refresh token valid for 30 days
        ]);
    }

    // Log authentication events for auditing
    private function logAuthEvent(User $user, string $action, Request $request): void
    {
        AuthAuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => session()->getId(),
            'created_at' => now(),
        ]);
    }

    private function syncUserToIpService($user)
    {
        try {
            $ipServiceUrl = config('app.ip_service_url', env('IP_SERVICE_URL', 'http://localhost:8001'));

            Http::timeout(5)->post("{$ipServiceUrl}/api/internal/users/sync", [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
            ]);
        } catch (\Exception $e) {

            \Log::warning('Failed to sync user to IP service: '.$e->getMessage());
        }
    }
}
