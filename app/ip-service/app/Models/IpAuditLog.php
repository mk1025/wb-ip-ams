<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpAuditLog extends Model
{
    //
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'entity_id',
        'old_value',
        'new_value',
        'ip_address',
        'user_agent',
        'session_id',
        'created_at',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
        'created_at' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            return false;
        }

        return parent::save($options);
    }

    public function delete(): bool
    {
        return false;
    }
}
