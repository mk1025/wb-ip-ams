<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IpAddressFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'owner_id' => ['nullable', 'integer'],
            'ownership' => ['nullable', 'string', 'in:all,mine,others'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'sort_by' => ['nullable', 'string', 'in:ip_address,label,created_at'],
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
