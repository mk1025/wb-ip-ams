<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuditLogFilterRequest;
use App\Models\AuthAuditLog;
use App\Services\AuthAuditLogService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AuthAuditLogController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private AuthAuditLogService $logService) {}

    public function index(AuditLogFilterRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->cannot('viewAny', AuthAuditLog::class)) {
                return $this->forbidden('Only super-admins can view audit logs');
            }

            return $this->logService->getAuthAuditLogs($request);
        } catch (\Throwable $th) {
            Log::error('Error fetching auth audit logs: '.$th->getMessage());

            return $this->error('Error fetching auth audit logs', 500);
        }
    }
}
