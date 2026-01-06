<?php

namespace App\Models\KyThuat;

use App\Models\Kho\Agency;
use Illuminate\Database\Eloquent\Model;
use App\Models\KyThuat\WarrantyCollaborator;

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
        'collaborator_id',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Constants cho status
    const STATUS_CHUA_XAC_NHAN_AGENCY = 'chua_xac_nhan_daily';
    const STATUS_DA_XAC_NHAN_AGENCY = 'da_xac_nhan_daily';
    const STATUS_DA_DIEU_PHOI = 'da_dieu_phoi';
    const STATUS_HOAN_THANH = 'hoan_thanh';
    const STATUS_DA_THANH_TOAN = 'da_thanh_toan';
    const STATUS_CHO_KIEM_TRA = 'cho_kiem_tra';

    /**
     * Quan hệ: RequestAgency belongsTo Agency
     * Một yêu cầu lắp đặt thuộc về một đại lý
     */
    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id', 'id');
    }

    /**
     * Quan hệ: RequestAgency belongsTo WarrantyCollaborator
     */
    public function collaborator()
    {
        return $this->belongsTo(WarrantyCollaborator::class, 'collaborator_id', 'id');
    }

    /**
     * Lấy danh sách trạng thái
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_CHUA_XAC_NHAN_AGENCY => 'Chưa xác nhận đại lý',
            self::STATUS_DA_XAC_NHAN_AGENCY => 'Đang xử lý',
            self::STATUS_DA_DIEU_PHOI => 'Đã điều phối',
            self::STATUS_HOAN_THANH => 'Hoàn thành',
            self::STATUS_DA_THANH_TOAN => 'Đã thanh toán',
            self::STATUS_CHO_KIEM_TRA => 'Chờ kiểm tra',
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

