<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IpAddress;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class IpStatsController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $user = $guard->user();

        $total = IpAddress::count();
        $mine = IpAddress::where('owner_id', $user->id)->count();
        $others = IpAddress::where('owner_id', '!=', $user->id)->count();

        return $this->success([
            'total' => $total,
            'mine' => $mine,
            'others' => $others,
        ]);
    }
}
