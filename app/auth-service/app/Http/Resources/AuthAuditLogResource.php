<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthAuditLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_email' => $this->user?->email,
            'action' => $this->action,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'session_id' => $this->session_id,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
