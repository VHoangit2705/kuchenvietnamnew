<?php

namespace App\Models\KyThuat;

use App\Models\Kho\Agency;
use Illuminate\Database\Eloquent\Model;

class RequestAgency extends Model
{
    protected $connection = 'mysql';
    protected $table = 'request_agency';
    
    protected $fillable = [
        'order_code',
        'product_name',
        'customer_name',
        'customer_phone',
        'installation_address',
        'notes',
        'status',
        'type',
        'agency_id',
        'received_at',
        'received_by',
        'assigned_to',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Constants cho status
    const STATUS_CHUA_XAC_NHAN_AGENCY = 'chua_xac_nhan_daily';
    const STATUS_CHUA_TIEP_NHAN = 'chua_tiep_nhan';
    const STATUS_DA_TIEP_NHAN = 'da_tiep_nhan';
    const STATUS_DA_DIEU_PHOI = 'da_dieu_phoi';

    /**
     * Quan hệ: RequestAgency belongsTo Agency
     * Một yêu cầu lắp đặt thuộc về một đại lý
     */
    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id', 'id');
    }

    /**
     * Lấy danh sách trạng thái
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_CHUA_XAC_NHAN_AGENCY => 'Chưa xác nhận đại lý',
            self::STATUS_CHUA_TIEP_NHAN => 'Chưa tiếp nhận',
            self::STATUS_DA_TIEP_NHAN => 'Đã tiếp nhận',
            self::STATUS_DA_DIEU_PHOI => 'Đã điều phối',
        ];
    }

    /**
     * Lấy tên trạng thái
     */
    public function getStatusNameAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }
}

