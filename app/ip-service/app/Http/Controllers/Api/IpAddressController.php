<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIpAddressRequest;
use App\Http\Requests\UpdateIpAddressRequest;
use App\Http\Resources\IpAddressResource;
use App\Models\IpAddress;
use App\Models\User;
use App\Services\IpAddressService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IpAddressController extends Controller
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

            return $this->ipAddressService->getIpAddresses($user, $request);
        } catch (\Throwable $th) {
            Log::error('Error fetching IP addresses: '.$th->getMessage());

            return $this->error('Error fetching IP addresses', 500);
        }
    }

    public function store(StoreIpAddressRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                return $this->unauthorized('User not authenticated');
            }

            return $this->ipAddressService->storeIpAddress($user, $request);
        } catch (\Throwable $th) {
            Log::error('Error creating IP address: '.$th->getMessage());

            return $this->error('Error creating IP address', 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $ipAddress = IpAddress::with('owner')->find($id);

            if (! $ipAddress) {
                return $this->notFound('IP address not found');
            }

            return $this->success(new IpAddressResource($ipAddress));
        } catch (\Throwable $th) {
            Log::error('Error fetching IP address: '.$th->getMessage());

            return $this->error('Error fetching IP address', 500);
        }

    }

    public function update(UpdateIpAddressRequest $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            $ipAddress = IpAddress::find($id);

            if (! $user) {
                return $this->unauthorized('User not authenticated');
            }

            if (! $ipAddress) {
                return $this->notFound('IP address not found');
            }

            if ($user->role !== User::ROLE_SUPER_ADMIN && $ipAddress->owner_id !== $user->id) {
                return $this->forbidden('You do not have permission to update this IP address');
            }

            return $this->ipAddressService->updateIpAddress($user, $ipAddress, $request);
        } catch (\Throwable $th) {
            Log::error('Error updating IP address: '.$th->getMessage());

            return $this->error('Error updating IP address', 500);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            $ipAddress = IpAddress::find($id);

            if (! $user) {
                return $this->unauthorized('User not authenticated');
            }

            if (! $ipAddress) {
                return $this->notFound('IP address not found');
            }

            if ($user->role !== User::ROLE_SUPER_ADMIN) {
                return $this->forbidden('Only super-admins can delete IP addresses');
            }

            return $this->ipAddressService->deleteIpAddress($user, $ipAddress, $request);
        } catch (\Throwable $th) {
            Log::error('Error deleting IP address: '.$th->getMessage());

            return $this->error('Error deleting IP address', 500);
        }
    }
}
