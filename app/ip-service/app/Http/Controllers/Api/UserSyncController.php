<?php

declare(strict_types=1);

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

        $user = User::find($validated['id']) ?? new User;
        $user->id = $validated['id'];
        $user->fill([
            'email' => $validated['email'],
            'role' => $validated['role'],
        ]);
        if (! $user->exists) {
            $user->password = bcrypt('not-used');
        }
        $user->save();

        return $this->success($user, 'User synced successfully');
    }
}
