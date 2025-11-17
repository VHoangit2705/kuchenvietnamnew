<?php

namespace App\Models\KyThuat;

use Illuminate\Database\Eloquent\Model;

class UserDeviceToken extends Model
{
    protected $table = 'user_device_tokens';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'device_fingerprint',
        'device_token',
        'ip_address',
        'browser_info',
        'is_active',
        'status',
        'approval_requested_at',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'approval_requested_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

