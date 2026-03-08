<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IpAuditLogResource;
use App\Models\IpAuditLog;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class IpAuditLogController extends Controller
{
    //
    use ApiResponseTrait;

    public function index(Request $request)
    {
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $user = $guard->user();

        if ($user->role !== 'super-admin') {
            return $this->forbidden('Only super-admins can view audit logs');
        }

        $allowed = ['action', 'user_id', 'entity_id', 'created_at'];
        $sortBy = in_array($request->sortBy, $allowed) ? $request->sortBy : 'created_at';
        $sortDir = $request->sortDir === 'asc' ? 'asc' : 'desc';

        $query = IpAuditLog::with('user:id,email')->orderBy($sortBy, $sortDir);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('entity_id')) {
            $query->where('entity_id', $request->entity_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', '%'.$request->ip_address.'%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(10);

        $userOptions = IpAuditLog::with('user:id,email')
            ->selectRaw('user_id, count(*) as count')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->user_id,
                'email' => $row->user?->email,
                'count' => (int) $row->count,
            ])
            ->filter(fn ($u) => ! is_null($u['email']))
            ->values();

        $actionOptions = IpAuditLog::selectRaw('action, count(*) as count')
            ->groupBy('action')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'value' => $row->action,
                'count' => (int) $row->count,
            ]);

        $response = [
            'logs' => $logs->through(fn ($log) => new IpAuditLogResource($log)),
            'filter_options' => [
                'users' => $userOptions,
                'actions' => $actionOptions,
            ],
        ];

        return $this->success($response);
    }
}
