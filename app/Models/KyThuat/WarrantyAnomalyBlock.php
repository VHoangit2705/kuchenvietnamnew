<?php

namespace App\Models\KyThuat;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WarrantyAnomalyBlock extends Model
{
    protected $table = 'warranty_anomaly_blocks';
    public $timestamps = true;

    protected $fillable = [
        'staff_name',
        'branch',
        'date',
        'blocked_until',
        'count_when_blocked',
        'threshold',
        'is_active',
    ];

    protected $casts = [
        'date' => 'date',
        'blocked_until' => 'datetime',
        'count_when_blocked' => 'integer',
        'threshold' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Kiểm tra xem nhân viên có đang bị chặn không
     */
    public static function isBlocked($staffName, $branch, $date = null)
    {
        if (!$date) {
            $date = Carbon::today();
        }

        return self::where('staff_name', $staffName)
            ->where('branch', $branch)
            ->where('date', $date)
            ->where('is_active', true)
            ->where('blocked_until', '>', now())
            ->exists();
    }

    /**
     * Lấy thông tin block hiện tại
     */
    public static function getActiveBlock($staffName, $branch, $date = null)
    {
        if (!$date) {
            $date = Carbon::today();
        }

        return self::where('staff_name', $staffName)
            ->where('branch', $branch)
            ->where('date', $date)
            ->where('is_active', true)
            ->where('blocked_until', '>', now())
            ->first();
    }
}

