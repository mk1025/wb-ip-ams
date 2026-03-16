<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IpAuditLogFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer'],
            'entity_id' => ['nullable', 'integer'],
            'action' => ['nullable', 'string'],
            'ip_address' => ['nullable', 'string'],
            'session_id' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'sort_by' => ['nullable', 'string', 'in:action,user_id,entity_id,created_at'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'date_from' => 'start date',
            'date_to' => 'end date',
        ];
    }
}
