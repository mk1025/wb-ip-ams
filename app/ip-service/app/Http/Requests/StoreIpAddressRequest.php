<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Helpers\IpValidator;
use Illuminate\Foundation\Http\FormRequest;

class StoreIpAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ip_address' => [
                'required',
                'string',
                'unique:ip_addresses,ip_address',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! IpValidator::isValid($value)) {
                        $fail('The IP address must be a valid IPv4 or IPv6 address.');
                    }
                },
            ],
            'label' => [
                'required',
                'string',
                'max:255',
            ],
            'comment' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ip_address.required' => 'IP address is required',
            'ip_address.unique' => 'This IP address already exists',
            'label.required' => 'Label is required',
            'label.max' => 'Label must not exceed 255 characters',
        ];
    }
}
