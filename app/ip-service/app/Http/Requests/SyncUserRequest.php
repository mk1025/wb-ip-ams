<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'email' => ['required', 'email'],
            'role' => ['required', 'string', Rule::in([User::ROLE_USER, User::ROLE_SUPER_ADMIN])],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'User ID is required',
            'id.integer' => 'User ID must be an integer',
            'email.required' => 'Email is required',
            'email.email' => 'Email must be a valid email address',
            'role.required' => 'Role is required',
            'role.string' => 'Role must be a string',
            'role.in' => 'Invalid role',
        ];
    }
}
