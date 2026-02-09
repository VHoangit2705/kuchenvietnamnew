<?php

namespace App\Models\KyThuat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechnicalDocumentAttachment extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'technical_document_attachments';

    protected $fillable = [
        'document_id',
        'file_path',
        'file_type',
        'file_name',
    ];

    public function technicalDocument()
    {
        return $this->belongsTo(TechnicalDocument::class, 'document_id');
    }
}
