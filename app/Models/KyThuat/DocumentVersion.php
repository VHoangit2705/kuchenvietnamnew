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
        'file_path',
        'file_type',
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
}
