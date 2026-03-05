<?php

namespace App\Http\Controllers\Api;

use App\Helpers\IpValidator;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIpAddressRequest;
use App\Http\Requests\UpdateIpAddressRequest;
use App\Http\Resources\IpAddressResource;
use App\Models\IpAddress;
use App\Models\IpAuditLog;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IpAddressController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        $query = IpAddress::query()->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $query->where('ip_address', 'like', '%'.$request->search.'%');
        }

        $ipAddresses = $query->paginate(15);

        return $this->success(
            $ipAddresses->through(fn ($ip) => new IpAddressResource($ip))
        );
    }

    public function store(StoreIpAddressRequest $request)
    {
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $user = $guard->user();

        if (! IpValidator::isValid($request->ip_address)) {
            return $this->validationError([
                'ip_address' => ['The IP address must be a valid IPv4 or IPv6 address.']
            ]);
        }

        $ipAddress = IpAddress::create([
            'ip_address' => $request->ip_address,
            'label' => $request->label,
            'comment' => $request->comment,
            'owner_id' => $user->id,
        ]);


        $this->logAudit($user->id, 'create', $ipAddress->id, null, $ipAddress->toArray(), $request);

        return $this->created(new IpAddressResource($ipAddress));
    }

    public function show(Request $request, int $id)
    {
        $ipAddress = IpAddress::find($id);

        if (! $ipAddress) {
            return $this->notFound('IP address not found');
        }

        return $this->success(new IpAddressResource($ipAddress));
    }

    public function update(UpdateIpAddressRequest $request, int $id)
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

        $oldValues = $ipAddress->toArray();

        $ipAddress->update([
            'label' => $request->label,
            'comment' => $request->comment,
        ]);

        $this->logAudit($user->id, 'update', $ipAddress->id, $oldValues, $ipAddress->fresh()->toArray(), $request);

        return $this->success(new IpAddressResource($ipAddress));
    }

    public function destroy(Request $request, int $id)
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

        return $this->success(null, 'IP address deleted successfully');
    }

    // PRIVATES

    private function logAudit(int $userId, string $action, int $entityId, ?array $oldValue, ?array $newValue, Request $request): void
    {
        IpAuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => 'ip_address',
            'entity_id' => $entityId,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => session()->getId(),
            'created_at' => now(),
        ]);
    }
}
