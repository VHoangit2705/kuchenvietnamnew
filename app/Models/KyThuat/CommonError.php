<?php

namespace App\Models\KyThuat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Kho\ProductModel;

class CommonError extends Model
{
    use HasFactory;

    /**
     * Kết nối DB
     */
    protected $connection = 'mysql';

    /**
     * Tên bảng
     */
    protected $table = 'common_errors';

    /**
     * Mass assignment
     */
    protected $fillable = [
        'model_id',
        'error_code',
        'error_name',
        'severity',
        'description',
    ];

    /**
     * Quan hệ: Error → Product Model
     */
    public function productModel()
    {
        return $this->belongsTo(ProductModel::class, 'model_id');
    }

    /**
     * (Chuẩn bị cho bước tiếp theo)
     * Quan hệ: Error → Repair Guides
     */
    public function repairGuides()
    {
        return $this->hasMany(RepairGuide::class, 'error_id');
    }

    /**
     * Scope tiện dụng
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeCommon($query)
    {
        return $query->where('severity', 'common');
    }
}
