<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class UserSyncController extends Controller
{
    //
    use ApiResponseTrait;

    public function sync(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
            'email' => 'required|email',
            'role' => 'required|string|in:user,super-admin',
        ]);

        $user = User::updateOrCreate(
            ['id' => $validated['id']],
            [
                'email' => $validated['email'],
                'role' => $validated['role'],
                'password' => bcrypt('not-used'),
            ]
        );

        return $this->success($user, 'User synced successfully');
    }
}
