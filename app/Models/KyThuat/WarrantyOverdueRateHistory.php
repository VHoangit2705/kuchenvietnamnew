<?php

namespace App\Models\KyThuat;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WarrantyOverdueRateHistory extends Model
{
    protected $table = 'warranty_overdue_rate_history';
    public $timestamps = true;

    protected $fillable = [
        'report_date',
        'report_type',
        'from_date',
        'to_date',
        'branch',
        'staff_received',
        'tong_tiep_nhan',
        'so_ca_qua_han',
        'ti_le_qua_han',
        'dang_sua_chua',
        'cho_khach_hang_phan_hoi',
        'da_hoan_tat',
    ];

    protected $casts = [
        'report_date' => 'date',
        'from_date' => 'date',
        'to_date' => 'date',
        'tong_tiep_nhan' => 'integer',
        'so_ca_qua_han' => 'integer',
        'ti_le_qua_han' => 'decimal:2',
        'dang_sua_chua' => 'integer',
        'cho_khach_hang_phan_hoi' => 'integer',
        'da_hoan_tat' => 'integer',
    ];
}

