<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;

class UserSyncTest extends IpFeatureTestCase
{
    public function test_creates_new_user(): void
    {
        $response = $this->postJson(self::SYNC_URL, [
            'id' => 9001,
            'email' => 'synced@example.com',
            'role' => 'user',
        ], ['X-Internal-Secret' => config('app.internal_secret')]);

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertDatabaseHas('users', ['email' => 'synced@example.com', 'role' => 'user']);
    }

    public function test_updates_existing_user(): void
    {
        $existing = User::factory()->create(['email' => 'before@example.com', 'role' => 'user']);

        $response = $this->postJson(self::SYNC_URL, [
            'id' => $existing->id,
            'email' => 'after@example.com',
            'role' => 'super-admin',
        ], ['X-Internal-Secret' => config('app.internal_secret')]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $existing->id, 'email' => 'after@example.com', 'role' => 'super-admin']);
        $this->assertDatabaseMissing('users', ['email' => 'before@example.com']);
    }

    public function test_returns_401_without_secret_header(): void
    {
        $this->postJson(self::SYNC_URL, [
            'id' => 1, 'email' => 'x@example.com', 'role' => 'user',
        ])->assertStatus(401);
    }

    public function test_returns_401_with_wrong_secret(): void
    {
        $this->postJson(self::SYNC_URL, [
            'id' => 1, 'email' => 'x@example.com', 'role' => 'user',
        ], ['X-Internal-Secret' => 'wrong-secret'])->assertStatus(401);
    }

    public function test_returns_422_on_duplicate_email_for_different_id(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $other = User::factory()->create();

        $response = $this->postJson(self::SYNC_URL, [
            'id' => $other->id,
            'email' => 'taken@example.com',
            'role' => 'user',
        ], ['X-Internal-Secret' => config('app.internal_secret')]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }
}
