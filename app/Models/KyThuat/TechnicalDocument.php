<?php

namespace App\Models\KyThuat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Kho\ProductModel;
use App\Models\KyThuat\DocumentVersion;
use App\Models\KyThuat\RepairGuide;

class TechnicalDocument extends Model
{
    use HasFactory;

    /**
     * Kết nối DB (cùng mysql với document_versions, repair_guide_documents)
     */
    protected $connection = 'mysql';

    /**
     * Tên bảng
     */
    protected $table = 'technical_documents';

    /**
     * Mass assignment
     */
    protected $fillable = [
        'model_id',
        'doc_type',
        'title',
        'description',
        'status',
    ];

    /**
     * Quan hệ logic: Document → Product Model
     * (cross-database relation)
     */
    public function productModel()
    {
        return $this->belongsTo(ProductModel::class, 'model_id');
    }

    /**
     * Quan hệ: Document → Các phiên bản tài liệu
     */
    public function documentVersions()
    {
        return $this->hasMany(DocumentVersion::class, 'document_id');
    }

    /**
     * Quan hệ: Document ↔ Repair Guides (qua bảng trung gian)
     */
    public function repairGuides()
    {
        return $this->belongsToMany(
            RepairGuide::class,
            'repair_guide_documents',
            'document_id',
            'repair_guide_id'
        );
    }
}
