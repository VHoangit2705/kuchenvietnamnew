<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\KyThuat;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Passport\HasApiTokens;
// use Laravel\Sanctum\HasApiTokens; 

/**
 * Class User
 * 
 * @property int $id
 * @property string $full_name
 * @property string $password
 * @property string $position
 * @property string $zone
 * @property string $img
 * @property string $cookie_value
 *
 * @package App\Models\KyThuat
 */
// class User extends Authenticatable
class User extends Authenticatable  implements OAuthenticatable
{
	use HasApiTokens, HasFactory, Notifiable;
	protected $table = 'users';
	public $timestamps = false;

	protected $hidden = [
		'password'
	];

	protected $fillable = [
		'full_name',
		'password',
		'position',
		'zone',
		'img',
		'cookie_value'
	];
	
	public function roles()
	{
		return $this->belongsToMany(Role::class, 'user_role', 'user_id', 'role_id');
	}

	public function hasAnyRole($roles)
    {
        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role)) {
                    return true;
                }
            }
        } else {
            if ($this->hasRole($roles)) {
                return true;
            }
        }
        return false;
    }

    public function hasRole($roleName)
    {
        // so sánh không phân biệt hoa thường
        return $this->roles()->whereRaw('LOWER(name) = ?', [strtolower($roleName)])->exists();
    }

    public function permissions()
    {
        return $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id');
    }

    public function hasPermission($permissionName)
    {
        // return  $this->permissions()->contains('name', $permissionName);
		return $this->permissions() ->contains(fn($p) => strcasecmp($p->name, $permissionName) === 0);
    }
    
    public function findForPassport($username)
	{
		return $this->where('username', $username)->first();
	}
}
