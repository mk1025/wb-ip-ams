<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuthAuditLog;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class AuthAuditLogController extends Controller
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

        $query = AuthAuditLog::query()->orderBy('created_at', 'desc');

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('session_id')) {
            $query->where('session_id', $request->session_id);
        }

        $logs = $query->paginate(15);

        return $this->success($logs);
    }
}
