<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IpAddress;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IpStatsController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $user = $guard->user();

        $stats = IpAddress::selectRaw(
            'COUNT(*) as total, SUM(CASE WHEN owner_id = ? THEN 1 ELSE 0 END) as mine, SUM(CASE WHEN owner_id != ? THEN 1 ELSE 0 END) as others',
            [$user->id, $user->id]
        )->first();

        return $this->success([
            'total' => (int) $stats?->getAttribute('total'),
            'mine' => (int) $stats?->getAttribute('mine'),
            'others' => (int) $stats?->getAttribute('others'),
        ]);
    }
}
