<?php

namespace App\Models\KyThuat;

use Illuminate\Database\Eloquent\Model;

class WarrantyUploadError extends Model
{
    protected $table = 'warranty_upload_error';
    
    protected $fillable = [
        'warranty_request_id',
        'video_upload_error',
        'image_upload_error',
        'note_error',
    ];

    public function warrantyRequest()
    {
        return $this->belongsTo(WarrantyRequest::class, 'warranty_request_id', 'id');
    }
}

