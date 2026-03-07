<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function save(array $options = [])
    {
        if ($this->exists) {
            return false;
        }

        return parent::save($options);
    }

    public function delete()
    {
        return false;
    }
}
