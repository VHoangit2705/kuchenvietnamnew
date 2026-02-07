<?php

namespace App\Models\KyThuat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'document_versions';

    protected $fillable = [
        'document_id',
        'version',
        'img_upload',
        'video_upload',
        'pdf_upload',
        'status',
        'uploaded_by',
    ];

    public function technicalDocument()
    {
        return $this->belongsTo(TechnicalDocument::class, 'document_id');
    }

    public function documentShares()
    {
        return $this->hasMany(DocumentShare::class, 'document_version_id');
    }

    public function getFilePathAttribute()
    {
        return $this->img_upload ?? $this->video_upload ?? $this->pdf_upload;
    }

    public function getFileTypeAttribute()
    {
        $path = $this->file_path;
        if ($path) {
            return strtolower(pathinfo($path, PATHINFO_EXTENSION));
        }
        return null;
    }
}
