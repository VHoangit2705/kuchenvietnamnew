<?php

namespace App\Models\KyThuat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairGuide extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'repair_guides';

    protected $fillable = [
        'error_id',
        'title',
        'steps',
        'estimated_time',
        'safety_note',
    ];

    protected $casts = [
        'estimated_time' => 'integer',
    ];

    public function commonError()
    {
        return $this->belongsTo(CommonError::class, 'error_id');
    }

    public function technicalDocuments()
    {
        return $this->belongsToMany(
            TechnicalDocument::class,
            'repair_guide_documents',
            'repair_guide_id',
            'document_id'
        );
    }

    public function repairGuideDocuments()
    {
        return $this->hasMany(RepairGuideDocument::class, 'repair_guide_id');
    }

    public function repairGuideParts()
    {
        return $this->hasMany(RepairGuidePart::class, 'repair_guide_id');
    }

    public function warrantyCases()
    {
        return $this->hasMany(WarrantyCase::class, 'repair_guide_id');
    }
}
