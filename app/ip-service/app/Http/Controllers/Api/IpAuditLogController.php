<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IpAuditLog;
use App\Services\IpAddressAuditLogService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IpAuditLogController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private IpAddressAuditLogService $logService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->cannot('viewAny', IpAuditLog::class)) {
                return $this->forbidden('Only super-admins can view audit logs');
            }

            return $this->logService->getIpAuditLogs($request);
        } catch (\Throwable $th) {
            Log::error('Error fetching IP audit logs: '.$th->getMessage());

            return $this->error('Error fetching IP audit logs', 500);
        }
    }
}
