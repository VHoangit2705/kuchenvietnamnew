<?php

namespace App\Models\KyThuat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairGuideDocument extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'repair_guide_documents';

    protected $fillable = [
        'repair_guide_id',
        'document_id',
    ];

    public function repairGuide(): BelongsTo
    {
        return $this->belongsTo(RepairGuide::class, 'repair_guide_id');
    }

    public function technicalDocument(): BelongsTo
    {
        return $this->belongsTo(TechnicalDocument::class, 'document_id');
    }
}
