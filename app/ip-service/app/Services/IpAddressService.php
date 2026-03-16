<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Resources\IpAddressResource;
use App\Models\IpAddress;
use App\Models\IpAuditLog;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IpAddressService
{
    use ApiResponseTrait;

    public const CACHE_KEY_OWNER_OPTIONS = 'ip_address_owner_options';

    public const COLUMN_KEYS = ['ip_address', 'label', 'comment', 'owner_id'];

    private const ALLOWED_SORTS = ['ip_address', 'label', 'created_at'];

    private const PAGINATION_SIZE = 15;

    public function __construct(private IpAddressAuditLogService $logService) {}

    public function getIpAddresses(User $user, Request $request): JsonResponse
    {
        $query = IpAddress::query();

        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\\%', '\\_'], $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', '%'.$search.'%')
                    ->orWhere('label', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('owner_id')) {
            $query->where('owner_id', $request->input('owner_id'));
        } else {
            $ownership = $request->input('ownership', 'all');
            if ($ownership === 'mine') {
                $query->where('owner_id', $user->id);
            } elseif ($ownership === 'others') {
                $query->where('owner_id', '!=', $user->id);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $sortBy = in_array($request->input('sort_by'), self::ALLOWED_SORTS)
            ? $request->input('sort_by')
            : 'created_at';

        $sortDir = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDir)->with('owner');

        $ipAddresses = $query->paginate(self::PAGINATION_SIZE);

        $ownerOptions = $this->getOwnerFilterOptions();

        return $this->success([
            'items' => $ipAddresses->through(fn ($ip) => new IpAddressResource($ip)),
            'filter_options' => [
                'owners' => $ownerOptions,
            ],
        ]);
    }

    public function storeIpAddress(User $user, Request $request): JsonResponse
    {
        $ipAddress = IpAddress::create([
            'ip_address' => $request->input('ip_address'),
            'label' => $request->input('label'),
            'comment' => $request->input('comment'),
            'owner_id' => $user->id,
        ]);

        $ipAddress->setRelation('owner', $user);

        $this->logService->logAuditEvent($user, IpAuditLog::ACTION_CREATE, $ipAddress->id, null, $ipAddress->only(self::COLUMN_KEYS), $request);

        Cache::forget(self::CACHE_KEY_OWNER_OPTIONS);

        return $this->created(new IpAddressResource($ipAddress));
    }

    public function updateIpAddress(User $user, IpAddress $ipAddress, Request $request): JsonResponse
    {
        $oldValues = $ipAddress->only(self::COLUMN_KEYS);

        $ipAddress->update([
            'label' => $request->input('label'),
            'comment' => $request->input('comment'),
        ]);

        $ipAddress->load('owner');

        $this->logService->logAuditEvent($user, IpAuditLog::ACTION_UPDATE, $ipAddress->id, $oldValues, $ipAddress->only(self::COLUMN_KEYS), $request);

        Cache::forget(self::CACHE_KEY_OWNER_OPTIONS);

        return $this->success(new IpAddressResource($ipAddress));
    }

    public function deleteIpAddress(User $user, IpAddress $ipAddress, Request $request): JsonResponse
    {
        $oldValues = $ipAddress->only(self::COLUMN_KEYS);

        $ipAddress->delete();

        $this->logService->logAuditEvent($user, IpAuditLog::ACTION_DELETE, $ipAddress->id, $oldValues, null, $request);

        Cache::forget(self::CACHE_KEY_OWNER_OPTIONS);

        return $this->success(null, 'IP address deleted successfully');
    }

    public function getStats(User $user): JsonResponse
    {
        $total = IpAddress::count();

        $mine = IpAddress::where('owner_id', $user->id)->count();

        return $this->success([
            'total' => $total,
            'mine' => $mine,
            'others' => $total - $mine,
        ]);
    }

    /** @return array<int, array{id: int, email: string, count: int}> */
    private function getOwnerFilterOptions(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY_OWNER_OPTIONS, 60, function (): array {
                return IpAddress::with('owner:id,email')
                    ->get(['id', 'owner_id'])
                    ->groupBy('owner_id')
                    ->map(fn ($rows) => [
                        'id' => (int) $rows->first()->owner_id,
                        'email' => $rows->first()->owner?->email,
                        'count' => $rows->count(),
                    ])
                    ->sortByDesc('count')
                    ->filter(fn ($o) => ! is_null($o['email']))
                    ->values()
                    ->toArray();
            });
        } catch (\Throwable $th) {
            Log::error('Error fetching owner options: '.$th->getMessage());

            throw $th;
        }
    }
}
