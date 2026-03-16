<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Resources\AuthAuditLogResource;
use App\Models\AuthAuditLog;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AuthAuditLogService
{
    use ApiResponseTrait;

    public const CACHE_KEY_USER_OPTIONS = 'auth_audit_user_options';

    public const CACHE_KEY_ACTION_OPTIONS = 'auth_audit_action_options';

    private const ALLOWED_COLUMNS = ['action', 'user_id', 'ip_address', 'created_at'];

    private const PAGINATION_SIZE = 15;

    public function logAuthEvent(User $user, string $action, Request $request, ?string $sessionId = null): void
    {
        AuthAuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $sessionId,
            'created_at' => now(),
        ]);

        Cache::forget(self::CACHE_KEY_USER_OPTIONS);
        Cache::forget(self::CACHE_KEY_ACTION_OPTIONS);
    }

    public function getAuthAuditLogs(Request $request): JsonResponse
    {

        $sortBy = in_array($request->input('sort_by'), self::ALLOWED_COLUMNS) ? $request->input('sort_by') : 'created_at';

        $sortDir = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';

        $query = AuthAuditLog::with('user:id,email')->orderBy($sortBy, $sortDir);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('ip_address')) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $request->input('ip_address'));
            $query->where('ip_address', 'like', '%'.$escaped.'%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('session_id')) {
            $query->where('session_id', 'like', '%'.$request->input('session_id').'%');
        }

        $logs = $query->paginate(self::PAGINATION_SIZE);

        $userOptions = $this->getUserFilterOptions();
        $actionOptions = $this->getActionFilterOptions();

        $response = [
            'logs' => $logs->through(fn ($log) => new AuthAuditLogResource($log)),
            'filter_options' => [
                'users' => $userOptions,
                'actions' => $actionOptions,
            ],
        ];

        return $this->success($response);

    }

    /** @return array<int, array{id: int, email: string, count: int}> */
    private function getUserFilterOptions(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY_USER_OPTIONS, 60, function (): array {
                return User::withCount('authAuditLogs')
                    ->whereHas('authAuditLogs')
                    ->orderByDesc('auth_audit_logs_count')
                    ->get()
                    ->map(fn ($user) => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'count' => $user->auth_audit_logs_count,
                    ])
                    ->toArray();
            });
        } catch (\Throwable $th) {
            Log::error('Error fetching user options for audit logs: '.$th->getMessage());

            throw $th;
        }
    }

    /** @return array<int, array{value: string, count: int}> */
    private function getActionFilterOptions(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY_ACTION_OPTIONS, 60, function () {
                return AuthAuditLog::pluck('action')
                    ->countBy()
                    ->sortDesc()
                    ->map(fn ($count, $action) => [
                        'value' => $action,
                        'count' => $count,
                    ])
                    ->values()
                    ->toArray();
            });
        } catch (\Throwable $th) {
            Log::error('Error fetching action options for audit logs: '.$th->getMessage());

            throw $th;
        }
    }
}
