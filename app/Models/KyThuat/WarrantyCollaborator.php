<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\KyThuat;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class WarrantyCollaborator
 * 
 * @property int $id
 * @property string|null $full_name
 * @property string|null $gender
 * @property Carbon|null $date_of_birth
 * @property int|null $phone
 * @property string|null $address
 * @property string|null $area
 * @property string|null $avatar
 *
 * @package App\Models\Kho
 */
class WarrantyCollaborator extends Model
{
	protected $table = 'warranty_collaborator';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'id' => 'int',
		'date_of_birth' => 'datetime',
		'created_at' => 'datetime', // Sửa từ create_at thành created_at
	];

	protected $fillable = [
		'full_name',
		'gender',
		'date_of_birth',
		'phone',
		'province_id',
		'province',
		'district_id',
		'district',
		'ward_id',
		'ward',
		'address',
		'avatar',
		'created_at', // Sửa từ create_at thành created_at
		'create_by',
		'sotaikhoan',
		'chinhanh',
		'cccd',
		'ngaycap'
	];

	public static function getById($id)
    {
        return self::find($id);
    }
}
