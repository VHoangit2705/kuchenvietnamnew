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
        'product_id',
        'xuat_xu',
        'doc_type',
        'title',
        'description',
        'status',
    ];

    /**
     * Quan hệ: Document → Product
     */
    public function product()
    {
        return $this->belongsTo(\App\Models\Kho\Product::class, 'product_id');
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

    /**
     * Quan hệ: Document → File đính kèm (Attachments)
     */
    public function attachments()
    {
        return $this->hasMany(TechnicalDocumentAttachment::class, 'document_id');
    }
}
