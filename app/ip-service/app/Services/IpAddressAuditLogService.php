<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Resources\IpAuditLogResource;
use App\Models\IpAuditLog;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;

class IpAddressAuditLogService
{
    use ApiResponseTrait;

    public const CACHE_KEY_USER_OPTIONS = 'ip_audit_user_options';

    public const CACHE_KEY_ACTION_OPTIONS = 'ip_audit_action_options';

    private const ALLOWED_COLUMNS = ['action', 'user_id', 'entity_id', 'created_at'];

    private const PAGINATION_SIZE = 10;

    public function __construct(private JWTAuth $jwt) {}

    /**
     * @param  array<string, mixed>|null  $oldValue
     * @param  array<string, mixed>|null  $newValue
     */
    public function logAuditEvent(User $user, string $action, int $entityId, ?array $oldValue, ?array $newValue, Request $request): void
    {
        $sessionId = null;
        try {
            $raw = $this->jwt->parseToken()->getPayload()->get('session_id');
            $sessionId = is_string($raw) ? $raw : null;
        } catch (\Throwable) {
            // session_id is best-effort; no token in context during testing
        }

        IpAuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'entity_id' => $entityId,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $sessionId,
            'created_at' => now(),
        ]);

        Cache::forget(self::CACHE_KEY_USER_OPTIONS);
        Cache::forget(self::CACHE_KEY_ACTION_OPTIONS);
    }

    public function getIpAuditLogs(Request $request): JsonResponse
    {
        $sortBy = in_array($request->input('sort_by'), self::ALLOWED_COLUMNS)
            ? $request->input('sort_by')
            : 'created_at';

        $sortDir = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';

        $query = IpAuditLog::with('user:id,email')->orderBy($sortBy, $sortDir);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('entity_id')) {
            $query->where('entity_id', $request->input('entity_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('ip_address')) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $request->input('ip_address'));
            $query->where('ip_address', 'like', '%'.$escaped.'%');
        }

        if ($request->filled('date_from')) {
            if (! strtotime($request->input('date_from')) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->input('date_from'))) {
                return $this->error('Invalid date_from format. Use Y-m-d.', 422);
            }
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            if (! strtotime($request->input('date_to')) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->input('date_to'))) {
                return $this->error('Invalid date_to format. Use Y-m-d.', 422);
            }
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('session_id')) {
            $query->where('session_id', 'like', '%'.$request->input('session_id').'%');
        }

        $logs = $query->paginate(self::PAGINATION_SIZE);

        $userOptions = $this->getUserFilterOptions();
        $actionOptions = $this->getActionFilterOptions();

        return $this->success([
            'logs' => $logs->through(fn ($log) => new IpAuditLogResource($log)),
            'filter_options' => [
                'users' => $userOptions,
                'actions' => $actionOptions,
            ],
        ]);
    }

    /** @return array<int, array{id: int, email: string, count: int}> */
    private function getUserFilterOptions(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY_USER_OPTIONS, 60, function (): array {
                return IpAuditLog::with('user:id,email')
                    ->whereNotNull('user_id')
                    ->get(['id', 'user_id'])
                    ->groupBy('user_id')
                    ->map(fn ($rows) => [
                        'id' => (int) $rows->first()->user_id,
                        'email' => $rows->first()->user?->email,
                        'count' => $rows->count(),
                    ])
                    ->sortByDesc('count')
                    ->filter(fn ($u) => ! is_null($u['email']))
                    ->values()
                    ->toArray();
            });
        } catch (\Throwable $th) {
            Log::error('Error fetching user options for IP audit logs: '.$th->getMessage());

            throw $th;
        }
    }

    /** @return array<int, array{value: string, count: int}> */
    private function getActionFilterOptions(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY_ACTION_OPTIONS, 60, function (): array {
                return IpAuditLog::query()
                    ->get(['action'])
                    ->countBy('action')
                    ->sortByDesc(fn ($count) => $count)
                    ->map(fn ($count, $value) => [
                        'value' => $value,
                        'count' => $count,
                    ])
                    ->values()
                    ->toArray();
            });
        } catch (\Throwable $th) {
            Log::error('Error fetching action options for IP audit logs: '.$th->getMessage());

            throw $th;
        }
    }
}
