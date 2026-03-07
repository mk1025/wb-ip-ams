<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpAddress extends Model
{
    //
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

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(IpAuditLog::class, 'entity_id')
            ->where('entity_type', 'ip_address');
    }
}
