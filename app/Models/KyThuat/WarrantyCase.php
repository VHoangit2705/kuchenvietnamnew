<?php

namespace App\Models\KyThuat;

use App\Models\Kho\ProductModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarrantyCase extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'warranty_cases';

    protected $fillable = [
        'model_id',
        'error_id',
        'repair_guide_id',
        'technician_id',
        'result',
        'note',
    ];

    /**
     * Quan hệ: Case → Product Model (logic, cross-DB)
     */
    public function productModel()
    {
        return $this->belongsTo(ProductModel::class, 'model_id');
    }

    public function commonError()
    {
        return $this->belongsTo(CommonError::class, 'error_id');
    }

    public function repairGuide()
    {
        return $this->belongsTo(RepairGuide::class, 'repair_guide_id');
    }
}
