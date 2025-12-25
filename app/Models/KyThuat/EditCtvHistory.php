<?php

namespace App\Models\KyThuat;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\KyThuat\WarrantyCollaborator;

/**
 * Class EditCtvHistory
 * 
 * @property int $id
 * @property int $collaborator_id
 * @property string $action_type
 * @property string|null $field_name
 * @property string|null $old_value
 * @property string|null $new_value
 * @property string $edited_by
 * @property string|null $comments
 * @property Carbon $edited_at
 *
 * @package App\Models
 */
class EditCtvHistory extends Model
{
    protected $table = 'edit_ctv_history';
    public $timestamps = false;

    protected $casts = [
        'old_collaborator_id' => 'int',
        'new_collaborator_id' => 'int',
        'installation_orders_id' => 'int',
        'edited_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected $fillable = [
        'installation_orders_id',
        'order_code',
        'event',
        'changes_old',
        'changes_new',
        'created_at',
        'old_collaborator_id',
        'new_collaborator_id',
        'action_type',
        'edited_by',
        'comments',
        'edited_at',
        'order_code',
    ];

    /**
     * Relationship với WarrantyCollaborator (old)
     */
    public function oldCollaborator()
    {
        return $this->belongsTo(WarrantyCollaborator::class, 'old_collaborator_id', 'id');
    }

    /**
     * Relationship với WarrantyCollaborator (new)
     */
    public function newCollaborator()
    {
        return $this->belongsTo(WarrantyCollaborator::class, 'new_collaborator_id', 'id');
    }
}
