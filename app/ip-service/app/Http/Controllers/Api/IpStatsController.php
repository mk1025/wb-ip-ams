<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\IpAddressService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IpStatsController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private IpAddressService $ipAddressService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                return $this->unauthorized('User not authenticated');
            }

            return $this->ipAddressService->getStats($user);
        } catch (\Throwable $th) {
            Log::error('Error fetching IP stats: '.$th->getMessage());

            return $this->error('Error fetching IP stats', 500);
        }
    }
}
