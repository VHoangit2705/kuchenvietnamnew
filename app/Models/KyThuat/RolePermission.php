<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\KyThuat;

use Illuminate\Database\Eloquent\Model;

/**
 * Class RolePermission
 * 
 * @property int $role_id
 * @property int $permission_id
 * 
 * @property Role $role
 * @property Permission $permission
 *
 * @package App\Models\KyThuat
 */
class RolePermission extends Model
{
	protected $table = 'role_permission';
	public $incrementing = false;
	public $timestamps = false;
	protected $fillable = ['role_id', 'permission_id'];
	protected $casts = [
		'role_id' => 'int',
		'permission_id' => 'int'
	];

	public function role()
	{
		return $this->belongsTo(Role::class);
	}

	public function permission()
	{
		return $this->belongsTo(Permission::class);
	}
}
