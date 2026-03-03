<?php

namespace App\Http\Requests;

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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
            'ip_address' => [
                'required',
                'string',
                'unique:ip_addresses,ip_address',
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
