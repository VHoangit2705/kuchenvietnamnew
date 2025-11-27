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
		'username',
		'email',
		'full_name',
		'password',
		'position',
		'zone',
		'img',
		'cookie_value',
		'password_changed_at'
	];
	
	/**
	 * Cache roles và permissions để tránh query lặp lại
	 */
	protected $cachedRoles = null;
	protected $cachedPermissions = null;
	
	public function roles()
	{
		return $this->belongsToMany(Role::class, 'user_role', 'user_id', 'role_id');
	}
	
	/**
	 * Lấy danh sách roles của user với cache và eager load permissions
	 */
	protected function getCachedRoles()
	{
		if ($this->cachedRoles === null) {
			// Eager load roles cùng với permissions để tránh N+1 query
			$this->cachedRoles = $this->roles()->with('permissions')->get();
		}
		return $this->cachedRoles;
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
        // so sánh không phân biệt hoa thường, sử dụng cache để tránh query lặp lại
        return $this->getCachedRoles()
            ->contains(fn($role) => strcasecmp($role->name, $roleName) === 0);
    }
    
    /**
     * Lấy danh sách permissions của user
     * Sử dụng cache để tránh query lặp lại trong cùng một request
     */
    public function permissions()
    {
        if ($this->cachedPermissions === null) {
            // Sử dụng cached roles đã có permissions được eager load
            $this->cachedPermissions = $this->getCachedRoles()
                ->pluck('permissions')
                ->flatten()
                ->unique('id');
        }
        
        return $this->cachedPermissions;
    }

    public function hasPermission($permissionName)
    {
        // return  $this->permissions()->contains('name', $permissionName);
		return $this->permissions()->contains(fn($p) => strcasecmp($p->name, $permissionName) === 0);
    }
    
    public function findForPassport($username)
	{
		return $this->where('username', $username)->first();
	}
}
