<?php

namespace App\Models\KyThuat;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

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
        'edited_at' => 'datetime'
    ];

    protected $fillable = [
        'old_collaborator_id',
        'new_collaborator_id',
        'action_type',
        'edited_by',
        'comments',
        'edited_at',
        // Thông tin CTV - Cũ và Mới
        'old_full_name', 'new_full_name',
        'old_phone', 'new_phone',
        'old_province', 'new_province',
        'old_province_id', 'new_province_id',
        'old_district', 'new_district',
        'old_district_id', 'new_district_id',
        'old_ward', 'new_ward',
        'old_ward_id', 'new_ward_id',
        'old_address', 'new_address',
        // Thông tin tài khoản CTV - Cũ và Mới
        'old_sotaikhoan', 'new_sotaikhoan',
        'old_chinhanh', 'new_chinhanh',
        'old_cccd', 'new_cccd',
        'old_ngaycap', 'new_ngaycap',
        // Thông tin đại lý - Cũ và Mới
        'old_agency_name', 'new_agency_name',
        'old_agency_phone', 'new_agency_phone',
        'old_agency_address', 'new_agency_address',
        'old_agency_paynumber', 'new_agency_paynumber',
        'old_agency_branch', 'new_agency_branch',
        'old_agency_cccd', 'new_agency_cccd',
        'old_agency_release_date', 'new_agency_release_date',
        'order_code'
    ];

    /**
     * Relationship với WarrantyCollaborator (old)
     */
    public function oldCollaborator()
    {
        return $this->belongsTo(\App\Models\KyThuat\WarrantyCollaborator::class, 'old_collaborator_id', 'id');
    }

    /**
     * Relationship với WarrantyCollaborator (new)
     */
    public function newCollaborator()
    {
        return $this->belongsTo(\App\Models\KyThuat\WarrantyCollaborator::class, 'new_collaborator_id', 'id');
    }
}
