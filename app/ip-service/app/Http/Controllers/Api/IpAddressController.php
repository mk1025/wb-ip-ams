<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIpAddressRequest;
use App\Http\Requests\UpdateIpAddressRequest;
use App\Http\Resources\IpAddressResource;
use App\Models\IpAddress;
use App\Models\IpAuditLog;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class IpAddressController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $user = $guard->user();

        $query = IpAddress::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', '%'.$search.'%')
                    ->orWhere('label', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('owner_id')) {
            $query->where('owner_id', $request->owner_id);
        } else {
            $ownership = $request->input('ownership', 'all');
            if ($ownership === 'mine') {
                $query->where('owner_id', $user->id);
            } elseif ($ownership === 'others') {
                $query->where('owner_id', '!=', $user->id);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $allowedSorts = ['ip_address', 'label', 'created_at'];

        $sortBy = in_array($request->input('sort_by'), $allowedSorts)
            ? $request->input('sort_by')
            : 'created_at';

        $sortDir = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDir)->with('owner');

        $ipAddresses = $query->paginate(15);

        $ownerOptions = Cache::remember('ip_address_owner_options', 60, fn () => IpAddress::with('owner:id,email')
            ->select('owner_id')
            ->selectRaw('count(*) as count')
            ->groupBy('owner_id')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->owner_id,
                'email' => $row->owner?->email,
                'count' => (int) $row->getAttribute('count'),
            ])
            ->filter(fn ($o) => ! is_null($o['email']))
            ->values()
            ->toArray());

        return $this->success([
            'items' => $ipAddresses->through(fn ($ip) => new IpAddressResource($ip)),
            'filter_options' => [
                'owners' => $ownerOptions,
            ],
        ]);
    }

    public function store(StoreIpAddressRequest $request): JsonResponse
    {
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $user = $guard->user();

        $ipAddress = IpAddress::create([
            'ip_address' => $request->ip_address,
            'label' => $request->label,
            'comment' => $request->comment,
            'owner_id' => $user->id,
        ]);

        $ipAddress->setRelation('owner', $user);

        $this->logAudit($user->id, 'create', $ipAddress->id, null, $ipAddress->toArray(), $request);

        Cache::forget('ip_address_owner_options');
        Cache::forget('ip_audit_user_options');
        Cache::forget('ip_audit_action_options');

        return $this->created(new IpAddressResource($ipAddress));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $ipAddress = IpAddress::with('owner')->find($id);

        if (! $ipAddress) {
            return $this->notFound('IP address not found');
        }

        return $this->success(new IpAddressResource($ipAddress));
    }

    public function update(UpdateIpAddressRequest $request, int $id): JsonResponse
    {
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $user = $guard->user();
        $userRole = $user->role ?? 'user';

        $ipAddress = IpAddress::find($id);

        if (! $ipAddress) {
            return $this->notFound('IP address not found');
        }

        // Check ownership - regular users can only update their own
        if ($userRole !== 'super-admin' && $ipAddress->owner_id !== $user->id) {
            return $this->forbidden('You do not have permission to update this IP address');
        }

        $snapshotKeys = ['ip_address', 'label', 'comment', 'owner_id'];
        $oldValues = $ipAddress->only($snapshotKeys);

        $ipAddress->update([
            'label' => $request->label,
            'comment' => $request->comment,
        ]);

        $ipAddress->load('owner');

        $this->logAudit($user->id, 'update', $ipAddress->id, $oldValues, $ipAddress->only($snapshotKeys), $request);

        Cache::forget('ip_address_owner_options');
        Cache::forget('ip_audit_user_options');
        Cache::forget('ip_audit_action_options');

        return $this->success(new IpAddressResource($ipAddress));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {

        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $user = $guard->user();
        $userRole = $user->role ?? 'user';

        $ipAddress = IpAddress::find($id);

        if (! $ipAddress) {
            return $this->notFound('IP address not found');
        }

        // Only super-admins can delete
        if ($userRole !== 'super-admin') {
            return $this->forbidden('Only super-admins can delete IP addresses');
        }

        $oldValues = $ipAddress->toArray();

        $ipAddress->delete();

        $this->logAudit($user->id, 'delete', $ipAddress->id, $oldValues, null, $request);

        Cache::forget('ip_address_owner_options');
        Cache::forget('ip_audit_user_options');
        Cache::forget('ip_audit_action_options');

        return $this->success(null, 'IP address deleted successfully');
    }

    // PRIVATES

    /**
     * @param  array<string, mixed>|null  $oldValue
     * @param  array<string, mixed>|null  $newValue
     */
    private function logAudit(int $userId, string $action, int $entityId, ?array $oldValue, ?array $newValue, Request $request): void
    {
        $sessionId = null;
        try {
            $raw = JWTAuth::parseToken()->getPayload()->get('session_id');
            $sessionId = is_string($raw) ? $raw : null;
        } catch (\Throwable) {
            // session_id is best-effort; no token in context during testing
        }

        IpAuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'entity_id' => $entityId,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $sessionId,
            'created_at' => now(),
        ]);
    }
}
