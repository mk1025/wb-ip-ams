<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IpAddress extends Model
{
    protected $fillable = [
        'ip_address',
        'label',
        'comment',
        'owner_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return HasMany<IpAuditLog, $this> */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(IpAuditLog::class, 'entity_id');
    }
}
