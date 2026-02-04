<?php

namespace App\Models\Kho;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\KyThuat\CommonError;
use App\Models\KyThuat\TechnicalDocument;
use App\Models\KyThuat\WarrantyCase;

class ProductModel extends Model
{
    use HasFactory;

    /**
     * Kết nối DB
     */
    protected $connection = 'mysql3';

    /**
     * Tên bảng
     */
    protected $table = 'product_models';

    /**
     * Các cột cho phép mass assignment
     */
    protected $fillable = [
        'product_id',
        'model_code',
        'version',
        'release_year',
        'xuat_xu',
        'status',
    ];

    /**
     * Cast dữ liệu
     */
    protected $casts = [
        'release_year' => 'integer',
    ];

    /**
     * Quan hệ: Model → Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * (Mở rộng sau) Quan hệ: Model → Lỗi kỹ thuật
     */
    public function commonErrors()
    {
        return $this->hasMany(CommonError::class, 'model_id');
    }

    /**
     * (Mở rộng sau) Quan hệ: Model → Tài liệu kỹ thuật
     */
    public function technicalDocuments()
    {
        return $this->hasMany(TechnicalDocument::class, 'model_id');
    }

    /**
     * Quan hệ: Model → Các case bảo hành (cross-DB)
     */
    public function warrantyCases()
    {
        return $this->hasMany(WarrantyCase::class, 'model_id');
    }
}
