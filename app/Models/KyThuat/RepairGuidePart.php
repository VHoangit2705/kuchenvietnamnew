<?php

namespace App\Models\KyThuat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairGuidePart extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'repair_guide_parts';

    protected $fillable = [
        'repair_guide_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function repairGuide()
    {
        return $this->belongsTo(RepairGuide::class, 'repair_guide_id');
    }
}
