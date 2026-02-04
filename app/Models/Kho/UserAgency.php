<?php

namespace App\Models\Kho;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class UserAgency extends Authenticatable
{
    use Notifiable;

    protected $connection = 'mysql3';
    protected $table = 'user_agency';

    protected $fillable = [
        'username',
        'password',
        'fullname',
        'phone_verified_at',
        'status',
        'otp_oa',
        'otp_expires_at',
        'remember_token',
        'agency_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_oa',
    ];

    protected $casts = [
        'phone_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'status' => 'integer',
        'agency_id' => 'integer',
    ];

    /**
     * Quan hệ: UserAgency belongsTo Agency
     * Một user đại lý thuộc về một đại lý
     */
    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id', 'id');
    }

    /**
     * Kiểm tra user đã được xác minh chưa
     * Điều kiện: status = 1, có phone_verified_at và đã liên kết agency_id
     */
    public function isVerified(): bool
    {
        return $this->status === 1
            && $this->phone_verified_at !== null
            && $this->agency_id !== null;
    }

    /**
     * Kiểm tra OTP còn hiệu lực không
     */
    public function isOtpValid(): bool
    {
        return $this->otp_expires_at !== null && $this->otp_expires_at->isFuture();
    }
}
