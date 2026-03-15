<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncUserRequest;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UserSyncController extends Controller
{
    use ApiResponseTrait;

    public function sync(SyncUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::find($validated['id']) ?? new User;

        $user->id = $validated['id'];

        $user->fill([
            'email' => $validated['email'],
            'role' => $validated['role'],
        ]);

        if (! $user->exists) {
            $user->password = Hash::make('not-used');
        }

        $emailTaken = User::where('email', $validated['email'])
            ->where('id', '!=', $validated['id'])
            ->exists();

        if ($emailTaken) {
            return $this->error('Email already assigned to a different user ID.', 422);
        }

        $user->save();

        return $this->success($user, 'User synced successfully');
    }
}
