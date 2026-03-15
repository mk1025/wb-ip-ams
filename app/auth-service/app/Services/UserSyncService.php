<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserSyncService
{
    public function syncUserToIpService(User $user): void
    {
        try {
            $url = config('services.ip.url', default: 'http://localhost:8001');

            $secret = config('app.internal_secret');

            $postUrl = "{$url}/api/internal/sync-user";

            Http::timeout(5)->withHeaders(
                ['X-Internal-Secret' => $secret]
            )->post($postUrl, [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,

            ]);

        } catch (\Exception $e) {
            Log::warning('Failed to sync user to IP service: '.$e->getMessage());
        }
    }
}
