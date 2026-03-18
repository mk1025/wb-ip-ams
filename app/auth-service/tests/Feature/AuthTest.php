<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\RefreshToken;
use App\Models\User;
use App\Services\TokenService;

class AuthTest extends AuthFeatureTestCase
{
    public function test_register_returns_201_with_user_and_tokens(): void
    {
        $response = $this->postJson(self::REGISTER_URL, [
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'email', 'role'],
                    'tokens' => ['access_token', 'token_type'],
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'newuser@example.com')
            ->assertJsonPath('data.user.role', 'user')
            ->assertJsonPath('data.tokens.token_type', 'bearer');

        $tokens = $response->json('data.tokens');
        $this->assertNotEmpty($tokens['access_token']);
        $response->assertCookie('refresh_token');
    }

    public function test_register_returns_422_for_missing_email(): void
    {
        $response = $this->postJson(self::REGISTER_URL, [
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)->assertJsonStructure(['message', 'errors']);
    }

    public function test_register_returns_422_for_missing_password(): void
    {
        $response = $this->postJson(self::REGISTER_URL, [
            'email' => 'newuser@example.com',
        ]);

        $response->assertStatus(422)->assertJsonStructure(['errors']);
    }

    public function test_register_returns_422_for_invalid_email_format(): void
    {
        $response = $this->postJson(self::REGISTER_URL, [
            'email' => 'not-a-valid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_returns_422_for_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson(self::REGISTER_URL, [
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_returns_422_for_password_too_short(): void
    {
        $response = $this->postJson(self::REGISTER_URL, [
            'email' => 'newuser@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_returns_200_with_tokens_on_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('secret1234'),
        ]);

        $response = $this->postJson(self::LOGIN_URL, [
            'email' => 'user@example.com',
            'password' => 'secret1234',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'email', 'role'],
                    'tokens' => ['access_token', 'token_type'],
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tokens.token_type', 'bearer');

        $tokens = $response->json('data.tokens');
        $this->assertNotEmpty($tokens['access_token']);
        $response->assertCookie('refresh_token');
    }

    public function test_login_returns_401_for_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->postJson(self::LOGIN_URL, [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_returns_401_for_invalid_credentials(): void
    {
        $response = $this->postJson(self::LOGIN_URL, [
            'email' => 'nobody@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_creates_refresh_token_in_db(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret1234')]);

        $this->postJson(self::LOGIN_URL, [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $this->assertDatabaseHas('refresh_tokens', ['user_id' => $user->id]);
    }

    public function test_logout_returns_200_and_deletes_refresh_tokens(): void
    {
        $user = User::factory()->create();
        RefreshToken::create([
            'user_id' => $user->id,
            'token' => str_repeat('a', 64),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($user, 'api')->postJson(self::LOGOUT_URL);

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertDatabaseMissing('refresh_tokens', ['user_id' => $user->id]);
    }

    public function test_logout_returns_401_for_unauthenticated_request(): void
    {
        $response = $this->postJson(self::LOGOUT_URL);

        $response->assertStatus(401);
    }

    public function test_me_returns_authenticated_user_data(): void
    {
        $user = User::factory()->create(['email' => 'iam@example.com', 'role' => User::ROLE_USER]);

        $response = $this->actingAs($user, 'api')->getJson(self::ME_URL);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'iam@example.com')
            ->assertJsonPath('data.role', User::ROLE_USER);
    }

    public function test_me_returns_401_for_unauthenticated_request(): void
    {
        $response = $this->getJson(self::ME_URL);

        $response->assertStatus(401);
    }

    public function test_refresh_returns_new_access_token_for_valid_refresh_token(): void
    {
        $user = User::factory()->create();

        $refreshToken = app(TokenService::class)->createRefreshToken($user);

        $response = $this->withCredentials()
            ->withUnencryptedCookie('refresh_token', $refreshToken->token)
            ->postJson(self::REFRESH_URL);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['access_token', 'token_type']])
            ->assertJsonPath('success', true);
    }

    public function test_refresh_does_not_delete_the_refresh_token(): void
    {
        $user = User::factory()->create();

        $refreshToken = app(TokenService::class)->createRefreshToken($user);

        $this->withCredentials()->withUnencryptedCookie('refresh_token', $refreshToken->token)
            ->postJson(self::REFRESH_URL);

        $this->assertDatabaseHas('refresh_tokens', ['id' => $refreshToken->id]);
    }

    public function test_refresh_returns_401_for_invalid_token(): void
    {
        $response = $this->withCredentials()
            ->withUnencryptedCookie('refresh_token', 'completely-bogus-token-that-does-not-exist')
            ->postJson(self::REFRESH_URL);

        $response->assertStatus(401);
    }

    public function test_refresh_returns_401_for_expired_token(): void
    {
        $user = User::factory()->create();

        $refreshToken = app(TokenService::class)->createRefreshToken($user);
        $refreshToken->update(['expires_at' => now()->subDay()]);

        $response = $this->withCredentials()
            ->withUnencryptedCookie('refresh_token', $refreshToken->token)
            ->postJson(self::REFRESH_URL);

        $response->assertStatus(401);
    }

    public function test_refresh_returns_401_for_missing_refresh_token_cookie(): void
    {
        $response = $this->postJson(self::REFRESH_URL);

        $response->assertStatus(401);
    }
}
