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
	 * Cache permissions để tránh query lặp lại
	 */
	protected $cachedPermissions = null;
	
	
	/**
	 * Cache permissions để tránh query lặp lại
	 */
	protected $cachedPermissions = null;
	
	public function roles()
	{
		return $this->belongsToMany(Role::class, 'user_role', 'user_id', 'role_id');
	}
    public function hasAnyRole($roles)
    {
         // Eager load roles nếu chưa có
        if (!$this->relationLoaded('roles')) {
            $this->load('roles');
        }
        
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
         // Eager load roles nếu chưa có
        if (!$this->relationLoaded('roles')) {
            $this->load('roles');
        }
        
        // so sánh không phân biệt hoa thường
        return $this->roles->contains(function ($role) use ($roleName) {
            return strcasecmp($role->name, $roleName) === 0;
        });
    }
    
    /**
     * Lấy danh sách permissions với cache
     */
    public function permissions()
    {
        // Nếu đã cache thì trả về cache
        if ($this->cachedPermissions !== null) {
            return $this->cachedPermissions;
        }
        // Eager load roles với permissions nếu chưa có
        if (!$this->relationLoaded('roles')) {
            $this->load(['roles.permissions']);
        } else {
            // Nếu roles đã load nhưng chưa có permissions thì load thêm
            $this->loadMissing('roles.permissions');
        }
        // Lấy permissions từ roles đã load
        $this->cachedPermissions = $this->roles
            ->pluck('permissions')
            ->flatten()
            ->unique('id');
            
        return $this->cachedPermissions;
    }

    public function hasPermission($permissionName)
    {
        // Sử dụng permissions() đã được cache
        return $this->permissions()->contains(fn($p) => strcasecmp($p->name, $permissionName) === 0);
    }
    
    /**
     * Reset cache permissions khi cần (ví dụ sau khi update roles)
     */
    public function clearPermissionCache()
    {
        $this->cachedPermissions = null;
    }
    
    public function findForPassport($username)
	{
		return $this->where('username', $username)->first();
	}
}

