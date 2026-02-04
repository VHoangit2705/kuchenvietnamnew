<?php

namespace App\Models\KyThuat;

use App\Models\Kho\Agency;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $connection = 'mysql';
    protected $table = 'notifications';
    
    protected $fillable = [
        'agency_id',
        'request_agency_id',
        'type',
        'title',
        'message',
        'status_old',
        'status_new',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Constants cho type
    const TYPE_STATUS_CHANGED = 'status_changed';
    const TYPE_ORDER_UPDATED = 'order_updated';
    const TYPE_PAYMENT_RECEIVED = 'payment_received';

    /**
     * Quan hệ: Notification belongsTo Agency
     */
    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id', 'id');
    }

    /**
     * Quan hệ: Notification belongsTo RequestAgency
     */
    public function requestAgency()
    {
        return $this->belongsTo(RequestAgency::class, 'request_agency_id', 'id');
    }

    /**
     * Đánh dấu đã đọc
     */
    public function markAsRead()
    {
        $this->is_read = true;
        $this->read_at = now();
        $this->save();
    }
}
