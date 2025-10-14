<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\KyThuat;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Role
 * 
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Permission[] $permissions
 * @property Collection|User[] $users
 *
 * @package App\Models\KyThuat
 */
class Role extends Model
{
	
	protected $table = 'roles';

	protected $fillable = [
		'name',
		'description'
	];

	public function permissions()
	{
		return $this->belongsToMany(Permission::class, 'role_permission');
	}

	public function users()
	{
		return $this->belongsToMany(User::class, 'user_role');
	}
}
