<?php

namespace App\Models\KyThuat;

use Illuminate\Database\Eloquent\Model;

class WarrantyAnomalyAlert extends Model
{
    protected $table = 'warranty_anomaly_alerts';
    public $timestamps = true;

    protected $fillable = [
        'branch',
        'staff_name',
        'date',
        'staff_count',
        'total_count',
        'staff_count_in_branch',
        'average_count',
        'threshold',
        'alert_level',
        'is_resolved',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'staff_count' => 'integer',
        'total_count' => 'integer',
        'staff_count_in_branch' => 'integer',
        'average_count' => 'decimal:2',
        'threshold' => 'decimal:2',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}

