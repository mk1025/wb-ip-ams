<?php

namespace App\Http\Controllers\Api;

use App\Helpers\IpValidator;
use App\Http\Controllers\Controller;
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
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $user = $guard->user();
        $userRole = $user->role ?? 'user';

        // Super-admin sees all, regular users see only their own
        $query = IpAddress::query();

        if ($userRole !== 'super-admin') {
            $query->where('owner_id', $user->id);
        }

        $ipAddresses = $query->orderBy('created_at', 'desc')->get();

        return $this->success(IpAddressResource::collection($ipAddresses));
    }

    public function store(Request $request)
    {
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $user = $guard->user();

        $validator = Validator::make($request->all(), [
            'ip_address' => 'required|string|unique:ip_addresses,ip_address',
            'label' => 'required|string|max:255',
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

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
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $user = $guard->user();
        $userRole = $user->role ?? 'user';

        $ipAddress = IpAddress::find($id);

        if (! $ipAddress) {
            return $this->notFound('IP address not found');
        }

        // Check ownership - regular users can only see their own
        if ($userRole !== 'super-admin' && $ipAddress->owner_id !== $user->id) {
            return $this->forbidden('You do not have permission to view this IP address');
        }

        return $this->success(new IpAddressResource($ipAddress));
    }

    public function update(Request $request, int $id)
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

        $validator = Validator::make($request->all(), [
            'ip_address' => 'required|string|unique:ip_addresses,ip_address,'.$id,
            'label' => 'required|string|max:255',
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        // Validate IP format
        if (! IpValidator::isValid($request->ip_address)) {
            return $this->validationError([
                'ip_address' => ['The IP address must be a valid IPv4 or IPv6 address.']
            ]);
        }

        $oldValues = $ipAddress->toArray();

        $ipAddress->update([
            'ip_address' => $request->ip_address,
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

        // Check ownership
        if ($userRole !== 'super-admin' && $ipAddress->owner_id !== $user->id) {
            return $this->forbidden('You do not have permission to delete this IP address');
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
