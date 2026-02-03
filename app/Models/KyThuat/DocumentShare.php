<?php

namespace App\Models\KyThuat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentShare extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'document_shares';

    protected $fillable = [
        'document_version_id',
        'share_token',
        'permission',
        'password_hash',
        'expires_at',
        'access_count',
        'last_access_at',
        'created_by',
        'status',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_access_at' => 'datetime',
    ];

    public function documentVersion()
    {
        return $this->belongsTo(DocumentVersion::class, 'document_version_id');
    }
}
