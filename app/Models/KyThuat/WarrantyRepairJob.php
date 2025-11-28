<?php

namespace App\Models\KyThuat;

use Illuminate\Database\Eloquent\Model;

class WarrantyRepairJob extends Model
{
    protected $table = 'warranty_repair_jobs';

    protected $fillable = [
        'warranty_request_id',
        'description',
        'component',
        'quantity',
        'unit_price',
        'total_price',
        'created_by',
    ];

    protected $casts = [
        'warranty_request_id' => 'int',
        'quantity' => 'float',
        'unit_price' => 'int',
        'total_price' => 'int',
    ];

    public function warrantyRequest()
    {
        return $this->belongsTo(WarrantyRequest::class, 'warranty_request_id');
    }

}

